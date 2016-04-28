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
            if ($this->es_valido($user, $cert)) {                
                $usuario = new rest_usuario();
                $usuario->set_usuario($user);
                return $usuario;
            }
        }

        return; //anonimo
    }


    function es_valido($usuario, $certificado)
    {
        //Calculo el fingerprint del certificado enviado por el usuario
        $fingerprint_cert = self::certificado_get_fingerprint($certificado);
        //Recupero el fingerprint configurado anteriormente y comparo
        $fingerprint_local = $this->get_usuario_huella($usuario);
        if (is_null($fingerprint_local) || is_null($fingerprint_cert)) {            //Algo quedo mal en la configuracion del server, si sigue explota hash_equals
            return false;
        }
        return hash_equals($fingerprint_local, $fingerprint_cert);
    }

    function get_usuario_huella($usuario)
    {
       // $usuarios_ini = toba_modelo_rest::get_ini_usuarios($this->modelo_proyecto);
        foreach ($this->validador_ssl->get_passwords() as $key => $u) {
            if ($key === $usuario) {
                if (isset($u['fingerprint'])) {
                    return $u['fingerprint'];
                } else {
                    rest::app()->logger->info('Se encontro al usuario "' . $usuario . '", pero no tiene una entrada fingerprint en rest_usuario.ini');
                }
            }
        }
        return NULL;
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
        $rta->set_data(array('mensaje' => 'autenticación cancelada, falta información'));
    }

    static protected function certificado_decodificar($certificado)
    {
        $resource = openssl_x509_read($certificado);
        $output = null;
        $result = openssl_x509_export($resource, $output);
        if($result !== false) {
            $output = str_replace('-----BEGIN CERTIFICATE-----', '', $output);
            $output = str_replace('-----END CERTIFICATE-----', '', $output);
            return base64_decode($output);
        } else {
            throw new toba_error("El certificado no es un certificado valido", "Detalles: $certificado");
        }
    }

    static protected function certificado_get_fingerprint($certificado)
    {
        return sha1(self::certificado_decodificar($certificado));
    }
}
