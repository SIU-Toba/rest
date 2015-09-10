
[![Build Status](https://travis-ci.org/SIU-Toba/rest.svg?branch=master)](https://travis-ci.org/SIU-Toba/rest)

# REST

Esta librería permite servir APIs rest de forma simple pero estructurada. La misma no posee requisitos específicos de Toba y puede utilizarse de manera standalone en otros sistemas.

## Creación de una API REST

La definición de una API REST se basa en convenciones y no requiere especificar metadatos.

###Definición de Recursos

Toda información que pueda ser nombrada es un Recurso, por ejemplo: documentos, imagenes, colecciones de otros recursos, tablas definidas en una base de datos, etc.
Los recursos a publicar/compartir para un determinado proyecto deben indicarse mediante una clase PHP dentro de la carpeta _/proyectos/nombre_proyecto/php/rest/_. Por Ejemplo:

* `php/rest/recurso_personas.php` se publica en  `http://../rest/personas`
* `php/rest/personas/recurso_personas.php` se publica en `http://../rest/personas`
* `php/rest/recurso_deportes.php` se publica en `http://../rest/deportes`

Los archivos de los recursos deben tener el prefijo `recurso_`, por ejemplo, para el recurso `personas`, se debe definir el archivo `recurso_personas.php`. Cualquier otro archivo definido sin dicho prefijo, no será interpretada como recurso. El nombre en sí de la clase puede diferir del del archivo, no será tomado en cuenta por nada de la librería, simplemente instancia la clase que encuentre en el archivo.

Cada acceso al recurso tiene asociado un método en la clase del mismo, recibiendo como parámetros la parte dinámica de la URL. Por ejemplo, para el siguiente recurso se utiliza el parametro `id` como identificador:
```  php
    //Equivale a GET /rest/{id}: retorna un recurso puntual
    function get($id) ...
    
    //Equivale a DELETE /rest/{id}: elimina un recurso puntual
    function delete($id) ...
    
    //Equivale a PUT /rest/{id}: modifica parte de los atributos del recuso 
    function put($id) ...    
```    

Aquí un ejemplo completo de recurso `personas`:

``` php
<?php
class recurso_personas
{

    function get($id_persona)
    {
        $modelo = new modelo_persona($id_persona);
        $fila = $modelo->get_datos();
        rest::response()->get($fila);        
    }

    function delete($id_persona)
    {
        $modelo = new modelo_persona($id_persona);
        $ok = $modelo->delete();
        $errores = array();
        if (!$ok) {
            rest::response()->not_found();
        } else {
            rest::response()->delete($errores);
        }
    }

    function put($id_persona)
    {
        $datos = rest::request()->get_body_json();
        $modelo = new modelo_persona($id_persona);
        $ok = $modelo->update($datos);
        if (!$ok) {
            rest::response()->not_found();
        } else {
            rest::response()->put();
        }
    }
}
```
Para los casos en los que se requiera recuperar un conjunto de recursos o dar de alta un recurso en particular, se utiliza el sufijo list (para hacer referencia que es sobre la lista de valores y no sobre uno puntual):

``` php
    // Equivale a GET /rest: retorna el recurso como un conjunto
    function get_list() ...
    
    // Equivale a POST /rest: da de alta un nuevo recurso
    function post_list($id) ...
```

``` php
<?php
class recurso_personas
{

    function post_list()
    {
        $datos = rest::request()->get_body_json();
        $nuevo = modelo_persona::insert($datos);
        $fila = array('id' => $nuevo);
        rest::response()->post($fila);
    }

    function get_list()
    {
        $personas = modelo_persona::get_personas($where);
        rest::response()->get($personas);
    }
```

Si se quiere enviar respuestas que no sean JSON o con headers especificos, se puede hacer cambiando la **vista** y configurando la respuesta de la siguiente manera:
``` php
<?php
class recurso_documento
{

    function get_list()
    {
        $pdf = documentos::get_pdf();

        $vista_pdf = new \SIUToba\rest\http\vista_raw(rest::response());
        $vista_pdf->set_content_type("application/pdf");
        rest::app()->set_vista($vista_pdf);

        rest::response()->set_data($pdf);
        rest::response()->set_status(200);
        rest::response()->add_headers(array(
            "Content-Disposition" => "attachment; filename=Mi_documento.pdf"
        ));
    }
```


###Sub APIs

La librería permite agrupar recursos en subcarpetas, con **hasta dos niveles** de profundidad, permitiendo asi, definir sub APIs y lograr una mejor división semántica que facilite la aplicación de distintas configuraciones según el caso. Además estas subcarpetas sirven de prefijo de acceso en la URL, por ejemplo _/personas/deportes/_. 

Por ejemplo, una API que brinda servicios al usuario actual, puede tener las subdivisiones `admin` y `me`. Para esto se deberá crear una carpeta _/rest/me_ y _/rest/admin_ sin ningún recurso dentro. Si se quieren conocer las `mascotas` del usuario actual, se debe crear un recurso `mascotas` en _/rest/me/mascotas/recurso_mascotas.php_ y luego, se podrá acceder por medio de la url _/rest/me/mascotas_. La alternativa, mas compleja, sin utilizar sub APIs, es accediendo a _/rest/usuarios/{usuario_actual}/mascotas_.

##Links relacionados
* [**Testing de APIs REST**](https://github.com/SIU-Toba/rest/wiki/Testing-de-APIs-REST)
* [**Documentación de APIs REST**](https://github.com/SIU-Toba/rest/wiki/Documentaci%C3%B3n-de-APIs-REST)
* [**Convenciones en la creación de APIs REST**](https://github.com/SIU-Toba/rest/wiki/Convenciones-en-la-creaci%C3%B3n-de-APIs-REST)
* [**Uso de la libreria REST standalone**](https://github.com/SIU-Toba/rest/wiki/Uso-de-la-libreria-REST-standalone)
