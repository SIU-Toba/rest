<?php

namespace SIUToba\rest\seguridad\autenticacion;

use SIUToba\rest\http\request;
use SIUToba\rest\http\respuesta_rest;
use SIUToba\rest\seguridad\proveedor_autenticacion;
use SIUToba\rest\seguridad\rest_usuario;

class autenticacion_ssl extends proveedor_autenticacion
{
    /**
     * @var usuarios_usuario_password
     */
    protected $validador_ssl;

    public function __construct(usuarios_usuario_password $pu)
    {
        $this->validador_ssl = $pu;
    }

    public function get_usuario(request $request = null)
    {
        $cert_valido = (isset($_SERVER['SSL_CLIENT_VERIFY']) && $_SERVER['SSL_CLIENT_VERIFY'] == 'SUCCESS'); //Se presenta certif. y esta verificado
        $hay_serial = isset($_SERVER['SSL_CLIENT_M_SERIAL']);           //Tiene serial el cert?
        $hay_CA = isset($_SERVER['SSL_CLIENT_I_DN']);
        $hay_vto = isset($_SERVER['SSL_CLIENT_V_END']);                 //Hay vencimiento?
        if (!$hay_serial || !$hay_vto || !$cert_valido || !$hay_CA) {
            return; 
        }

        if ($_SERVER['SSL_CLIENT_V_REMAIN'] <= 0) {                     //Le quedan dias de validez al cert?
            return;
        }
 
        if (isset($_SERVER['SSL_CLIENT_S_DN_CN']) && trim($_SERVER['SSL_CLIENT_S_DN_CN']) != '') {    //Busco el nombre del cliente
            $user = trim($_SERVER['SSL_CLIENT_S_DN_CN']);
            $cert = $_SERVER['SSL_CLIENT_CERT'];
            if ($this->validador_ssl->es_valido($user, $cert)) {                
                $usuario = new rest_usuario();
                $usuario->set_usuario($user);
                return $usuario;
            }
        }

        return; //anonimo
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
        /*$rta->add_headers(array(
            'WWW-Authenticate' => 'Basic realm="Usuario de la API"',
        ));
        $rta->set_data(array('mensaje' => 'autenticación cancelada'));*/
    }
}
