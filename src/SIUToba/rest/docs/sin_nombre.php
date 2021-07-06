<?php

class sin_nombre
{	
	protected $list; 
	protected $api_url;

	function __construct()
	{
		$this->list = array();
	}

	function getResourceList()
    {
        $this->list = $this->getHeader();
        $this->list['paths'] = array();
        $this->list['definitions'] = array();

        $lista_apis = $this->get_lista_apis();
        foreach ($lista_apis as $path) {
            $this->add_modelos($path);
            $this->add_apis($path);
        }

        $this->reordenar_lista_apis($list['paths']);
        return $this->list;
    }

	protected function getHeader()
	{
		$list = array();
		$list['swagger'] = "2.0";
        $list['info'] = array('title' => 'API Title', 'version' => '1.0');                               //TODO: Read from settings
        $list['basePath'] = $this->api_url;
        $list['produces'] = array("application/json");
        return $list;
	}

    protected function get_lista_apis()
    {
        $list = array();
        $prefijo = rest::app()->config('prefijo_controladores');
        foreach ($this->api_root as $root) {
            $path = realpath($root);
            if ($path === false) {
                continue;
            }
            $archivos_api = $this->obtener_clases_directorio($path);
            foreach ($archivos_api as $nombre => $objeto) {
                if ('php' !== pathinfo($nombre, PATHINFO_EXTENSION)) {
                    continue;
                }

                if (!$this->empieza_con($prefijo, pathinfo($nombre, PATHINFO_BASENAME))) {
                    continue;
                }

                $nombre = str_replace('\\', '/', $nombre); // windows! ...
                $path = $this->get_url_de_clase($root, $nombre);
                $path = ltrim($path, '/');

                $list[] = $path;
            }
        }
        return $list;
    }


    protected function add_apis($path)
    {

        /** @var $reflexion anotaciones_docs */
        $reflexion = $this->get_annotaciones_de_path($path);
        $metodos = $reflexion->get_metodos();

        $montaje = $this->get_montaje_de_path($path);
        $prefijo_montaje = $montaje ? '/' . $montaje : '';

        foreach ($metodos as $metodo) {
            $parametros = $metodo['parametros'];
            $nombre_metodo = $metodo['nombre'];

            $alias = '';
            $partes_nombre_alias = explode('__', $nombre_metodo);
            if (count($partes_nombre_alias) > 1) {
                $alias = $partes_nombre_alias[1];
                $nombre_metodo = $partes_nombre_alias[0];
            }

            $partes_nombre = explode('_', $nombre_metodo);
            $prefijo_metodo = array_shift($partes_nombre);
            if ($es_coleccion = $this->termina_con(ruteador::SUFIJO_COLECCION, $nombre_metodo)) {
                array_pop($partes_nombre); //SUFIJO_COLECCION
            }

            /////------------PARAMETERS ---------------------------------
            $params_path = array();
            $partes_path = explode('/', $path);

            if ($montaje) {
                array_shift($partes_path);
            }

            foreach ($partes_nombre as $parte) {
                $partes_path[] = $parte;
            }

            $nro_parametro = 0;
            $api_path = $prefijo_montaje; // $path;

            foreach ($partes_path as $parte) {
                $parte = str_replace('_', '-', $parte); //no permito '_' en las colecciones
                $api_path .= "/" . $parte;
                if (isset($parametros[$nro_parametro])) {
                    $param_name = $parametros[$nro_parametro++];
                    $api_path .= "/{" . $param_name . "}";
                    $params_path[] = $this->get_parametro_path($param_name, $parte);
                }
            }
            if ($alias) {
                $api_path .= '/' . $alias;
            }
            ////--------------------------------------------------------
            $params_query = $reflexion->get_parametros_metodo($metodo, 'query');
            $params_body = $reflexion->get_parametros_metodo($metodo, 'body');
            if (! empty($params_body)) {                                        //Agrego los schemas para los tipos locales
                $params_body = $this->add_tipos_en_modelo($params_body);
            }

            $operation = array();
            $operation['tags'] = array(str_replace('_', '-', $path)); //cambio el _ para mostrarlo
            $operation['method'] = strtolower($prefijo_metodo);
            $operation['summary'] = $reflexion->get_summary_metodo($metodo);
            $operation['description'] = $reflexion->get_notes_metodo($metodo);

            $operation['operationId'] = $nombre_metodo;
            $operation['parameters'] = array_merge($params_path, $params_body, $params_query);

            $operation['responses'] = $reflexion->get_respuestas_metodo($metodo);

            $this->list['paths'][$api_path][$operation['method']] = $operation;
        }
    }

    protected function get_parametro_path($param_name, $parte)
    {
        $api_parameter = array();
        $api_parameter['name'] = $param_name;
        $api_parameter['in'] = "path";
        $api_parameter['description'] = "ID del recurso $parte";
        $api_parameter['type'] = "string";
        $api_parameter['required'] = true;

        return $api_parameter;
    }

    /**
     * @param $path
     *
     * @return anotaciones_docs
     */
    protected function get_annotaciones_de_path($path)
    {
        $lector = rest::app()->lector_recursos; //new lector_recursos_archivo($this->api_root);
        $archivo = $lector->get_recurso(explode('/', $path));

        return new anotaciones_docs($archivo['archivo']);
    }


    /**
     * @param $path
     *
     * @return anotaciones_docs
     */
    protected function add_modelos($path)
    {
        $lector = rest::app()->lector_recursos; //new lector_recursos_archivo($this->api_root);
        $archivo = $lector->get_recurso(explode('/', $path));

        $i = new rest_instanciador();
        $i->archivo = $archivo['archivo'];
        $objeto = $i->get_instancia();

        if (method_exists($objeto, '_get_modelos')) {
            $modelo = new modelo_recursos();
            $specs = $modelo->to_swagger($objeto->_get_modelos());
            $this->list['definitions'] = array_merge($this->list['definitions'], $specs);
        } else {
            rest::app()->logger->debug('El objeto no tiene el metodo _get_modelos. Clase: ' . get_class($objeto));

            return array();
        }
    }

    /**
    * @param $params List of body params
    * @return array  List of body params with schema definitions
    */
    protected function add_tipos_en_modelo($params)  
    {
        $non_predefined_types = array_keys($this->list['definitions']);
        $param_keys = array_keys($params);
        foreach($param_keys as $key)  {
            if (isset($params[$key]['type']) && in_array($params[$key]['type'], $non_predefined_types)) {
                $params[$key]['schema'] = array('$ref' => "#/definitions/". trim($params[$key]['type']));
            }
        }
            
        return $params;
    }

}
?>