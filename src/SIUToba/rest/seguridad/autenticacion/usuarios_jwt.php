<?php

namespace SIUToba\rest\seguridad\autenticacion;

/**
 * Retorna el usuario del token JWT
 */
interface usuarios_jwt
{
    public function get_usuario_jwt($token);
}
