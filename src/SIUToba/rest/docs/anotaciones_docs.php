<?php

namespace SIUToba\rest\docs;

use ReflectionClass;
use SIUToba\rest\lib\rest_instanciador;
use \SIUToba\rest\docs\tipo_datos_docs;

class anotaciones_docs
{
    protected static $metodos_validos = array('get', 'put', 'post', 'delete');
    
    /**
     * @var \ReflectionClass
     */
    protected $reflexion;

    protected $anotaciones_clase;
    
    /**
     * La clase no puede tener namespaces (esta pensada para las del modelo).
     *
     * @param $archivo
     */
    public function __construct($archivo)
    {
        $i = new rest_instanciador();
        $i->archivo = $archivo;
        $obj = $i->get_instancia();

        $this->reflexion = new ReflectionClass($obj);
        $this->get_annotations($this->reflexion);
    }

    /**
     * Devuelve el nombre (con Namespace) de la clase
     * @return string
     */
    public function get_nombre_clase()
    {
        $name = $this->reflexion->getName();
        if ($this->reflexion->inNamespace()) {
            $name = $this->reflexion->getNamespaceName() .'\\'. $this->reflexion->getShortName();
        }
        return $name;
    }

    /**
     * Parsea una reflexion (metodo, clase) y devuelve las anotaciones en un arreglo.
     *
     * @param $reflexion
     *
     * @return array formato ['nombre'][]
     */
    protected function get_annotations($reflexion)
    {
        if (!isset($this->anotaciones_clase)) {
            $this->anotaciones_clase = $this->get_annotations_metodo($reflexion);
        }
        return $this->anotaciones_clase;
    }

    protected function get_annotations_metodo($reflexion)
    {
        $doc = $reflexion->getDocComment();
        $doc = $this->limpiar_doc_comment($doc);

        return $this->extraer_anotaciones($doc);
    }

    /**
     * Limpia los asteriscos y espacios de un phpdoc.
     *
     * @param $doc string php doc
     *
     * @return string el documento sin los caracteres
     */
    protected function limpiar_doc_comment($doc)
    {
        //remuevo /* */ y * de principio de linea
        $doc = preg_replace('#/\*+|\*/#', '', $doc);
        $doc = preg_replace('#^\s*\*+#m', '', $doc);
        //remuevo separadores de mas
        $doc = preg_replace('#\s+#', ' ', $doc);

        return $doc;
    }

    /**
     * En base a un string, extrae @ anotaciones.
     *
     * @param $doc string
     *
     * @return array formato ['nombre'][]
     */
    protected function extraer_anotaciones($doc)
    {
        //remuevo lo que esta antes del primer @
        //$annotations = preg_split('/^@/', $doc);
        $annotations = explode('@', $doc);
        array_shift($annotations);

        $retorno = array();
        foreach ($annotations as $annotation) {
            $pos = strpos($annotation, ' ');
            $nombre = substr($annotation, 0, $pos);
            $contenido = substr($annotation, $pos + 1);
            $retorno [$nombre][] = trim($contenido);
        }

        return $retorno;
    }

    public function get_descripcion_clase()
    {
        if (isset($this->anotaciones_clase['description'])) {
            $desc = $this->anotaciones_clase['description'][0];
        } else {
            $desc = "[@descripcion de la clase]";
        }

        return $desc;
    }

    public function get_metodos()
    {
        $mis_metodos = array();
        $metodos = $this->reflexion->getMethods();
        foreach ($metodos as $metodo) {
            if (!$this->es_metodo_de_api($metodo)) {
                continue;
            }

            $parametros = array();
            $parameters = $metodo->getParameters();
            foreach ($parameters as $p) {
                $parametros[] = $p->getName();
            }

            $anotaciones = $this->get_annotations_metodo($metodo);

            $nuevo_metodo = array(
                'nombre' => $metodo->getName(),
                'parametros' => $parametros,
                'anotaciones' => $anotaciones,
            );
            $mis_metodos[] = $nuevo_metodo;
        }

        return $mis_metodos;
    }

