<?php

namespace SIUToba\rest\seguridad\autenticacion;

use SIUToba\rest\http\request;
use SIUToba\rest\http\respuesta_rest;
use SIUToba\rest\seguridad\proveedor_autenticacion;
use SIUToba\rest\seguridad\rest_usuario;
use SIUToba\SSLCertUtils\SSLCertUtils;

require_once(__DIR__. '/../../lib/funciones_old_versions.php');
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
            $mensaje = '';
            if (!$cert_valido) {
                $mensaje = "El certificado presentado por el cliente no se puede verificar contra la CA definida en el Servidor Web (en apache la CA es la variable SSLCACertificateFile)";
            } else if (! $hay_CA) {
                $mensaje = "No se definió una CA en el Servidor Web (en apache la CA es la variable SSLCACertificateFile)";
            } else if (! $hay_vto) {
                $mensaje = "El certificado del cliente no tiene fecha de vencimiento";
            } else if (! $hay_serial) {
                $mensaje = "El certificado del cliente no tiene serial";
            }

            $this->set_error($mensaje);
            return;
        }

        if ($_SERVER['SSL_CLIENT_V_REMAIN'] <= 0) {                     //Le quedan dias de validez al cert?
            $this->set_error("El certificado de cliente está expirado");
            return;
        }

        if (isset($_SERVER['SSL_CLIENT_S_DN_CN']) && trim($_SERVER['SSL_CLIENT_S_DN_CN']) != '') {   //Busco el nombre del cliente
            $user = trim($_SERVER['SSL_CLIENT_S_DN_CN']);
            $cert = $_SERVER['SSL_CLIENT_CERT'];
            if ($this->es_valido($user, $cert)) {
                $usuario = new rest_usuario();
                $usuario->set_usuario($user);
                return $usuario;
            } else {
                $this->set_error("El certificado de cliente es válido, pero no se encuentra en el repositorio de usuarios");
            }
        } else {
            $this->set_error("El certificado de cliente no contiene un DN");
        }

        return; //anonimo
    }


    protected function calcularFingerprint($cert)
    {
        $certUtils = new SSLCertUtils();
        $certUtils->loadCert($cert);
        return $certUtils->getFingerprint();
    }

    function es_valido($usuario, $certificado)
    {
        //Calculo el fingerprint del certificado enviado por el usuario
        $fingerprint_cert = $this->calcularFingerprint($certificado);
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

    /**
     * Indica si la petición/headers debe manejarse con este mecanismo de autenticación.
     *
     * @param  request $request la petición
     *
     * @return boolean          true si este mecanismo atiende la petición de autenticación
     */
    public function atiende_pedido(request $request)
    {
        // la sola existencia de SSL_CLIENT_VERIFY indica que trabajamos con SSL
        return isset($_SERVER['SSL_CLIENT_VERIFY']) && $_SERVER['SSL_CLIENT_VERIFY'] != 'NONE';
    }
}
