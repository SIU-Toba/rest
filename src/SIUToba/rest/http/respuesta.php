<?php

namespace SIUToba\rest\http;

use SIUToba\rest\lib\rest_error_interno;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response;

/**
 * Abstae la respuesta HTTP. Permite setearle estados, headers
 * y contenido que subclases puede imprimir con otro formato o
 * con los helpers apropiados.
 */
class respuesta extends Response
{
    protected $encoding;
    
    /**
     * @var string Verison de la API
     */
    protected $api_version;

    /**
     * Constructor.
     *
     * @param mixed $data    El cuerpo de la respuesta
     * @param int   $status  El status HTTP
     * @param array $headers Headers
     */
    public function __construct($data = null, $status = 200, $headers = array())
    {
	parent::__construct($status, $headers, $data);		
    }

    public function set_encoding_datos($encoding)
    {
        $this->encoding = $encoding;
    }

    public function get_encoding_datos()
    {
        return $this->encoding;
    }

    public function get_status()
    {
        return $this->getStatusCode();
    }

    public function set_status($status)
    {
	return $this->withStatus($status);
    }

   public function add_headers(array $headers)
    {
	 $new = $this;
	 foreach($headers as $header) {		 
		 $new = $new->withAddedHeader($header[0], $header[1]);		// headerName => valor
	 }
	 return $new;
    }

    public function get_data()
    {
	return $this->getBody();
    }

    public function set_data($content)
    {
	$content = $this->getParaStream($content);
	return $this->withBody(Psr7\stream_for($content));
    }
    
    public function set_api_version($api_version)
    {
        $this->api_version = $api_version;        
        // Agrego la version de la API a los headers
	return $this->withHeader('API-Version' , $this->api_version);
    }

    /**
     * Realiza chequeos sobre el formato de la respuesta antes de enviarla.
     */
    public function finalizar()
    {
	$new = $this;
        if (in_array($this->getStatusCode(), array(204, 304))) {
		$new = $this->withoutHeader('Content-Type')->withoutHeader('Content-Length')->withBody(Psr7\stream_for(''));
        }
        if (!isset($new->stream)) {									//Accede a la variable directamente porque cuando es vacio tiene un stream_for('')
            throw new rest_error_interno("El contenido de la respuesta no puede ser nulo. Si no se desea una respuesta, inicializar
            en '' o arreglo vacio");
        }
	return $new;
    }

    /**
     * Get message for HTTP status code.
     *
     * @param int $status
     *
     * @return string|null
     */
    public static function getMessageForCode($status)
    {
        if (isset(self::$phrases[$status])) {
            return self::$phrases[$status];
        } else {
            return;
        }
    }
	
	/**
	 * Devuelve el parametro de manera compatible para la funcion stream_for
	 * Esto es un Iterador en caso de arreglo o el mismo parametro
	 * @param mixed $valores
	 * @return mixed
	 */
	protected function getParaStream($valores)
	{
		if (is_array($valores)) {
			$valores = new \ArrayIterator($valores);		//Esto genera un pumpStream, hay que ver como hacer para generar un Multipart
		}
		return $valores;
	}
}
