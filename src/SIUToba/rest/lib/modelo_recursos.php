<?php

namespace SIUToba\rest\lib;


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
            $def = array('type' => $aux);
        }

        $prop = array();
        foreach($def as $k => $campo_def) {
            if (false === \strpos($k, '_')) {           //Obtengo propiedades != _compuesto y _mapeo
                $prop[$k] = $campo_def;
            }
            /*if (false !== \strpos($k, '_mapeo')) {      //Busco posibles mapeos de nombres
                $prop['discriminator'] = array('propertyName' => $campo, 'mapping' => $campo_def);
            }*/
        }
        return array($campo => $prop);
    }

    protected function getSchema($id, $modelo_in)
    {
        $required = array();
        $properties = array();

        foreach ($modelo_in as $campo => $def) {
            $prop = $this->getProperty($campo, $def);
            if (isset($prop[$campo]['required'])) {
                $required[] = $campo;
            }
            $properties = array_merge($properties, $prop);
        }

        return $nuevo = array(
            //'id' => $id,
            'required' => array_values($required),
            'properties' => $properties,
            'type' => 'object',
            "xml" => [ "name" => $id],
            'nullable' => empty($required)
        );
    }


    protected function get_schema()
    {
        $property = array();
        //TODO, hacer mas modelos para representar estos subrecursos? eso impacta en definiciones y herencia entre ellas?
        if (isset($def['_compuesto'])) {
            $aux = array();
            $this->get_property($aux, $campo, $def['_compuesto']);
            $def = array('type' => $aux);
        }

        //paso derecho los campos no especiales
        foreach ($def as $k => $campo_def) {
            if (strpos($k, '_') !== 0) {
                $property[$k] = $campo_def;
				if ($k == 'items' && is_array($campo_def) && isset($campo_def['$ref'])) {	//Falta chequear tipo basico, queda proximo release
					$property[$k] = array('$ref' => "#/definitions/". trim($campo_def['$ref']));
				}
            }
        }
        $properties[$campo] = $property;
    }
}
