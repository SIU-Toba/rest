<?php

namespace SIUToba\rest\tests\lib;

use PHPUnit\Framework\TestCase;
use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_validador;

class rest_validadorTest extends TestCase
{
    public function testOK()
    {
        $this->assertEquals(true, true);
    }

    public function testVacio()
    {
        $regla = array(
            'campo' => array('_validar' => array(rest_validador::TIPO_LONGITUD => array('min' => 1, 'max' => 2))),
        );
        $dato = array('campo' => '');
        $result = rest_validador::validar($dato, $regla);
        $this->assertTrue(true);
    }

    /**
     */
    public function testLongitudOk()
    {
        $regla = array(
            'campo' => array('_validar' => array(rest_validador::TIPO_LONGITUD => array('min' => 1, 'max' => 2))),
        );
        $dato = array('campo' => '12');
        rest_validador::validar($dato, $regla);
        $this->assertTrue(true);
    }

    /**
     * @expectedException rest_error
     */
    public function testLongitudError()
    {
		$this->expectException(rest_error::class);
        $regla = array(
            'campo' => array('_validar' => array(rest_validador::TIPO_LONGITUD => array('min' => 1, 'max' => 2))),
        );
        $dato = array('campo' => '123');
        rest_validador::validar($dato, $regla);
        $this->assertTrue(false);                 
    }

    /**
     * @expectedException rest_error
     */
    public function testLongitudError2()
    {
		$this->expectException(rest_error::class);
        $regla = array(
            'campo' => array('_validar' => array(rest_validador::TIPO_LONGITUD => array('min' => 2))),
        );
        $dato = array('campo' => '1');
        rest_validador::validar($dato, $regla);
        $this->assertTrue(false);
    }

    /**
     */
    public function testArrayOk()
    {
        $regla = array(
            'campo' => array('_validar' => array(rest_validador::TIPO_ARREGLO))
        );
        $dato = array('campo' => array('1'));
        rest_validador::validar($dato, $regla);
        $this->assertTrue(true);
    }

    /**
     * @expectedException rest_error
     */
    public function testLongitudError3()
    {
		$this->expectException(rest_error::class);
        $regla = array(
            'campo' => array('_validar' => array(rest_validador::TIPO_ARREGLO => array('min' => 2, 'max' => 3)))
        );
        $dato = array('campo' => array('1','7','5','23'));
        rest_validador::validar($dato, $regla);
        $this->assertTrue(false);
    }

    /**
     */
    public function testOKs()
    {
        $regla = array(
            'int' => array('_validar' => array(rest_validador::TIPO_INT => array('min' => 2, 'max' => 50))),
            'numer' => array('_validar' => array(rest_validador::TIPO_NUMERIC => array('min' => 8.34))),
            'alfa' => array('_validar' => array(rest_validador::TIPO_ALPHA)),
            'alfanum' => array('_validar' => array(rest_validador::TIPO_ALPHANUM)),
            'date' => array('_validar' => array(rest_validador::TIPO_DATE => array('format' => 'd/m/Y'))),
            'time' => array('_validar' => array(rest_validador::TIPO_TIME => array('format' => 'H:i:s'))),
            'enum' => array('_validar' => array(rest_validador::TIPO_ENUM => array('A', 'B', 'C'))),
            'texto' => array('_validar' => array(rest_validador::TIPO_TEXTO)),
            'long' => array('_validar' => array(rest_validador::TIPO_LONGITUD => array('min' => 2))),
            'oblig' => array('_validar' => array(rest_validador::OBLIGATORIO)),
            'arr' => array('_validar' => array(rest_validador::TIPO_ARREGLO => array('max' => 2))),
        );

        $datos = array(
            'int' => 50,
            'numer' => 8.35,
            'alfa' => 'abcdXYZ',
            'alfanum' => 'abcdXYZ1234567890',
            'date' => '04/10/1999',
            'time' => '15:30:05',
            'enum' => 'C',
            'texto' => '234j23io-+`+/*',
            'long' => '////////',
            'oblig' => 'fasd',
            'arr' => array('a', 'b'),
        );
        rest_validador::validar($datos, $regla);
        $this->assertTrue(true);
    }

    public function testErrores()
    {
        $regla = array(
            'int' => array('_validar' => array(rest_validador::TIPO_INT => array('min' => 2, 'max' => 50))),
            'numer' => array('_validar' => array(rest_validador::TIPO_NUMERIC => array('min' => 8.34))),
            'alfa' => array('_validar' => array(rest_validador::TIPO_ALPHA)),
            'alfanum' => array('_validar' => array(rest_validador::TIPO_ALPHANUM)),
            'date' => array('_validar' => array(rest_validador::TIPO_DATE => array('format' => 'd/m/Y'))),
            'time' => array('_validar' => array(rest_validador::TIPO_TIME => array('format' => 'H:i:s'))),
            'enum' => array('_validar' => array(rest_validador::TIPO_ENUM => array('A', 'B', 'C'))),
            'long' => array('_validar' => array(rest_validador::TIPO_LONGITUD => array('min' => 2))),
            'oblig' => array('_validar' => array(rest_validador::OBLIGATORIO)),
            'arr' => array('_validar' => array(rest_validador::TIPO_ARREGLO => array('max' => 2))),
        );

        $datos = array(
            'int' => 'a',
            'numer' => 8.25,
            'alfa' => '123abcd',
            'alfanum' => '--abcdXYZ1234567890',
            'date' => '30/30/1999',
            'time' => '15-30:05',
            'enum' => 'D',
            'long' => '/',
            'arr' => array('a','4', '5'),
        );
        try {
            rest_validador::validar($datos, $regla);
        } catch (rest_error $e) {
            //fallaron todas las reglas
            $this->assertEquals(count($regla), count($e->get_datalle()));
            return;
        }
        $this->assertTrue(false, "No se lanzo la excepci�n por los errores");
    }


}
