<?php

namespace SIUToba\rest\tests\http;

use PHPUnit\Framework\TestCase;
use SIUToba\rest\http\respuesta;
use SIUToba\rest\lib\rest_error_interno;

class respuestaTest extends TestCase
{
    /**
     * @expectedException rest_error_interno
     */
    public function testFinalizarError()
    {
		$this->expectException(rest_error_interno::class);
        $r = new respuesta();
        $r->finalizar(); //no se seteo la respuesta
    }

    public function testFinalizarValidacionVacio()
    {
        $r = new respuesta("data", 204);
        $r->finalizar();
        $this->assertEmpty($r->get_data());

        $r = new respuesta("data", 304);
        $r->finalizar();
        $this->assertEmpty($r->get_data());
    }
}
