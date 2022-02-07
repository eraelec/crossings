<?php
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../app/globals.php';


$req_copyright = "https://api.rasp.yandex.net/v1.0/copyright/?" .
    "apikey=" . YANDEX_RASP_KEY .
    "&format=json";

if ($curl = curl_init()) {
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

    curl_setopt($curl, CURLOPT_URL, $req_copyright);
    $out = curl_exec($curl);
    echo $out;
    curl_close($curl);
}