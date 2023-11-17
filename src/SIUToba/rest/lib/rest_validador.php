<?php
namespace SIUToba\rest\lib;

class rest_validador
{
    public static $mensajes = array(
        self::TIPO_INT => "El campo '%s' debe ser un n�mero entero. Se recibi� '%s'.%s",
        self::TIPO_NUMERIC => "El campo '%s' debe ser un n�mero decimal. Se recibi� '%s'.%s",
        self::TIPO_ALPHA => "El campo '%s' debe ser de texto a-zA-Z. Se recibi� '%s'.%s",
        self::TIPO_ALPHANUM => "El campo '%s' debe ser alfanum�rico. Se recibi� '%s'.%s",
        self::TIPO_DATE => "El campo '%s' debe ser una fecha. Se recibi� '%s'.%s",
        self::TIPO_TIME => "El campo '%s' debe ser una hora. Se recibi� '%s'.%s",
        self::TIPO_LONGITUD => "El campo '%s' debe tener longitud apropiada. Se recibi� '%s'.%s",
        self::OBLIGATORIO => "El campo '%s' es obligatoio.%s",
        self::TIPO_ENUM => "El campo '%s' no pertenece a la lista de opciones v�lidas. Se recibi� '%s'.%s",
        self::TIPO_MAIL => "El campo '%s' debe ser un mail. Se recibi� '%s'.%s",
        self::TIPO_CUSTOM => "El campo '%s' no es v�lido. Se recibi� '%s'.%s",
        self::TIPO_ARREGLO => "El campo '%s' debe ser un arreglo. Se recibi� '%s'.%s",
        'campos_no_permitidos' => "Se encontraron campos no permitidos: %s.",

    );
    const TIPO_INT = 'int';
    const TIPO_NUMERIC = 'numerico';
    const TIPO_ALPHA = 'alpha';
    const TIPO_ALPHANUM = 'alphanum';
    const TIPO_DATE = 'date'; //Parametros: format -> http://php.net/manual/en/datetime.createfromformat.php
    const TIPO_TIME = 'time'; //Parametros: format -> http://php.net/manual/en/datetime.createfromformat.php
    const TIPO_LONGITUD = 'longitud'; //Parametros: format -> min, max
    const OBLIGATORIO = 'obligatorio';
    const TIPO_TEXTO = 'texto';
    const TIPO_CUSTOM = 'custom';
    const TIPO_MAIL = 'mail';
    const TIPO_ENUM = 'enum'; //Parametros: array(opc1, opc2 ..)
    const TIPO_ARREGLO = 'arreglo';

    const MAIL_MAX_LENGTH = 127;

    /**
     * Todos los campos en los datos tienen que estar en las reglas (con un array vacio al menos)
     * Esto es para que no se introduzcan campos no desados y se puedan procesar automaticamente para hacer sqls.
     * Si se ingresan campos no aceptados, se lanza un error.
     * Para la especificacion se utiliza la misma que el hidratador, agrupando en un array _validar las reglas.
     * Notar las reglas que tienen parametros.
     * Ejemplo:
     * rest_validador::validar($data, array(
     * 'id_curso_externo' => array('_validar' => array(rest_validador::TIPO_LONGITUD => array('min' =>1, 'max' => 50), rest_validador::OBLIGATORIO )),
     * 'nombre'           => array(),
     * 'plataforma'    => array( '_mapeo' => 'id_plat', '_validar' => (rest_validador::TIPO_INT))
     * );.
     *
     * @param $data
     * @param $reglas_spec array
     * @param $relajar_ocultos boolean no valida la obligatoriedad de los campos que no est�n presentes
     *
     * @throws rest_error
     */
    public static function validar($data, $reglas_spec, $relajar_ocultos = false)
    {
        $errores = self::validar_recursivo($data, $reglas_spec, $relajar_ocultos);
        if (!empty($errores)) {
            throw new rest_error(400, "Error en la validaci�n del recurso", $errores);
        }
        return $errores;
    }

    protected static function validar_recursivo($data, $reglas_spec, $relajar_ocultos)
    {
        $errores = array();
        foreach ($reglas_spec as $nombre_campo => $spec_campo) {
            if (is_array($spec_campo) && isset($spec_campo['_validar'])) {
                $reglas = $spec_campo['_validar'];
            } else {
                $reglas = array();
            }

            if ($relajar_ocultos
                && !isset($data[$nombre_campo])
                && (array_search(self::OBLIGATORIO, $reglas)) !== false
            ) {
                unset($data[$nombre_campo]);
                continue; //no valido
            }

            $valor_campo = (isset($data[$nombre_campo])) ? $data[$nombre_campo] : null;
            unset($data[$nombre_campo]);

            if (is_array($spec_campo) && isset($spec_campo['_compuesto'])) {        //Es un objeto con reglas propias                
                $result = self::validar_recursivo($valor_campo, $spec_campo['_compuesto'], $relajar_ocultos);
            } else {
                $result = self::aplicar_reglas($reglas, $nombre_campo, $valor_campo);
            }
            if (is_array($result) && ! empty($result)) {
                $errores = array_merge_recursive($errores, $result);    
            }            
        }

        if (!empty($data)) {
            $errores['campos_no_permitidos'][] = sprintf(self::$mensajes['campos_no_permitidos'], implode(', ', array_keys($data)));
        }
        return $errores;
    }

