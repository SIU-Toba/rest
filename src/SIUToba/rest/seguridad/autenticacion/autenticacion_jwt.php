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

    public function __construct(validador_jwt $pu)
    {
        $this->validador_jwt = $pu;

        $this->mensaje = "Autenticaci贸n cancelada, falta informaci贸n";
    }

    public function get_usuario(request $request = null)
    {
        $auth_header = $request->headers('HTTP_AUTHORIZATION', null);

        preg_match('/Bearer (.+)/i', $auth_header, $result);

        $token = $result[1];

        try {
            $user = $this->validador_jwt->get_usuario($token);

            if ($user != null) {
                $usuario = new rest_usuario();
                $usuario->set_usuario($user);
                return $usuario;
            }
            if (isset($token)) {
                $this->mensaje = "No se encontro usuario v醠ido en el token";
            } else {
                $this->mensaje = 'Debe proveer un token v醠ido';
            }

        } catch (\Exception $exc) {
            $this->mensaje = $exc->getMessage();
        }

        return ; // an贸nimo
    }

    /**
     * Escribe la respuesta/headers para pedir autenticacion al usuario.
     *
     * @param respuesta_rest $rta
     *
     * @return mixed
     */
    public function requerir_autenticacion(respuesta_rest &$rta)
    {
        $rta = $rta->set_data(array('mensaje' => \utf8_e_seguro($this->mensaje)));
    }

    /**
     * Indica si la petici贸n/headers debe manejarse con este mecanismo de autenticaci贸n.
     *
     * @param  request $request la petici贸n
     *
     * @return boolean          true si este mecanismo atiende la petici贸n de autenticaci贸n
     */
    public function atiende_pedido(request $request)
    {
        $auth_header = $request->headers('HTTP_AUTHORIZATION', null);

        if ($auth_header === null) {
            return false;
        }

        $well_formed_header = preg_match('/Bearer (.+)/i', $auth_header, $result);

        if ($well_formed_header === 0 || $well_formed_header === false) {
            return false;
        }

        return true;
    }
}
