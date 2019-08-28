<?php

namespace SIUToba\rest;

use Exception;
use SIUToba\rest\docs\controlador_docs;
use SIUToba\rest\http\request;
use SIUToba\rest\http\respuesta_rest;
use SIUToba\rest\http\vista_json;
//use SIUToba\rest\http\vista_respuesta;
use SIUToba\rest\http\vista_xml;
use SIUToba\rest\lib\lector_recursos_archivo;
//use SIUToba\rest\lib\logger;
use SIUToba\rest\lib\logger_vacio;
use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_error_interno;
use SIUToba\rest\lib\rest_instanciador;
use SIUToba\rest\lib\ruteador;
use SIUToba\rest\lib\Set;
use SIUToba\rest\seguridad\autenticacion\rest_error_autenticacion;
use SIUToba\rest\seguridad\autorizacion\autorizacion_anonima;
use SIUToba\rest\seguridad\autorizacion\rest_error_autorizacion;
use SIUToba\rest\seguridad\firewall;
//use SIUToba\rest\seguridad\proveedor_autorizacion;
use SIUToba\rest\seguridad\rest_usuario;

/**
 * @property lector_recursos_archivo lector_recursos
 * @property request request
 * @property respuesta_rest $response
 * @property vista_respuesta vista
 * @property ruteador router
 * @property logger logger
 * @property firewall firewall
 * @property mixed settings
 * @property rest_usuario usuario
 * @property proveedor_autorizacion autorizador
 */
class rest
{
    protected static $instancia;

    /**
     * @var \SIUToba\rest\lib\Set
     */
    public $container;

    /**
     * @return rest
     */
    public static function app()
    {
        return self::$instancia;
    }

    /**
     * @return request
     */
    public static function request()
    {
        return self::$instancia->request;
    }

    /**
     * @return respuesta_rest
     */
    public static function response()
    {
        return self::$instancia->response;
    }

    /**
     * Si el usuario es null, es acceso anonimo.
     *
     * @return rest_usuario
     */
    public static function usuario()
    {
        return self::$instancia->usuario;
    }

    /**
     * Settings default - Se pueden cambiar en el constructor.
     *
     * @return array
     */
    public static function get_default_settings()
    {
        return array(
            'formato_respuesta' => 'json',
            'encoding' => 'utf-8', //latin1
            'path_controladores' => '/',
            'prefijo_controladores' => 'recurso_',
            'url_api' => '/api',
            'prefijo_api_docs' => 'api-docs',
            'url_protegida' => '/.*/',

            //DEBUG
            'debug' => false,
            // HTTP
            'http.version' => '1.1',
            // API version
            'api_version' => '1.0.0',
            'api_titulo' => 'Api Reference',
        );
    }

    public function __construct($settings = array())
    {
        self::$instancia = $this;

        $this->container = new Set();
        $this->container['settings'] = array_merge(static::get_default_settings(), $settings);

        // Request default
        $this->container->singleton('request', function ($c) {
            $req = request::fromGlobals();
            $req->set_encoding_datos($c['settings']['encoding']);

            return $req;
        });

        // Respuesta default
        $this->container->singleton('response', function ($c) {
	   $respuesta = new respuesta_rest();
           $respuesta = $respuesta->set_encoding_datos($c['settings']['encoding'])->set_api_version($c['settings']['api_version']);

            return $respuesta;
        });

        // Ruteador default
        $this->container->singleton('router', function ($c) {
            $r = new ruteador($c->lector_recursos, new rest_instanciador());

            return $r;
        });

        // Proveedor de autenticacion --> SE DEBE INDICAR UNO EXTERNAMEN
        $this->container->singleton('autenticador', function ($c) {
            throw new rest_error_interno("Se debe indicar un autenticador que provea los usuarios del negocio");
        });

        // Proveedor de autorizacion
        $this->container->singleton('autorizador', function ($c) {
            $autorizador = new autorizacion_anonima();

            return $autorizador;
        });

        // Firewall default
        $this->container->singleton('firewall', function ($c) {
            $autorizador = new firewall($c->autenticador, $c->autorizador, $c->settings['url_protegida']);

            return $autorizador;
        });

        // Logger
        $this->container->singleton('logger', function ($c) {
            return new logger_vacio();
        });

        $this->container->singleton('lector_recursos', function ($c) {
            return new lector_recursos_archivo(
                $c['settings']['path_controladores'],
                $c['settings']['prefijo_controladores']);
        });

        $this->container->singleton('controlador_documentacion', function ($c) {
			$url = $c['request']->get_url() . $c['settings']['url_api'];
            return new controlador_docs(
                $c['settings']['path_controladores'],
				$url               
            );
        });

        // Vistas default
        $this->container->singleton('vista', function ($c) {
            $formato = $c['settings']['formato_respuesta'];
            $respuesta = $c['response'];
            switch ($formato) {
                case 'json':
                    return new vista_json($respuesta);
                case 'xml':
                    return new vista_xml($respuesta);
            }
        });
    }

    public function set_autenticador($autenticador)
    {
        $this->autenticador = $autenticador;
    }

    public function set_autorizador($autorizador)
    {
        $this->autorizador = $autorizador;
    }

    public function set_lector_recursos($lector)
    {
        $this->lector_recursos = $lector;
    }

    public function set_logger($logger)
    {
        $this->logger = $logger;
    }

    public function set_quoter($quoter)
    {
        $this->rest_quoter = $quoter;
    }

