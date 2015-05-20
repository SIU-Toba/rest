<?php

namespace SIUToba\rest\lib;


class modelo_recursos
{
    /**
     * Recibe un arreglo de modelos.
     *
     * @param $models
     *
     * @return array
     */
    public function to_swagger($models)
    {
        $out = array();
        foreach ($models as $id_modelo => $m) {
            $nuevo = $this->to_swagger_modelo($id_modelo, $m);
            $out[$id_modelo] = $nuevo;
        }

        return $out;
    }

    protected function to_swagger_modelo($id, $modelo_in)
    {
        $required = array();
        $properties = array();

        foreach ($modelo_in as $campo => $def) {
            $this->get_property($properties, $campo, $def);                 //$properties is an in/out parameter...... Damocles sword is over our neck!!
            if (isset($properties[$campo]['required'])) {
                $required[] = $campo;
            }
        }
		
        return $nuevo = array(
            'id' => $id,
            'required' => array_values($required),
            'properties' => $properties,
        );
    }

    protected function get_property(&$properties, $campo, $def)
    {
        $property = array();
        //TODO, hacer mas modelos para representar estos subrecursos? eso impacta en definiciones y herencia entre ellas?
        if (isset($def['_compuesto'])) {
            //$def = array('type' => $campo); //lo muestro asi por ahora
            $aux = array();
            $this->get_property($aux, $campo, $def['_compuesto']);
            $def = array('type' => $aux);
        }

        //paso derecho los campos no especiales
        foreach ($def as $k => $campo_def) {
            if (strpos($k, '_') !== 0) {
                $property[$k] = $campo_def;
            }
        }
        $properties[$campo] = $property;
    }
}
