<?php
if (!function_exists('utf8_e_seguro')) {
    /**
    * Convierte el string a UTF-8 a menos que ya se encuentre en dicho encoding.
    * @param string $s
    * @return string $s en utf8
    */
    function utf8_e_seguro($s)
    {
        echo 'paso por enconde ' . PHP_EOL;
        if (mb_detect_encoding($s, "UTF-8", true) == "UTF-8") {
            return $s;
        }

        return \utf8_encode($s);
    }
}

if (!function_exists('utf8_d_seguro')) {
    /**
    * Convierte a LATIN-1 un string UTF-8, a menos que no este en ese encoding.
    * @param string $s
    * @return string $s en latin1
    */
    function utf8_d_seguro($s)
    {
        echo 'paso por Decode ' . PHP_EOL;
        if (mb_detect_encoding($s, "UTF-8", true) == "UTF-8") {
            return \utf8_decode($s);
        }

        return $s;
    }
}
?>