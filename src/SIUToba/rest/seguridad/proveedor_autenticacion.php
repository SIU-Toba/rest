<?php

namespace SIUToba\rest\seguridad;

use SIUToba\rest\http\request;
use SIUToba\rest\http\respuesta_rest;

abstract class proveedor_autenticacion
{
    /**
     * @var string
     */
    protected $ultimo_error = '';

    /**
     * Obtiene un usuario si está logueado o si lo puede obtener del request o cualquier otro medio.
     * Si el usuario es nulo, se puede llegar a llamar a requerir_autenticacion (si la operacion lo requiere).
     * En caso de errores, guardarlos y enviarlos en la respuesta.
     *
     * @param request $request
     *
     * @return rest_usuario el usuario logueado o null si es anonimo
     */
    abstract public function get_usuario(request $request = null);

    /**
     * Obtiene el último error de autenticación. Si no hubo ninguno devuelve un string vacío
     *
     * @return string el mensaje de error
     */
    public function get_ultimo_error()
    {
        return $this->ultimo_error;
    }

    /**
     * Setea el último error
     */
    protected function set_error($mensaje)
    {
        $this->ultimo_error = $mensaje;
    }

    /**
     * Escribe la respuesta/headers para pedir autenticacion al usuario.
     *
     * @param respuesta_rest $rta
     *
     * @return mixed
     */
    abstract public function requerir_autenticacion(respuesta_rest $rta);

    /**
     * Indica si la petición/headers debe manejarse con este mecanismo de autenticación.
     *
     * @param  request $request la petición
     * 
     * @return boolean true si este mecanismo atiende la petición de autenticación
     */
    abstract public function atiende_pedido(request $request);
}
