<?php
/**
 * Created by IntelliJ IDEA.
 * User: alejandro
 * Date: 2/18/14
 * Time: 12:39 PM.
 */

namespace SIUToba\rest\tests\http;

use PHPUnit\Framework\TestCase;
use SIUToba\rest\http\respuesta_rest;
use SIUToba\rest\lib\rest_error;

class respuesta_restTest extends TestCase
{
    public function testGet()
    {
        $data = array(1);
        $r = new respuesta_rest();
        $r = $r->get($data);			//Devuelve una nueva instancia
	
        $this->assertEquals(200, $r->get_status());
        $this->assertEquals($data, json_decode($r->get_data()->getContents(), true));
    }

    /**
     * @expectedException rest_error
     */
    public function testGetNotFound()
    {
		$this->expectException(rest_error::class);
        $data = false;
        $r = new respuesta_rest();
        $r->get($data);
    }

    /**
     * @expectedException rest_error
     */
    public function testNotFound()
    {
		$this->expectException(rest_error::class);
        $r = new respuesta_rest();
        $r->not_found("mje");
    }

    public function testPutOK()
    {
        $r = new respuesta_rest();
        $r = $r->put();
		
        $this->assertEquals(204, $r->get_status());
        $this->assertEmpty($r->get_data()->__toString());			//Hay que testear un stream empty
    }

    public function testDeleteOK()
    {
        $r = new respuesta_rest();
        $r = $r->delete();
        $this->assertEquals(204, $r->get_status());
        $this->assertEmpty($r->get_data()->__toString());			//Hay que testear un stream empty
    }

    public function testRedirect()
    {
        $r = new respuesta_rest();
        $r = $r->redirect('hola');
        $this->assertArrayHasKey('Location', $r->getHeaders());
        $this->assertTrue($r->hasHeader('Location'));
        $this->assertEquals($r->getHeader('Location'), ['hola']);
    }

    public function testErrorNegocio()
    {
        $r = new respuesta_rest();
        $error = array('error' => 'e');
        $r = $r->error_negocio($error);
        $this->assertEquals(400, $r->get_status());
        $this->assertEquals($error, json_decode($r->get_data()->__toString(), true));
    }
}
