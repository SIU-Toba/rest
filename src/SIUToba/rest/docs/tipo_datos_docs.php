<?php
namespace SIUToba\rest\docs;

class tipo_datos_docs
{
	//Tipos basicos permitidos por OpenApi
	protected static $tipos_basicos = array('array', 'boolean', 'integer', 
											'object', 'string', 'number');
	//Formatos basicos permitidos por OpenApi
	protected static $formatos_tipos = array('int32', 'int64', 'float', 'double',
											'byte', 'binary', 'date', 'date-time',
											'password');
	
	//Matriz de formato x tipo
	protected static $mapeo_tipos = array(
										'int32' => 'integer',
										'int64' => 'integer',
										'float' => 'number',
										'double' => 'number',
										'byte' => 'string',
										'binary' => 'string',
										'date' => 'string',
										'date-time' => 'string',
										'password' => 'string',
									);
	
	/**
	 * Obtiene el tipo de dato de un string con formato especifico
	 * @param string $tipo
	 * @example tipo_datos_docs::get_tipo_datos('$ref:Comision'); Devuelve ['$ref' => '#/components/schemas/Comision'].
	 * @example tipo_datos_docs::get_tipo_datos('int32'); Devuelve ['type' => 'integer', 'format' => 'int32'].
	 * @example tipo_datos_docs::get_tipo_datos('password'); Devuelve ['type' => 'string', 'format' => 'password'].
	 * @example tipo_datos_docs::get_tipo_datos('boolean'); Devuelve ['type' => 'boolean'].
	 * @example tipo_datos_docs::get_tipo_datos('integer'); Devuelve ['type' => 'integer'].
	 * @return mixed
	 */
	public static function get_tipo_datos($tipo)
    {
        $tipo = preg_replace("#[\{\}\"\s]#",'', $tipo);
        if (trim($tipo) == '') {
            return;
        }

        $refs = explode(':', $tipo);
        if (false === $refs) {
            $tipoRef = self::get_tipo_formato(trim($tipo));                           //Basic type - no name
        } else {
            if (substr($refs[0], 0, 1) == '$') {
                $tipoRef = array('$ref' => "#/components/schemas/". trim($refs[1]));   //Referred type {"$ref": "Defined@Model"}
            } else {
               $tipoEncontrado = (count($refs) > 1) ? $refs[1] : $refs[0];
               $tipoRef = self::get_tipo_formato(trim($tipoEncontrado));                    //Basic type - named {"id" : "integer"}
            }
        }
        return $tipoRef;
    }
	
	/**
	 * Obtiene el typo y formato segun definicion de OpenApi
	 * @param string $tipo Tipo/Formato que se desea convertir
	 * @return array
	 */
	public static function get_tipo_formato($tipo)
	{
		if (! is_array($tipo)) {
			$tipo_srch = strtolower($tipo);
			if (\in_array($tipo_srch, self::$formatos_tipos)) {
				return ['type' => self::$mapeo_tipos[$tipo_srch], 'format' => $tipo_srch];
			} elseif (\in_array($tipo_srch, self::$tipos_basicos)) {
				return ['type' => $tipo_srch];
			}
		}
		return ['type' => $tipo];			//Tipo definido por el usuario
	}
}