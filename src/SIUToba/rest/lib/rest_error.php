<?php

namespace SIUToba\rest\lib;

use Exception;
use SIUToba\rest\http\respuesta_rest;

class rest_error extends \Exception
{
    protected $detalle;

    /**
     * @param int    $status
     * @param string $mensaje
     * @param array  $detalle
     *
     * @throws \Exception
     */
    public function __construct($status, $mensaje, $detalle = array())
    {
        parent::__construct($mensaje, $status);

        if ($status < 400) {
            throw new Exception("Los errores HTTP son tipicamente codigo 400 o 500");
        }
        $this->detalle = $detalle;
    }

    public function get_datalle()
    {
        return $this->detalle;
    }

    public function configurar_respuesta(respuesta_rest &$rta)
    {
        $datos = array(
            'error' => \utf8_e_seguro($this->code),
            'mensaje' => \utf8_e_seguro($rta->getMessageForCode($this->code)),
            'descripcion' => \utf8_e_seguro($this->getMessage()));

        if (!empty($this->detalle)) {
            $datos['detalle'] = \array_map('utf8_e_seguro', $this->detalle);
        }
        $rta = $rta->set_status($this->code)->set_data($datos);

        return $this;
    }
}
