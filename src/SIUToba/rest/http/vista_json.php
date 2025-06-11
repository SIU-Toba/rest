<?php

namespace SIUToba\rest\http;

class vista_json extends vista_respuesta
{
    protected $pretty_print = true;

    protected function get_content_type()
    {
        return 'application/json';
    }

    public function get_cuerpo()
    {
        $data = $this->respuesta->get_data();
        if (!empty($data)) {
            $data = $this->utf8_encode_fields($data);
        }

        if ($this->pretty_print) {
            $output = json_encode($data, JSON_PRETTY_PRINT);
        } else {
            $output = json_encode($data);
        }

        return $output;
    }

    protected function utf8_encode_fields(array $elements)
    {
        $keys_e = array_keys($elements);
        foreach ($keys_e as $key) {
            if (is_array($elements[$key])) {
                $elements[$key] = $this->utf8_encode_fields($elements[$key]);
            } elseif (!empty($elements[$key]) && mb_detect_encoding($elements[$key], "UTF-8", true) !== "UTF-8") {
                $elements[$key] = mb_convert_encoding($elements[$key], 'UTF-8', 'LATIN1');
            }
        }
        return $elements;
    }
}
