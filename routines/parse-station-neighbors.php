<?php
/*
 * CSV с координатами из OpenStreetMap взят с сайта http://osm.sbin.ru/esr/
 */
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../app/globals.php';

$url = 'http://osm.sbin.ru/esr/region:leningrad:l';

$file = __DIR__ . '/data/station-neighbors.html';

$data = file_get_contents($file);
$html = '<html><body>' . $data . '</body></html>';
$html = '<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html;
$dom = new DOMDocument();
libxml_use_internal_errors(true);

$dom->loadHTML($html);
$dom->preserveWhiteSpace = false;

$table = $dom->getElementsByTagName('table')->item(0);
$rows = $table->getElementsByTagName('tr');
$n = 1;
$line = '';
//echo "All: " . $rows->length . "<br>";
foreach ($rows as $row) {
    if ($n > 1) {
//        echo $n . " : ";
        $cols = $row->getElementsByTagName('td');
//        echo $cols->length . " | ";
        if ($cols->length == 1) {
            // Направление (линия)
            $line = $cols->item(0)->nodeValue;
        } else {

            $esr = $cols->item(0)->getElementsByTagName('a')->item(0);
            $esr = (int)$esr->getAttribute('name');

            $division = 0;
            $_division = $cols->item(6)->nodeValue;
            if (strpos($_division, 'Витебское') !== false) {
                $division = 2;
            } else {
                $division = 3;
            }

            $yandex_id = null;

            $_links = $dom->saveHTML( $cols->item(7) );
            if (preg_match('/rasp\.yandex\.ru\/info\/station\/(\d+)/', $_links, $matches)) {

                if (isset($matches['1']))
                    $yandex_id = $matches['1'];
            }


            $title = (string)$cols->item(1)->nodeValue;
            $title = str_replace(chr(194).chr(160), " ", $title);
            $title = trim(trim(preg_replace('/\t+/', '', $title)));

            $station_data = array(
                'id'        =>  $esr,
                'title'     =>  $title,
                'division'  =>  $division,
                'yandex_id' =>  $yandex_id
            );

            $db
                ->onDuplicate(['division', 'title', 'yandex_id'])
                ->insert('railway_division_station', $station_data);

            /*
            $_neighbor = $cols->item(3)->getElementsByTagName('a');
            foreach ($_neighbor as $neighbor) {
                $neighbor_esr = $neighbor->getAttribute('href');
                $neighbor_esr = (int)str_replace('#', '', $neighbor_esr);
                $db->insert('railway_division_station_neighbor', array(
                    'station'   =>  $esr,
                    'neighbor'  =>  $neighbor_esr
                ));
            }*/
            //print_r($neighbor);
        }
//        echo "<br>";
        /*
        $data = array(
            'id'        => trim($cols->item(0)->nodeValue),
            'title'     => trim($cols->item(1)->nodeValue),
            'division'  => 2
        );
        $db
            ->onDuplicate(array('title'))
            ->insert('railway_division_station', $data);*/
    }

    if ($n > 10) {
        //die();
    }
    $n++;
}

/*
$station = $db
    ->where('lat IS NULL')
    ->orWhere('lng IS NULL')
    ->orWhere('osm_id IS NULL')
    ->get('railway_division_station', null, 'id, title');
foreach ($station as $s) {
    if (isset($esr[$s['id']])) {
        $db
            ->where('id', $s['id'])
            ->update('railway_division_station', array(
                'lat'   =>  $esr[$s['id']]['lat'],
                'lng'   =>  $esr[$s['id']]['lng'],
                'osm_id'=>  $esr[$s['id']]['osm_id']
        ));
    } else {
        echo "No info about {$s['id']} {$s['title']}<br>";
    }
}*/