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

    protected function getProperty($campo, $def)
    {
        if (isset($def['_compuesto']) && ! empty($def['_compuesto'])) {
			$aux = $this->getPropertiesCompuesto($def['_compuesto']);
            $def = array('type' => 'object', 'properties' => $aux);
        }
		
        $prop = array();
        foreach($def as $k => $campo_def) {
            if (false === \strpos($k, '_')) {           //Obtengo propiedades != _compuesto y _mapeo
				if ($k == 'type') {
					$prop = array_merge($prop, tipo_datos_docs::get_tipo_formato($campo_def));
				} else {
					
					// Si se hace referencia a otro schema se debe agregar el prefijo '#/components/schemas/' 
					// ver: https://swagger.io/docs/specification/data-models/data-types/#array
					if (isset($campo_def['$ref'])) {
						$campo_def['$ref'] = '#/components/schemas/'. trim($campo_def['$ref']);
					}
					
					$prop[$k] = $campo_def;
				}
             //   $prop[$k] = ($k != 'type') ? $campo_def: tipo_datos_docs::get_tipo_formato($campo_def);
            } elseif (false !== \strpos($k, '_mapeo')) {      //Busco posibles mapeos de nombres
            //    $prop['discriminator'] = array('propertyName' => $campo, 'mapping' => $campo_def);
            }
        }
        return array($campo => $prop);
    }

	private function getPropertiesCompuesto($def)
	{
		$prop = array();
		foreach($def as $campo => $campo_def) {
			$prop = array_merge($prop, $this->getProperty($campo, $campo_def));
        }
		return $prop;
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
