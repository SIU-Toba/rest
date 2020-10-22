<?php

namespace SIUToba\rest\tests\lib;

use \PHPUnit\Framework\TestCase;
use SIUToba\rest\http\respuesta_rest;
use SIUToba\rest\lib\rest_error;

class rest_errorTest extends TestCase
{
    public function testInicializacion()
    {
        $status = 400;
        $mensaje = "mi mensaje";
        $arreglo = array('hola' => 'mundo');
        $error = new rest_error($status, $mensaje, $arreglo);

        $this->assertEquals($mensaje, $error->getMessage());
        $this->assertEquals($status, $error->getCode());
        $this->assertEquals($arreglo, $error->get_datalle());
    }

    public function testConfiguracionRespuesta()
    {
        $status = 400;
        $mensaje = "mi mensaje";
        $arreglo = array('hola' => 'mundo');
        $error = new rest_error($status, $mensaje, $arreglo);        
	$r = new respuesta_rest();
         $error->configurar_respuesta($r);

        $data = json_decode($r->get_data()->getContents(), true);
        $this->assertEquals($mensaje, $data['descripcion']);
        $this->assertEquals($status, $r->get_status());
        $this->assertEquals($arreglo, $data['detalle']);
    }

    public function testConfiguracionRespuestaSinDetalle()
    {
        $status = 400;
        $mensaje = "mi mensaje";
        $arreglo = array();
        $error = new rest_error($status, $mensaje, $arreglo);
        $r = new respuesta_rest();
        $error->configurar_respuesta($r);

        $data = json_decode($r->get_data()->getContents(), true);
        $this->assertArrayNotHasKey('detalle', $data);

        $error = new rest_error($status, $mensaje);
        $error->configurar_respuesta($r);
        $data = json_decode($r->get_data()->getContents(), true);
        $this->assertArrayNotHasKey('detalle', $data);
    }
}
