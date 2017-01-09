<?php

namespace SIUToba\rest\seguridad\autenticacion;

use SIU\JWT\Util;
use SIU\JWT\Decoder\AbstractDecoder;

abstract class validador_jwt
{
    protected $jwt;

    public function __construct()
    {
        $this->jwt = new Util();
    }

    public function set_decoder(AbstractDecoder $decoder)
    {
        $this->jwt->setDecoder($decoder);
    }

    /**
     * Retorna el usuario
     */
    public function get_usuario($token)
    {
        $data = $this->jwt->decode($token);

        return $this->get_usuario_jwt($data);
    }

    abstract function get_usuario_jwt($data);
}
