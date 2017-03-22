<?php

namespace SIUToba\rest\tests\seguridad;

use \PHPUnit\Framework\TestCase;
use SIUToba\rest\seguridad\firewall;
use SIU\JWT\Decoder\SimetricDecoder;
use SIUToba\rest\seguridad\autenticacion\validador_jwt;
use SIU\JWT\Util;

class ValidarJWT extends validador_jwt
{
    public function get_usuario_jwt($data)
    {
        return $data;
    }
}

class jwtTest extends TestCase
{
    protected function get_instancia()
    {
        $validador = new ValidarJWT();

        $decoder = new SimetricDecoder(Util::ALG_HS512, 'test');

        $validador->set_decoder($decoder);

        return $validador;
    }

    public function testGetUsuario()
    {
        $f = $this->get_instancia();

        // token para usuario uid=123456
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJ1aWQiOjEyMzQ1NiwibmFtZSI6Im15IHVzZXIgbmFtZSJ9.RZcDtMfrzoVEISsVYsVz11-rZ87rWqS7RHYctQnpZKDt8m8YsVZysh9Hu0OpDnPT-8JjHbWS_Xkz6Am11UAulQ';

        $usuario = $f->get_usuario($token);

        $this->assertEquals($usuario->uid, 123456);
    }


}
