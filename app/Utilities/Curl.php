<?php

namespace App\Utilities;

class Curl{
    
    public static function getConsulta($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $dados = curl_exec($ch);

        return $dados;
    }

}