# CHANGELOG
## v 2.0.7 19/10/2020
 * Se fixea inconveniente con uso de autenticación `toba` en concomitancia con otro método

## v 2.0.6 23/09/2020
 * Agrega el parametro `schemes` a la generacion de la documentacion

## v 2.0.5 16/06/2020
 * Agrega la posibilidad de setear parametros del request para operar detras de un proxy
 * Fix en el template del recurso_info 

## v 2.0.4 18/05/2020
 * Agrega la posibilidad de incluir referencias a tipos propios en el modelo
 
## v 2.0.3 18/05/2020
 * Agrega template para un recurso que de información de la API (sirve como healthcheck)
 * Agrega posibilidad de especificar un logo para la documentacion
 * Agrega mecanismo para hacer univoco el campo operationId
 * Agrega parametrización de info básica para armado documentación en JSON

## v 2.0.2 06/10/2017
 * Fix encoding mensaje error en respuesta

## v 2.0.1 16/05/2017
 * Vuelve version para PHP 5.6 a 7+

## v 2.0.0 10/05/2017
 * Version para PHP 7+
 
## v 1.1.8 10/04/2017
 * Bugfix con closures y PHP 5.6

## v 1.1.7 04/04/2017
 * Se agrega mecanismo de logs

## v 1.1.6 09/03/2017
 * Autenticacion via JWT
 * Fix encoding de la respuesta

## v 1.1.5 12/04/2016
 * Autenticacion via X509

## v 1.1.4 28/10/2015
 * Bugfixes
    
## v 1.1.3 25/09/2015
 * Bugfixes

## v 1.1.2 14/09/2015
 * Agregado tipo arreglo a rest_validador
 * Nombre de clase puede diferir del nombre archivo
 * Agregado de multi-paths tanto para ruteador y generador de documentación
 * Agregada posibilidad de retornar datos en un PUT
 * Agregada vista_raw para devolver binarios por ejemplo
 * Corrección de la generación de documentación
 * Agregado de setters a la app para inyectar o reemplazar dependencias
 
## v 1.1.1 24/06/2015
 * Se agrega parametro API-Version y se actualizan el parseo de modelos
 
## v 1.1.0 10/03/2015
 * Se agregan puntos de montaje (/me, /admin) para subdividir la API
 * Se permiten nombres compuestos para los recursos. Los nombres compuestos se separan por '-' en la url, y por '_' en el código