    /**
     * @param $metodo
     *
     * @return bool
     */
    protected function es_metodo_de_api($metodo)
    {
        $valido = true;
        if (!$metodo->isPublic()) {
            $valido = false;
        }

        $partes_metodo = explode('_', $metodo->getName());
        $prefijo = array_shift($partes_metodo);
        if (!in_array($prefijo, static::$metodos_validos, true)) {
            $valido = false;
        }

        return $valido;
    }

    public function get_parametros_metodo($metodo, $type)
    {
        $api_parameters = array();
        $key = 'param_'.$type;
        $anotaciones = $metodo['anotaciones'];
        if (isset($anotaciones[$key])) {
            $parametros = $anotaciones[$key];
            foreach ($parametros as $parameter) {
                $param = $this->get_parametro_tipo($parameter, $type);
                if ($param) {
                    $api_parameters[] = $param;
                }
            }
        }

        return $api_parameters;
    }

    protected function get_parametro_tipo($parametro, $type)
    {
        $matches = array();
        preg_match('#(\$\w*)\b\s+(\w*)\s*(?:\[(.*?)\]\s+)?(.*)#', $parametro, $matches);

        if (count($matches) <= 3) {
            return array();
        }

        $api_parameter = array();
        $tipo_dato = tipo_datos_docs::get_tipo_datos($matches[2]);
        switch ($type) {
            case 'query':
                $api_parameter['name'] = ltrim($matches[1], '$');
                $api_parameter['in'] = $type;
                $api_parameter['schema'] = $tipo_dato;
                break;
        /*	case 'path':
                $api_parameter['in'] = $type;*/
            case 'body':
                $api_parameter['content'] = array('*/*' => ['schema' => $tipo_dato]);
                break;
        }
        
        $api_parameter['description'] = $matches[4] ?: '[sin descripcion]';
        if (!empty($matches[3])) {
            $modificadores = $matches[3];
            if (preg_match('/required/', $modificadores)) {
                $api_parameter['required'] = true;
            }
        }

        return $api_parameter;
    }

    public function get_summary_metodo($metodo)
    {
        if (isset($metodo['anotaciones']['summary'])) {
            return $metodo['anotaciones']['summary'][0];
        }

        return '';
    }

    public function get_notes_metodo($metodo)
    {
        if (isset($metodo['anotaciones']['notes'])) {
            return $metodo['anotaciones']['notes'][0];
        }

        return '';
    }

    public function get_since_metodo($metodo)
    {
        if (isset($metodo['anotaciones']['since'])) {
            return $metodo['anotaciones']['since'][0];
        }

        return '';
    }
     
    public function get_metodo_deprecado($metodo)
    {
        if (isset($metodo['anotaciones']['deprecated'])) {
            return $metodo['anotaciones']['deprecated'][0];
        }

        return '';
    }
    
    public function get_respuestas_metodo($metodo)
    {
        $respuestas = array();
        if (isset($metodo['anotaciones']['responses'])) {
            foreach ($metodo['anotaciones']['responses'] as $respuesta) {
                $matches = array();
                //200 [array] $tipo descripcion
                $resultado = preg_match("/(\d{3})?\s*([ary]*)\s*(\{[\":\w\$\s]+\})?\s*(.*)/i", $respuesta, $matches);
                if (0 === $resultado || false === $resultado) {
                    continue;
                }

                $status = $matches[1];

                if ($matches[2] == 'array') {
                    $items = tipo_datos_docs::get_tipo_datos($matches[3]);
                    $schema = array(
                        'type' => 'array',
                        'items' => $items,
                    );
                } else {
                    $schema = tipo_datos_docs::get_tipo_datos($matches[3]);
                }
                $mje = $matches[4];

                $resObj = array('description' => $mje);
                if (! empty($schema)) {
                    $resObj['content']['*/*']['schema'] = $schema;
                }

                $respuestas[$status] = $resObj;
            }
        }

        return $respuestas;
    }

    protected function termina_con($needle, $haystack)
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}
