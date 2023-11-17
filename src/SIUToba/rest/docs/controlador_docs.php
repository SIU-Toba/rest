<?php

namespace SIUToba\rest\docs;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SIUToba\rest\lib\modelo_recursos;
use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_instanciador;
use SIUToba\rest\lib\ruteador;
use SIUToba\rest\rest;

/**
 * @TODO: refactorizar esto - no es critico, pero se fue extendiendo, y no tiene tests (ups)
 *
 */
class controlador_docs
{
    protected $api_root;
    protected $api_url;

    protected $list;
    protected $settings = array('titulo' => 'Api Title', 'api_version' => '1.0');

    public function __construct($api_root, $api_url)
    {
        $this->api_root = (! is_array($api_root)) ? array($api_root) : $api_root;
        $this->api_url = $api_url;
    }

    /**
    * Permite agregar un nuevo punto de partida para la generación de la documentación.
    * Atencion!, requiere que el localizador sea capaz de encontrar los recursos.
    * @param $path string
    */
    public function add_punto_partida($path)
    {
        if (! is_array($path) && ! in_array($path, $this->api_root, true)) {
            $this->api_root[] = $path;
        }
    }

    /**
     * Permite fijar las opciones para la generacion de documentacion
     *
     * @param array $settings Array asociativo conteniendo las opciones
     * ['titulo' => '', 'version' => '', 'url_logo' => '',..]
     */
    public function set_config($settings=array())
    {
        $this->settings = \array_merge($this->settings, $settings);
    }

    /**
     * Retorna la documentacion en formato swagger para el path. Si el path
     * es nulo retorna la delcaracion de recursos, sino retorna la api para el path.
     */
    public function get_documentacion($path)
    {
        if (empty($path)) {
            $lista = $this->getResourceList();
            return rest::response()->get($lista);
        } else {
            throw new rest_error("En esta versión toda la documentación esta en la raiz");
        }
    }

