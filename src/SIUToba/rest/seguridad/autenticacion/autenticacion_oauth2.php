<?php
/**
 * Created by IntelliJ IDEA.
 * User: andres
 * Date: 1/14/15
 * Time: 5:31 PM.
 */

namespace SIUToba\rest\seguridad\autenticacion;

use SIUToba\rest\http\request;
use SIUToba\rest\http\respuesta_rest;
use SIUToba\rest\seguridad\autenticacion\oauth2\oauth_token_decoder;
use SIUToba\rest\seguridad\proveedor_autenticacion;
use SIUToba\rest\seguridad\rest_usuario;

class autenticacion_oauth2 extends proveedor_autenticacion
{
    /**
     * @var oauth_token_decoder
     */
    protected $decoder;

    public function __construct()
    {
    }

    public function set_decoder(oauth_token_decoder $decoder)
    {
        $this->decoder = $decoder;
    }

    /**
     * Obtiene un usuario si est� logueado o si lo puede obtener del request o cualquier otro medio.
     * Si el usuario es nulo, se puede llegar a llamar a requerir_autenticacion (si la operacion lo requiere).
     * En caso de errores, guardarlos y enviarlos en la respuesta.
     *
     * @param request $request
     *
     * @return rest_usuario el usuario logueado o null si es anonimo
     */
    public function get_usuario(request $request = null)
    {
        $auth_header = $request->headers('HTTP_AUTHORIZATION', null);

        $well_formed_header = preg_match('/Bearer (.+)/i', $auth_header, $result);

        $token = $result[1];

        $info = $this->decoder->decode($token);

        if ($info === null) {
            return;
        }

        $usuario = new rest_usuario();
        $usuario->set_usuario($info->get_user_id());
        $usuario->set_perfiles($info->get_scopes());

        return $usuario;
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
        // quiz� haya que agregar m�s detalles al error: http://hdknr.github.io/docs/identity/bearer.html#id5
        $rta = $rta
                ->set_status(401)
                ->add_headers(array('WWW-Authenticate' => 'Bearer'));
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
