<?php

namespace SIUToba\rest\seguridad;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\seguridad\autenticacion\rest_error_autenticacion;
use SIUToba\rest\seguridad\autorizacion\rest_error_autorizacion;

/**
 * Parte del esquema inspirado en symfony
 * http://symfony.com/doc/current/book/security.html.
 *
 * El firewall, si captura una ruta, se encarga de la autenticacion
 */
class firewall
{
    protected $authentications;
    protected $authorization;
    protected $path_pattern;

    public function __construct($authen, proveedor_autorizacion $author, $pattern)
    {
        // BC
        if (!is_array($authen))
            $authen = array($authen);

        $this->authentications = $authen;
        $this->authorization = $author;
        $this->path_pattern = $pattern;
    }

    public function maneja_ruta($ruta)
    {
        return preg_match($this->path_pattern, $ruta) == 1;
    }

    /**
     * @param $ruta
     * @param $request
     *
     * @throws autenticacion\rest_error_autenticacion
     * @throws autorizacion\rest_error_autorizacion
     *
     * @return rest_usuario
     */
    public function manejar($ruta, $request)
    {
        /* RFC:
          401 Unauthorized:
              If the request already included Authorization credentials, then the 401 response indicates that authorization has been refused for those credentials.
          403 Forbidden:
              The server understood the request, but is refusing to fulfill it.
         */

        // buscamos algun mecanismo de auth que atienda el pedido
        $authentication = null;

        if (count($this->authentications) == 1) {                           //BC
            // current ya invoca la closure
            $authentication = current($this->authentications);
        } else {
            foreach ($this->authentications as $auth){
                // invocamos la closure
                $auth = $auth();

                // basic|digest son el ultimo metodo, no atienden antes de redirect
                if ($auth instanceof autenticacion\autenticacion_basic_http ||
                    $auth instanceof autenticacion\autenticacion_digest_http ||
                    $auth->atiende_pedido($request)){
                    $authentication = $auth;
                    break;
                }
            }

            // para el caso de que ningún mecanismo atienda el pedido
            if ($authentication == null){
                throw new rest_error(401, 'No se pudo cargar un autenticador para el pedido');
            }
        }

        $usuario = $authentication->get_usuario($request);

        if (!$this->authorization->tiene_acceso($usuario, $ruta)) {
            if (null === $usuario) {
                throw new rest_error_autenticacion($authentication, $authentication->get_ultimo_error());
            } else {
                throw new rest_error_autorizacion();
            }
        }

        return $usuario;
    }
}
