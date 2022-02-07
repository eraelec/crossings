<?php
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../app/globals.php';

$file = __DIR__ . '/data/stations-2.html';
$url = 'http://www.russotrans.ru/spravochnik/spravgd/sankt-peterburgskoe/stations/';

$data = file_get_contents($file);
$html = '<html><body>' . $data . '</body></html>';
$html = '<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html;
$dom = new DOMDocument();

$dom->loadHTML($html);
$dom->preserveWhiteSpace = false;

$table = $dom->getElementsByTagName('table')->item(0);
$rows = $table->getElementsByTagName('tr');
$n = 1;
foreach ($rows as $row) {
    if ($n > 1) {
        $cols = $row->getElementsByTagName('td');

        $data = array(
            'id'        => trim($cols->item(0)->nodeValue),
            'title'     => trim($cols->item(1)->nodeValue),
            'division'  => 2
        );
        $db
            ->onDuplicate(array('title'))
            ->insert('railway_division_station', $data);
    }
    $n++;
}

/*
$file = 1;

function decode($str)
{
    return trim($str);
}

for ($file = 1; $file <=7; $file++) {
    $data = file_get_contents($url . $file . '.html');
    $html = '<html><body>' . $data . '</body></html>';
    $html = '<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html;
    $dom = new DOMDocument();

    $dom->loadHTML($html);
    $dom->preserveWhiteSpace = false;

    $tables = $dom->getElementsByTagName('table');
    $table = $dom->getElementsByTagName('table')->item(0);
    $rows = $table->getElementsByTagName('tr');
    $n = 1;
    foreach ($rows as $row) {
        if ($n > 1) {
            $cols = $row->getElementsByTagName('td');
            $tmp = explode(' ', decode($cols->item(3)->nodeValue));
            $data = array(
                'license_number' => decode($cols->item(0)->nodeValue),
                'organisation_name' => decode($cols->item(1)->nodeValue),
                'inn' => decode($cols->item(2)->nodeValue),
                'license_start' => str_to_sql_date($tmp[1]),
                'license_end' => str_to_sql_date($tmp[3]),
                'license_service_start' => str_to_sql_date(decode($cols->item(4)->nodeValue))
            );
            $db
                ->onDuplicate(array('license_number', 'organisation_name', 'license_start', 'license_end', 'license_service_start'))
                ->insert('operator', $data);
        }
        $n++;
    }
}*/