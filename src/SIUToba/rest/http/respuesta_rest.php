<?php

namespace SIUToba\rest\http;
use GuzzleHttp\Psr7;
use SIUToba\rest\lib\rest_error;

/**
 * Configuraciones comunes de respuestas para REST.
 */
class respuesta_rest extends respuesta
{
    protected static $not_found_message = 'No se pudo encontrar el recurso en el servidor';

    /**
     * GET de un recurso - Devuelve 200 si es existoso.
     * Si es falso retorna un error 404 Not Found.
     *
     * @param mixed $data Array si es exitoso, o false en caso de que no exista el recurso
     *
     * @throws \SIUToba\rest\lib\rest_error
     *
     * @return $this
     */
    public function get($data)
    {
	if ($data === false) {	
		$this->not_found();
	}
	return $this->get_list($data);
    }

    /**
     * GET a una lista - A diferencia del get(), siempre es exitoso, ya que una lista vacia es valida.
     */
    public function get_list($data)
    {
	$data = $this->getParaStream($data);
	return  $this->withStatus(200)->withBody(Psr7\stream_for($data));	
    }

    /**
     * POST a la lista. Data contiene un arreglo con el identificador del nuevo recurso.
     */
    public function post($data)
    {
	$data = $this->getParaStream($data);
	return  $this->withStatus(201)->withBody(Psr7\stream_for($data));	
    }

    /**
     * PUT a un recurso. Retorna 204 (sin contenido) o 200 (con contenido) en caso de exito,
     * Si el recurso no existía, enviar un not_found().
     *
     * @return $this
     */
    public function put($data = null)
    {
        if (! isset($data) || is_null($data)) {
		return $this->withStatus(204);
        } else {
		$data = $this->getParaStream($data);
		return  $this->withStatus(200)->withBody(Psr7\stream_for($data));	
        }
    }

    /**
     * Retorna un 204 si es exitoso.
     * Si el recurso no existía, enviar un not_found().
     *
     */
    public function delete()
    {
	return $this->withStatus(204);
    }

    /**
     * Ocurrió un error de negocio- validacion, falta de datos, datos incorrectos,
     * se adjunta un mensaje con indicaciones para corregir el mensaje.
     */
    public function error_negocio($errores, $status = 400)
    {
	$errores = $this->getParaStream($errores);
	return  $this->withStatus($status)->withBody(Psr7\stream_for($errores));	
    }

    /**
     * NO se encontró el recurso en el servidor.
     */
    public function not_found($mensaje = '', $errores = array())
    {
        if ($mensaje == '') {
            $mensaje = self::$not_found_message;
        }
        throw new rest_error(404, $mensaje, $errores);
    }

    /**
     * Redirect.
     */
    public function redirect($url, $status = 302)
    {
	return  $this->withStatus($status)->withHeader('Location', $url);
    }
		
}