    protected static function aplicar_reglas($reglas, $nombre_campo, $valor_campo)
    {
        if (!is_array($reglas)) { //es valido, es solo un campo permitido
            return;
        }

        $errores = array();
        foreach ($reglas as $regla_key => $regla) { //para todas las reglas del campo
            if (is_numeric($regla_key)) {
                $nombre_regla = $regla;
                $regla_params = array();
            } else {
                $nombre_regla = $regla_key;
                $regla_params = $regla;
            }

            if (!self::es_valido($valor_campo, $nombre_regla, $regla_params)) {
                $args = $regla_params ?
                    " Par�metros: ".implode(', ', array_keys($regla_params))." => ".implode(', ', $regla_params)
                    : '';
                $valor_campo = (is_array($valor_campo)) ? var_export($valor_campo, true) : $valor_campo;
                $errores[$nombre_campo][] = sprintf(self::$mensajes[$nombre_regla], $nombre_campo, $valor_campo, $args);
            }
        }
        return $errores;
    }

    /*
     * Retorna si un valor es valido, vacio es valido.
     */
    public static function es_valido($valor, $tipo, $options = array())
    {
        $valor = self::validar_campo($valor, $tipo, $options);

        return $valor !== false;
    }

    public static function validar_campo($valor, $tipo, $options = array())
    {
        $filter_options = array();
        $flags = '';

        $vacio = empty($valor) && 0 !== $valor && false !== $valor; //
        if ($vacio) {
            return ($tipo != self::OBLIGATORIO); //vacio es valido
        } else {
            if ($tipo == self::OBLIGATORIO) {
                return true;
            }
        }

        switch ($tipo) {
            case self::TIPO_ALPHA:
                $filter = FILTER_VALIDATE_REGEXP;
                $filter_options = array('regexp' => "/^[a-zA-Z]+$/");
                break;
            case self::TIPO_ALPHANUM:
                $filter = FILTER_VALIDATE_REGEXP;
                $filter_options = array('regexp' => "/^[a-zA-Z0-9]+$/");
                break;
            case self::TIPO_INT:
                $is_integer = is_integer($valor);
                $all_digits = ctype_digit($valor);
                if (($is_integer || $all_digits)) {
                    if (isset($options['min']) && $valor < $options['min']) {
                        return false;
                    }
                    if (isset($options['max']) && $valor > $options['max']) {
                        return false;
                    }

                    return $valor;
                }

                return false;
            case self::TIPO_NUMERIC:
                if (is_numeric($valor)) {
                    if (isset($options['min']) && $valor < $options['min']) {
                        return false;
                    }
                    if (isset($options['max']) && $valor > $options['max']) {
                        return false;
                    }

                    return $valor;
                }

                return false;
            case self::TIPO_MAIL:
                $filter = FILTER_VALIDATE_EMAIL;
                if (strlen($valor) > self::MAIL_MAX_LENGTH) {
                    return false;
                }
                break;
            case self::TIPO_TEXTO:
                return $valor;
                break;
            case self::TIPO_DATE:
                $date = date_parse_from_format($options['format'], $valor);
                if ($date['error_count'] == 0) {
                    if (checkdate($date['month'], $date['day'], $date['year'])) {
                        return $valor;
                    }
                }

                return false;
            case self::TIPO_TIME:
                $date = date_parse_from_format($options['format'], $valor);
                if ($date['error_count'] == 0) {
                    if (self::checktime($date['hour'], $date['minute'], $date['second'])) {
                        return $valor;
                    }
                }

                return false;
            case self::TIPO_LONGITUD:
                $l = strlen($valor);   
                if (isset($options['min']) && $l < $options['min']) {
                        return false;
                }
                if (isset($options['max']) && $l > $options['max']) {
                        return false;
                }

                return true;
            case self::TIPO_ENUM:
                return in_array($valor, $options, true);
            case self::TIPO_ARREGLO:
                if (is_array($valor)) {
                    $cant_items = count($valor);
                    if (isset($options['min']) && $cant_items < $options['min']) {
                        return false;
                    }
                    if (isset($options['max']) && $cant_items > $options['max']) {
                        return false;
                    }

                    return true;
                }
                return false;                
            case self::TIPO_CUSTOM:
                $filter = FILTER_VALIDATE_REGEXP;
                $format = $options['format'];
                $filter_options = array('regexp' => "/$format$/");
                break;
        }

        return filter_var($valor, $filter, array(
            'options' => $filter_options,
            'flags' => $flags,
        ));
    }

    public static function checktime($hour, $minute, $seconds = 0)
    {
        if ($hour > -1 && $hour < 24 && $minute > -1 && $minute < 60 && $seconds > -1 && $seconds < 60) {
            return true;
        }

        return false;
    }

    public static function const_name($value)
    {
        $x = $value;
        $fooClass = new \ReflectionClass('\SIUToba\rest\lib\rest_validador');
        $constants = $fooClass->getConstants();

        $constName = null;
        foreach ($constants as $name => $value) {
            if ($value == $x) {
                $constName = $name;
                break;
            }
        }

        return $constName;
    }
}
