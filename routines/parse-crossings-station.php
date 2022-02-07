<?php
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../app/globals.php';

$crossings = $db
    ->get('crossings_osm');


foreach ($crossings as $crossing) {
    $stations = $db->rawQuery("SELECT id, title, "
        . " ST_Distance_Sphere(loc, POINT(?, ?)) distance FROM railway_division_station WHERE NOT loc IS NULL  ORDER BY distance LIMIT 2", array(
        floatval($crossing['lng']),
        floatval($crossing['lat'])
    ));

    if (!isset($stations[0]))
        continue;

    $stationClosest = $stations[0];
    if (isset($stations[1]))
        $stationNext = $stations[1];
    else
        $stationNext = null;


    $data = [
        'title' => $stationClosest['title'],
        'station_closest' => $stationClosest['id'],
        'station_closest_distance' => round($stationClosest['distance']),
        'station_next' => isset($stationNext) ? $stationNext['id'] : null,
        'station_next_distance' => isset($stationNext) ? round($stationNext['distance']) : null, // in meters
    ];

    $db
        ->where('id', $crossing['id'])
        ->update('crossings_osm', $data);
}