    protected function getResourceList()
    {
        $resultado = $this->getHeader();
        $lista_apis = $this->get_lista_apis();

        $schemas = [];
        foreach ($lista_apis as $path) {                //Defino los tipos propios que pudieran definirse
            $schemas = array_merge($schemas, $this->add_modelos($path));
        }

        $apis = [];
        $tipos_propios = array_keys($schemas);          //Agrego los paths de las apis
        foreach ($lista_apis as $path) {
            $apis = array_merge($apis, $this->add_apis($path, $tipos_propios));
        }

        //Agregos los schemas de los tipos propios y reordeno la lista de apis
        $resultado['paths'] = $this->reordenar_lista_apis($apis);
        $resultado['components']['schemas'] = $schemas;
        return $resultado;
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
            $archivos_api = $this->obtener_clases_directorio($path);            //Devuelve iterador
            foreach ($archivos_api as $nombre => $objeto) {
                if ('php' !== pathinfo($nombre, PATHINFO_EXTENSION)) {
                    continue;
                }

                if (!$this->empieza_con($prefijo, pathinfo($nombre, PATHINFO_BASENAME))) {
                    continue;
                }
                $nombre = str_replace('\\', '/', $nombre); // windows! ...
                $path = $this->get_url_de_clase($root, $nombre);
                $list[] = ltrim($path, '/');
            }
        }
        return $list;
    }

    protected function add_apis($path, $tipos_propios)
    {
        $exploded_path = explode('/', $path);

        /** @var $reflexion anotaciones_docs */
        $reflexion = $this->get_annotaciones_de_path($exploded_path);
        $metodos = $reflexion->get_metodos();
        $nombre_clase = str_replace('\\', '/', $reflexion->get_nombre_clase());

        $montaje = $this->get_montaje_de_path($exploded_path);
        $prefijo_montaje = $montaje ? '/' . $montaje : '';
        if ($montaje != '') {
            array_shift($exploded_path);
        }

        $doc_api = [];
        foreach ($metodos as $metodo) {
            //Separo el nombre del metodo del alias que pueda tener
            list($alias, $nombre_metodo) = $this->separar_alias_nombre($metodo['nombre']);
            $es_coleccion = $this->termina_con(ruteador::SUFIJO_COLECCION, $nombre_metodo);

            //Vuelvo a separar el nombre sin alias para obtener el metodo que escucha
            list($prefijo_metodo, $partes_nombre) = $this->separar_prefijo_nombre($nombre_metodo, $es_coleccion);
            
            //Obtengo los query parameters
            $params_query = $reflexion->get_parametros_metodo($metodo, 'query');
            
            //Parametros del metodo pero con con mayor detalle
            $partes_path = array_merge($exploded_path, $partes_nombre);
            list($api_path, $params_path) = $this->get_parametros_path($prefijo_montaje, $metodo['parametros'], $partes_path);
            if ($alias != '') {
                $api_path = $api_path . '/' . $alias;
            }
            
            //Obtengo los param body
            $params_body = $reflexion->get_parametros_metodo($metodo, 'body');
            if (! empty($params_body)) {                                        //Agrego los schemas para los tipos locales
                $params_body = $this->add_tipos_en_modelo($params_body, $tipos_propios);
                $operation['requestBody'] = $params_body;
            }
            
            $operation['operationId'] = "$nombre_clase:{$metodo['nombre']}";
            $operation['parameters'] = array_merge($params_path, $params_query);
            
            //Reuno todo para crear la info de la operacion
            $method = strtolower($prefijo_metodo);
            $doc_api[$api_path][$method] = $this->get_operacion(
                $operation['operationId'],
                $reflexion->get_summary_metodo($metodo),
                $reflexion->get_notes_metodo($metodo),
                array(str_replace('_', '-', $path)),
                $operation['parameters'],
                $params_body,
                $reflexion->get_respuestas_metodo($metodo),
                $reflexion->get_since_metodo($metodo),
                $reflexion->get_metodo_deprecado($metodo)
            );
        }
        return $doc_api;
    }

    protected function reordenar_lista_apis($apis_paths)
    {
        $orden_apis = array();
        foreach ($apis_paths as $api_path => $api) {
            $orden_apis[] = $api_path;
            $apis_paths[$api_path] = $this->reordenar_operaciones($api);
        }

        $api_backup = $apis_paths;
        if (false === \array_multisort($orden_apis, \SORT_ASC, $apis_paths)) {
            $apis_paths = $api_backup;
        }
        return $apis_paths;
    }

    /**
     * Reordena los distintas operaciones GET PUT DELETE UPDATE.
     *
     * @param $paths
     *
     * @return array
     */
    protected function reordenar_operaciones($paths)
    {
        $orden_ops = array();
        $path_originales = $paths;
        foreach ($paths as $metodo => $detalle) {
            //3GET,3PUT,6DELETE,6UPDATE
            $orden_ops[] = strlen($metodo) . $metodo;
        }
        if (false === array_multisort($orden_ops, \SORT_ASC, $paths)) {
            $paths = $path_originales;
        }
        return $paths;
    }

    /**
     * Retorna la url del recurso REST en base a la ruta del archivo.
     * Se aceptan los formatos algo/recurso/recurso.php o /algo/recurso.php
     * Para ambos, la ruta es /algo/recurso.
     *
     * @param $ruta_absoluta
     *
     * @return string
     */
    protected function get_url_de_clase($api_root, $ruta_absoluta)
    {
        $url = '';
        $name = basename($api_root);
        $prefijo = rest::app()->config('prefijo_controladores');
        $partes = preg_split("#/$name/#", $ruta_absoluta);
        if (false !== $partes) {
            $path_relativo = $partes[1];
            $clase_recurso = basename($path_relativo, '.php'); //recurso_padre
            $recurso = substr($clase_recurso, strlen($prefijo)); //padre
            if ($this->termina_con($recurso, dirname($path_relativo))) {
                // /rest/padre/hijo/recurso_hijo.php  => /padre/hijo
                $url = substr($path_relativo, 0, -strlen($clase_recurso . '.php') - 1);
            } else {
                // /rest/padre/recurso_hijo.php => /padre/hijo
                $url = substr($path_relativo, 0, -strlen($clase_recurso . '.php'));
                $url .= $recurso;
            }
        }
        return $url;
    }

    /**
     * @param $path
     *
     * @return anotaciones_docs
     */
    protected function get_annotaciones_de_path($partes_path)
    {
        $lector = rest::app()->lector_recursos;
        $archivo = $lector->get_recurso($partes_path);

        return new anotaciones_docs($archivo['archivo']);
    }

    protected function get_montaje_de_path($partes_url)
    {
        $lector = rest::app()->lector_recursos;
        $montaje = '';
        if (false !== $partes_url) {
            $montaje = current($partes_url);
        }
        return ($lector->es_montaje($montaje)) ? $montaje : '';
    }

    /**
     * @param $path
     *
     * @return anotaciones_docs
     */
    protected function add_modelos($path)
    {
        $lector = rest::app()->lector_recursos;
        $archivo = $lector->get_recurso(explode('/', $path));

        $i = new rest_instanciador();
        $i->archivo = $archivo['archivo'];
        $objeto = $i->get_instancia();

        $specs = array();
        if (method_exists($objeto, '_get_modelos')) {
            $modelo = new modelo_recursos();
            $specs = $modelo->getSchemas($objeto->_get_modelos());
        } else {
            rest::app()->logger->debug('El objeto no tiene el metodo _get_modelos. Clase: ' . get_class($objeto));
        }
        return $specs;
    }

    /**
    * @param $params List of body params
    * @return array  List of body params with schema definitions
    */
    protected function add_tipos_en_modelo($params, $non_predefined_types)
    {
        $param_keys = array_keys($params);
        foreach ($param_keys as $key) {
            if (isset($params[$key]['content'])) {
                foreach ($params[$key]['content'] as $keycont => $contenido) {
                    if (in_array($contenido['schema']['type'], $non_predefined_types, true)) {
                        $type = array('$ref' => "#/components/schemas/". trim($contenido['schema']['type']));
                        $params[$key]['content'][$keycont]['schema']= $type;
                    }
                }
            }
        }

        return $params;
    }

    protected function getHeader()
    {
        $list = array();
        $list['openapi'] = "3.0.0";
        $list['info'] = array('title' => $this->settings['titulo'],
                              'description' => 'Documentación de la API',
                              'version' => $this->settings['version']);

        $list['servers'] = array([ "url" => rtrim($this->api_url, '/')]);
        $list = $this->add_extension_logo($list);
        return $list;
    }

    protected function get_operacion($opId, $resumen, $descripcion, $tags, $parametros, $body, $respuestas, $since, $deprecado)
    {
        $data = array(
                "tags" => $tags,
                "description" => $descripcion,
                //"externalDocs" => [],
                "operationId" => $opId,
                //"callbacks" => [],
                //"deprecated" => false,
                //"security" => [],
                //"servers" => []
        );
        
        if (! empty($resumen)) {
            $data["summary"] = $resumen;
        }
        if (! empty($parametros)) {
            $data["parameters"] = $parametros;
        }
        if (! empty($body)) {
            $data["requestBody"] = current($body);
        }
        if (! empty($deprecado) && $deprecado != '') {
            $data["deprecated"] = $deprecado;
        }
        if (! empty($since) && '' != $since) {
            //$data["x-since"] = $since;
        }
        $data["responses"] = $respuestas;
        
        return $data;
    }

    protected function get_parametro($nombre, $parte, $requerido=true)
    {
        $data = array(
            'name' => $nombre,
            'in' => 'path',
            'description' => "ID del recurso $parte",
            'required' => $requerido,
            'schema' => array('type' => 'string')
//			'schema' => array(
//						'deprecated' => false,
//						'allowEmptyValue' => ! $requerido,
//						'explode' => false,
//						'allowReserved' => false,
//					  //  'style' => []       //Aca iria type¿?
//					)
        );

        return $data;
    }

    protected function get_body()
    {
        $data = array(
            "requestBody" => [
                    "descripcion" => "",
                    "content" => [],
                    "required" => "boolean"
                ]
        );
        return $data;
    }

    protected function get_response($code, $headers, $descripcion, $default=false)
    {
        $codigo = ($default) ? 'default' : $code;
        $data = array(
             $codigo => [
                        "description" => $descripcion,
                        "headers" => [$headers],
                        "content"=> []/*,
                        "links"=> [
                            "operationId" => "",
                            "parameters" => [],
                            "requestBody" => "expression",
                            "description" => "",
                            "server" => []
                        ]*/
                    ]
        );
        return $data;
    }

    /**
     * @param $path
     *
     * @return RecursiveIteratorIterator
     */
    protected function obtener_clases_directorio($path)
    {
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
        return $objects;
    }

    protected function termina_con($needle, $haystack)
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }

    protected function empieza_con($prefijo, $string)
    {
        return substr($string, 0, strlen($prefijo)) === $prefijo;
    }

    protected function separar_alias_nombre($nombre)
    {
        $alias = '';
        //Separo el nombre del metodo del alias que pueda tener
        $partes_nombre_alias = explode('__', $nombre);
        if (count($partes_nombre_alias) > 1) {
            $nombre = $partes_nombre_alias[0];
            $alias = $partes_nombre_alias[1];
        }

        return [$alias, $nombre];
    }

    protected function separar_prefijo_nombre($nombre, $es_coleccion)
    {
        $partes_nombre = explode('_', $nombre);
        $prefijo_metodo = array_shift($partes_nombre);
        if ($es_coleccion) {
            array_pop($partes_nombre); //QUITA SUFIJO_COLECCION
        }
        return [$prefijo_metodo, $partes_nombre];
    }

    protected function get_parametros_path($api_path, $parametros, $partes_path)
    {
        $nro_parametro = 0;
        $params_path = array();
        foreach ($partes_path as $parte) {
            $parte = str_replace('_', '-', $parte); //no permito '_' en las colecciones
            $api_path .= "/" . $parte;
            if (isset($parametros[$nro_parametro])) {
                $param_name = $parametros[$nro_parametro++];
                $api_path .= "/{" . $param_name . "}";
                $params_path[] = $this->get_parametro($param_name, $parte); //Hay que agregar el required
            }
        }
        return [$api_path, $params_path];
    }
    
    protected function add_extension_logo($list)
    {
        //Agrega el logo si esta presente
        if (isset($this->settings['url_logo'])) {
            $valid =  (false !== filter_var($this->settings['url_logo'], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED));
            if ($valid) {
                $list['info']['x-logo'] = array('url' => $this->settings['api_logo']);
            }
        }
        return $list;
    }
}
