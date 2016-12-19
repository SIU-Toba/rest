<?php

namespace SIUToba\rest\seguridad\autenticacion;

use SIUToba\rest\http\request;
use SIUToba\rest\http\respuesta_rest;
use SIUToba\rest\seguridad\proveedor_autenticacion;
use SIUToba\rest\seguridad\rest_usuario;


class autenticacion_jwt extends proveedor_autenticacion
{
    protected $validador_jwt;

    protected $mensaje;

    public function __construct(usuarios_jwt $pu)
    {
        $this->validador_jwt = $pu;

        $this->mensaje = "Autenticación cancelada, falta información";
    }

    public function get_usuario(request $request = null)
    {
        $auth_header = $request->headers('HTTP_AUTHORIZATION', null);
        if ($auth_header === null) {
            return;
        }

        $well_formed_header = preg_match('/Bearer (.+)/i', $auth_header, $result);

        if ($well_formed_header === 0 || $well_formed_header === false) {
            return;
        }

        $token = $result[1];

        $user = $this->validador_jwt->get_usuario_jwt($token);
        if ($user != null) {
            $usuario = new rest_usuario();
            $usuario->set_usuario($user);
            return $usuario;
        }

        if (isset($token)) {
            $this->mensaje = "No se encontro usuario válido en el token";
        } else {
            $this->mensaje = 'Debe proveer un token válido';
        }

        return ; // anónimo
    }

    protected function es_valido($token)
    {
        return true;
    }

    /**
     * Escribe la respuesta/headers para pedir autenticacion al usuario.
     *
     * @param respuesta_rest $rta
     *
     * @return mixed
     */
    public function requerir_autenticacion(respuesta_rest $rta)
    {
        $rta->set_data(array('mensaje' => $this->mensaje));
    }
}
