<?php

namespace SIUToba\rest\lib;

use \SIUToba\rest\docs\tipo_datos_docs;

class modelo_recursos
{
    /**
     * Recibe un arreglo de modelos y lo convierte al modelo de swagger.
     *
     * @param $models
     * @return array
     * @deprecated since version number
     */
    public function to_swagger($models)
    {
        return $this->getSchemas($models);
    }

    /**
     * Recibe un arreglo representando los modelos y devuelve los schemas de los mismos
     * @param array $models
     * @return array
     */
    public function getSchemas($models)
    {
        $out = array();
        foreach ($models as $id_modelo => $m) {
            $out[$id_modelo] = $this->getSchema($id_modelo, $m);
        }
        return $out;
    }

    /**
     * @deprecated since version number
     */
    protected function get_property(&$properties, $campo, $def)
    {
        array_merge($properties, $this->getProperty($campo, $def));
    }

    protected function getProperty($campo, $def)
    {
        if (isset($def['_compuesto']) && ! empty($def['_compuesto'])) {
            $aux = $this->getProperty($campo, $def['_compuesto']);
            $def = array('type' => 'object', 'properties' => $aux[$campo]);
        }
		
        $prop = array();
        foreach($def as $k => $campo_def) {
            if (false === \strpos($k, '_')) {           //Obtengo propiedades != _compuesto y _mapeo
				if ($k == 'type') {
					$prop = array_merge($prop, tipo_datos_docs::get_tipo_formato($campo_def));
				} else {
					$prop[$k] = $campo_def;
				}
             //   $prop[$k] = ($k != 'type') ? $campo_def: tipo_datos_docs::get_tipo_formato($campo_def);
            } elseif (false !== \strpos($k, '_mapeo')) {      //Busco posibles mapeos de nombres
            //    $prop['discriminator'] = array('propertyName' => $campo, 'mapping' => $campo_def);
            }
        }
        return array($campo => $prop);
    }

    protected function getSchema($id, $modelo_in)
    {
        $required = array();
        $properties = array();
		$mapeos = array();

        foreach ($modelo_in as $campo => $def) {
            $prop = $this->getProperty($campo, $def);
            if (isset($prop[$campo]['required'])) {
                $required[] = $campo;
            }
			if (isset($prop[$campo]['discriminator'])) {
				$mapeos[] = $prop[$campo]['discriminator'];
			}
            $properties = array_merge($properties, $prop);
        }

        $nuevo = array(
			'type' => 'object',
            'properties' => $properties,
            'nullable' => empty($required)
        );
		if (! empty($required)) {
			$nuevo['required'] = array_values($required);
		}
		return $nuevo;
    }
}
