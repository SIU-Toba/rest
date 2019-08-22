<?php

namespace SIUToba\rest\tests\http;

use \PHPUnit\Framework\TestCase;
use SIUToba\rest\http\respuesta;

class respuestaTest extends TestCase
{
    /**
     * @expectedException SIUToba\rest\lib\rest_error_interno
     */
    public function testFinalizarError()
    {
        $r = new respuesta();
        $r->finalizar(); //no se seteo la respuesta
    }

    public function testFinalizarValidacionVacio()
    {
        $r = new respuesta("data", 204);
        $r = $r->finalizar();       //Recupera el nuevo objeto respuesta
        $this->assertEmpty($r->get_data()->__toString());

        $r = new respuesta("data", 304);
        $r->finalizar();            //Sigue con el objeto original
        $this->assertTrue($r->get_data()->__toString() === 'data');
    }
}
