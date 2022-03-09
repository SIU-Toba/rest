<?php

namespace SIUToba\rest\http;

use SIUToba\rest\lib\rest_error;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\Uri;

/**
 * Clase basada en Slim - a micro PHP 5 framework para abstraer el Request.
 */
class request extends ServerRequest
{
    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_OVERRIDE = '_METHOD';

    /**
     * Special-case HTTP headers that are otherwise unidentifiable as HTTP headers.
     * Typically, HTTP headers in the $_SERVER array will be prefixed with
     * `HTTP_` or `X_`. These are not so we list them here for later reference.
     *
     * @var array
     */
    /*protected static $special = array(
        'CONTENT_TYPE',
        'CONTENT_LENGTH',
        'PHP_AUTH_USER',
        'PHP_AUTH_PW',
        'PHP_AUTH_DIGEST',
        'AUTH_TYPE',
    );
*/
    protected $union; //get + post
    protected $encoding;

	protected $behind_proxy;
    
    private $request_uri;
    private $host;
    private $port;
    private $protocol;
	
    public function __construct($metodo, $uri, $headers=[], $body = null, $protocolo='1.1', $serverGlobal=[])
    {
		parent::__construct($metodo, $uri, $headers, $body, $protocolo, $serverGlobal);
    
        /*$this->headers = $this->extract_headers();
        $this->behind_proxy = $behind_proxy;*/
    }

    public static function fromGlobals()
    {
		$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
		$headers = getallheaders();
		$uri = ServerRequest::getUriFromGlobals();
		$body = new CachingStream(new LazyOpenStream('php://input', 'r+'));
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';
		$serverRequest = new request($method, $uri, $headers, $body, $protocol, $_SERVER);

		return $serverRequest
			->withCookieParams($_COOKIE)
			->withQueryParams($_GET)
			->withParsedBody($_POST)
			->withUploadedFiles(ServerRequest::normalizeFiles($_FILES));
    }

    public function set_encoding_datos($encoding)
    {
        $this->encoding = $encoding;
    }

    public function get_encoding_datos()
    {
        return $this->encoding;
    }

    public function get_method()
    {
		return $this->getMethod();
    }

    /**
     * Obtiene parametros del $_GET o $_POST unidos.
     *
     * Si key es nulo devuelve todos. Sino devuelve el parametro key si existe o su default
     */
    public function params($key = null, $default = null)
    {
        if (!$this->union) {
            $this->union = array_merge($this->get(), $this->post());
        }

        return $this->get_valor_o_default($this->union, $key, $default);
    }

    /**
     * Devuelve parametros del _GET.
     *
     * Si key es nulo devuelve todos. Sino devuelve el parametro key si existe o su default
     */
    public function get($key = null, $default = null)
    {
        return $this->get_valor_o_default($this->getQueryParams(), $key, $default);
    }

    /**
     * Devuelve parametros del _POST - Solo se setea para formularios html.
     *
     * Si key es nulo devuelve todos. Sino devuelve el parametro key si existe o su default
     */
    public function post($key = null, $default = null)
    {
        $datos = $this->get_valor_o_default($this->getParsedBody(), $key, $default);
        return $this->manejar_encoding($datos);
    }

    /**
     * Devuelve parametros del POST en formato json como un arreglo.
     */
    public function get_body_json()
    {
		$body = $this->getParsedBody();
		if (is_null($body) || ! is_array($body)) {
		 throw new rest_error(400, "No se pudo decodificar el mensaje");
		}

		$arreglo = $this->manejar_encoding($body);	
		return $arreglo;
    }

    /**
     * Devuelve los headers.
     *
     * Si key es nulo devuelve todos. Sino devuelve el parametro key si existe o su default
     */
    public function headers($key = null, $default = null)
    {		
		return ($this->hasHeader($key))  ? $this->getHeader($key): $default;	
    }

    /**
     * Retorna el body en crudo - Usar cuando no aplica el $_POST get_post().
     *
     * @return string
     */
    public function get_body()
    {
		return $this->getBody();
    }

    /**
     * Get Host.
     *
     * @return string
     */
    public function get_host()
    {
		return $this->headers('host');        
    }

    public function set_host($host)
    {
        if ($this->behind_proxy) {
            $this->host = $host;
        }
    }
    
    /**
     * Get Port.
     *
     * @return int
     */
    public function get_puerto()
    {
		return $this->getUri()->getPort();
        if (! isset($this->port)) {
            $this->port = (int) $_SERVER['SERVER_PORT'];
        }
        return $this->port;
    }

    public function set_puerto($port)
    {
        if ($this->behind_proxy) {
            $this->port = $port;
        }
    }
    
    /**
     * Devuelve el esquema (https or http).
     *
     * @return string
     */
    public function get_esquema()
    {
        return $this->getUri()->getScheme();
        if (!isset($this->protocol)) {
            $this->protocol = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') ? 'http' : 'https';
        }
        return $this->protocol;
    }

    public function set_esquema($proto)
    {
        if ($this->behind_proxy) {
            $this->protocol = $proto;
        }
    }
    
    public function get_request_uri()
    {
		return $this->getUri()->getPath();
		return $this->getUri()->__toString();
        if (! isset($this->request_uri)) {
            $this->request_uri = $_SERVER["REQUEST_URI"];
        }
        return $this->request_uri;
    }

    public function set_request_uri($uri)
    {
        if ($this->behind_proxy) {
            $this->request_uri = $uri;
        }
    }
    
    /**
    *  URL (schema + host [ + port si no es 80 ]).
    *
    * @return string
    */
    public function get_url()
    {
		$uri = $this->getUri();
		return Uri::composeComponents($uri->getScheme(), $uri->getAuthority(), $uri->getPath(), '', '');
    }

    protected function get_valor_o_default($arreglo, $key = null, $default = null)
    {
        if ($key) {
            if (isset($arreglo[$key])) {
                return $arreglo[$key];
            } else {
                return $default;
            }
        } else {
            return $arreglo;
        }
    }

    protected function manejar_encoding($datos)
    {
        if ($this->encoding !== 'utf-8') {
            $datos = $this->utf8_decode_fields($datos);
        }

        return $datos;
    }

    protected function utf8_decode_fields($entrada)
    {
        if (is_array($entrada)) {
            $salida = array();
            foreach ($entrada as $clave => $valor) {
                $salida[$clave] = $this->utf8_decode_fields($valor);
            }

            return $salida;
        } elseif (is_string($entrada)) {
            return \utf8_d_seguro($entrada);
        } else {
            return $entrada;
        }
    }
}