    public function set_router($router)
    {
        $this->router = $router;
    }

    public function set_response($response)
    {
        $this->response = $response;
    }

    public function set_request($request)
    {
        $this->request = $request;
    }

    public function set_vista($vista)
    {
        $this->vista = $vista;
    }

    public function procesar()
    {
        $this->logger->debug("Iniciando el pedido");
        try {
            $method = $this->request->get_method();
            $respuesta = $this->response;       //Fuerzo instanciacion de la respuesta ya que un singleton no sirve mas
            $this->set_response($respuesta->withStatus(200));

            $url = $this->get_url_relativa();
            $url = ltrim($url, '/');
            $this->logger->debug("Procesando URL '/$url'");

            $this->controlar_acceso($url);
            $partes_url = explode('/', $url);
            if ($partes_url[0] == $this->container['settings']['prefijo_api_docs']) {
                $this->mostrar_documentacion($url);
            } else {
                $recurso = $this->router->buscar_controlador($method, $url);
                $this->logger->debug("Controlador encontrado {$recurso->archivo} :: {$recurso->accion} (".implode(',', $recurso->parametros).")");
                $recurso->ejecutar_accion();
            }
        } catch (rest_error_autenticacion $ex) {
            $ex->configurar_respuesta($respuesta);
            $this->logger->info("Excepcion de Autenticacion. Autenticar y reintentar");
            $this->logger->info(var_export($respuesta, true));
            $this->logger->info($ex->getMessage());
        } catch (rest_error_autorizacion $ex) {
            $ex->configurar_respuesta($respuesta);
            $this->logger->info("Error de Autorizacion.");
        } catch (rest_error $ex) {
            // Excepciones controladas, partel del flujo normal de la API
            $ex->configurar_respuesta($respuesta);
            $this->logger->info("La api retornó un error. Status: ".$respuesta->get_status());
            $this->logger->info(var_export($respuesta->get_data(), true));
        } catch (Exception $ex) {
            // Excepcion del codigo del proyecto - Error de programación, no tiene que entrar aca en el flujo normal
            $this->logger->error("Error al ejecutar el pedido. ".$ex->getMessage());
            $this->logger->error($ex->getTraceAsString());
            $error = new rest_error(500, "Error Interno en el servidor: ".$ex->getMessage());
            $error->configurar_respuesta($respuesta);
        }
        $this->response->finalizar();
        $this->vista->escribir();
        $this->logger->debug("Pedido finalizado");
        if ($this->config('debug')) {
            $this->logger->debug(var_export($respuesta, true));
        }
        if (method_exists($this->logger, 'guardar')) { // es el logger de toba
            $this->logger->guardar();
        }
    }

    /**
     * @param $ruta
     *
     * @throws rest_error_autorizacion si el firewall denega el acceso
     */
    protected function controlar_acceso($ruta)
    {
        $this->logger->debug("Iniciando Autenticacion");
        if ($this->firewall->maneja_ruta($ruta)) {
            $this->logger->debug("Pedido capturado por el firewall");
            $usuario = $this->firewall->manejar($ruta, $this->request);
            $this->loggear_acceso_ok($usuario);
            $this->usuario = $usuario;
        } else {
            $this->logger->info("El firwall no controla acceso a $ruta");
        }
    }

    private function get_url_relativa()
    {
        $uri = $this->request->get_request_uri();
        $url = strtok($uri, '?');
        $url_api = $this->container['settings']['url_api'];

       /* $resultado = GuzzleHttp\Psr7\UriResolver::relativize(new GuzzleHttp\Psr7\Uri($url_api), new GuzzleHttp\Psr7\Uri($uri));
        return $resultado->__toString();*/

        if (substr($url, 0, strlen($url_api)) == $url_api) {
            return substr($url, strlen($url_api));
        }
        throw new rest_error_interno("Este controlador no está configurado para manejar esta URL. La url es: '$uri', la url de la API es '$url_api'");
    }

    public function config($clave)
    {
        return $this->container['settings'][$clave];
    }

    public function __get($name)
    {
        return $this->container[$name];
    }

    public function __set($name, $value)
    {
        $this->container[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->container[$name]);
    }

    public function __unset($name)
    {
        unset($this->container[$name]);
    }

    /**
     * @param $usuario
     */
    protected function loggear_acceso_ok($usuario)
    {
        if ($usuario != null) {
            $this->logger->debug("Usuario '{$usuario->get_usuario()}' autenticado y autorizado");
        } else {
            $this->logger->debug("Usuario autorizado anonimamente");
        }
    }

    /**
     * @param $url
     */
    protected function mostrar_documentacion($url)
    {
        $config = [
            'titulo' => $this->container['settings']['api_titulo'],
            'version' => $this->container['settings']['api_version'],
            ];
        if (isset($this->container['settings']['logo'])) {
            $config['url_logo'] = $this->container['settings']['logo'];
        }

        $this->logger->debug("Iniciando documentacion");
        $controlador = $this->controlador_documentacion;
        $controlador->set_config($config);
        $url = strstr($url, '/');
        $this->set_response($controlador->get_documentacion($url));
    }

    /**
     * Agrega una ruta donde la librería Rest busca recursos.
     *
     * @param string $path ruta a un directorio con recursos
     */
    public function add_path_controlador($path)
    {
        $this->lector_recursos->add_directorio_recursos($path);

        $this->controlador_documentacion->add_punto_partida($path);
    }
}
