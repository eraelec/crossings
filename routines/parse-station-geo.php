<?php
/*
 * CSV с координатами из OpenStreetMap взят с сайта http://osm.sbin.ru/esr/
 */
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../app/globals.php';

$file = __DIR__ . '/data/osm2esr.csv';

$header = NULL;
$esr = array();
if (($handle = fopen($file, 'r')) !== FALSE)
{
    while (($row = fgetcsv($handle, null, ';')) !== FALSE)
    {
        if(!$header) {
            $header = $row;
        } else {
            $row[0] = (int)$row[0];
            $esr[ $row[0] ] = array(
                'lat'   =>  $row[4],
                'lng'   =>  $row[5],
                'osm_id'=>  $row[3]
            );
        }
    }
    fclose($handle);
}

$station = $db
    ->where('lat IS NULL')
    ->orWhere('lng IS NULL')
    ->orWhere('loc IS NULL')
    ->orWhere('osm_id IS NULL')
    ->get('railway_division_station', null, 'id, title');
$found = 0;
$notFound = 0;
foreach ($station as $s) {
    if (isset($esr[$s['id']])) {
        $found++;
        $db
            ->where('id', $s['id'])
            ->update('railway_division_station', array(
                'lat'   =>  $esr[$s['id']]['lat'],
                'lng'   =>  $esr[$s['id']]['lng'],
                'loc'   =>  $db->func('Point(?, ?)', [$esr[$s['id']]['lng'], $esr[$s['id']]['lat']]),
                'osm_id'=>  $esr[$s['id']]['osm_id']
        ));
    } else {
        $notFound++;
//        echo "No info about {$s['id']} {$s['title']}<br>";
    }
}
echo "Found: {$found}, not: {$notFound}\n";