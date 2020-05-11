<?php
namespace SIUToba\rest\templates;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use SIUToba\rest\lib\rest_error_interno;

class template_info extends modelable
{
	public static function _get_modelos(): array
	{
		return array('info' => [	
							'version' => array('type' => 'string'), 
							'api_version' => array('type' => 'string'), 
							'api_major' => array('type'=> 'string'), 
							'api_minor' => array('type' => 'string')
						]);		
	}

	/**
	 * Devuelve informacion acerca de la API
	 * 
	 * @responses 200 {$ref:info} OK
	 */
	public function get()
	{
		$version = rest::config('version');
		$api = rest::config('api_version');
		$api_major = rest::config('api_major');
		$api_minor = rest::config('api_minor');
		
		if (is_null($version) || is_null($api) || is_null($api_major) || is_null($api_minor)) {
			rest::response()->error_negocio('La información solicitada no esta disponible', 500);
		} else {
			$datos = array( 'version' => $version,'api_version' => $api,
							'api_major' => $api_major,'api_minor' => $api_minor);
			rest::response()->get_list($datos);
		}
	}
}