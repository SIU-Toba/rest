<?php

namespace SIUToba\rest\tests\seguridad;

use PHPUnit\Framework\TestCase;
use SIUToba\rest\seguridad\firewall;
use SIU\JWT\Decoder\SimetricDecoder;
use SIU\JWT\Encoder\SimetricEncoder;
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

        $decoder = new SimetricDecoder(Util::ALG_HS256, 'testquerequiere512bitsbytesdeinformacion');

        $validador->set_decoder($decoder);

        return $validador;
    }

    public function testGetUsuario()
    {
        $f = $this->get_instancia();

        // token para usuario=[123456]
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.WzEyMzQ1Nl0.3QoSh0UdQMVYa2T8bdFPk5SuTAJyD-hbJJ-ATJxkkDI';

        $usuario = $f->get_usuario($token);

        $this->assertEquals(current($usuario), 123456);
    }


}
