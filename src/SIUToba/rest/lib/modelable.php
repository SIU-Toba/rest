<?php

namespace SIUToba\rest\lib;

/**
 * Esta clase no es obligatoria para el uso de los modelos. Está para referencia mayormente.
 */
interface modelable
{
    /**
     * Retorna un arreglo de modelos.
     *
     * @return array
     */
    public static function _get_modelos();

//	El modelo se construye con los campos del lado izquiero.
//	Se obtienen de la columna con el mismo nombre
//
//   Los campos especiales son
//      -- _mapeo: el valor del campo se toma de la columna _mapeo.
//      -- _compuesto: el valor del campo es un subarreglo que se calcula recursivamente con dicha especificacion.
//      -- _id: la fila que no se debe repetir (se usa al agrupar; filas con el mismo id, se agrupan segun las columas _agrupado.
//      -- _agrupado: si la columna tiene este atributo se agrupan los valores de la misma entre filas que compartan la columna _id.
//
//		Ejemplos
//
//	  		'Curso' => array(
//	 				'id_curso_externo'=> array('type' => 'string', _mapeo' => 'curso'),
//	 				'nombre' => array('type' => 'string'),
//	 				'estado' => array('type' => 'string','enum' => array('A', 'B')),
//	 				'id_plataforma' => array('type' => 'string','_mapeo' => 'sistema'),
//	 				'comisiones' => array('type'=> 'array', 'items'=> array('type'=> 'Comision')),
//	 		),
//
//			'Comision' => array(
//				"comision"	=> array('type' => 'integer'),
//				"nombre" 	=> array ('type' => 'string') ,
//				"catedra"    	=> array('type' => 'string','_mapeo' => "nombre_catedra"),
//
//				"modalidades"  	=> array('_mapeo' => "nombre_modalidad", "type"   => "array", "items" => array("\$ref" => "string")),
//				
//				"turno"		=> array('_compuesto' =>
//					                             array('turno'        => array('type' => 'string',),
//					                                   "nombre" => array('type' => 'string','_mapeo' => "nombre_turno"))
//				),
//				'ubicacion'	=> array('_compuesto' =>
//					                             array('ubicacion'        => array('type' => 'string',),
//					                                   'nombre_ubicacion' => array('type' => 'string','_mapeo' => "nombre"))
//				),
//				'actividad'	=> array('_compuesto' => array(
//								'codigo' => array('type' => 'string','_mapeo' => "codigo_actividad"),
//								'nombre' => array('type' => 'string','_mapeo' => "nombre_actividad"))
//				),
//
//				'periodo_lectivo'   => array('_compuesto' => array(
//								'periodo_lectivo' => array('type' => 'string'),
//								'nombre' => array('type' => 'string','_mapeo' => "nombre_periodo"))
//				),
//			),
//		);
//
//  		'Agrupacion' => array(
//					'comision' => array('type' => 'Comision') ,
//					'horarios' => array('_agrupado_por' => 'comision',
//							    '_compuesto' =>
//								array('dia'    => array('type' => 'date','_mapeo' => 'horario_dia'),
//									  'inicio' => array('type' => 'string','_mapeo' => 'horario_inicio'),
//									  'fin'    => array('type' => 'string','_mapeo' => 'horario_fin')
//								),
//							)
//			)
//      Esto mapea algo asi  [comision1; horario1], [comision1; horario2], [comision1; horario3]
//      a algo asi: [comision1; [horario1, horario2, horario3]]
//
//
//
}
