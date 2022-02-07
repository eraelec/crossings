<?php
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../app/globals.php';

$file = __DIR__ . '/data/osm-crossings.json';

$data = file_get_contents($file);
$crossings = json_decode($data, true);

$props = [];

foreach ($crossings['features'] as $crossing) {
    $osm_id = $crossing['id'];
    $osm_id = (int) filter_var($osm_id, FILTER_SANITIZE_NUMBER_INT);
    $lat = $crossing['geometry']['coordinates'][1];
    $lng = $crossing['geometry']['coordinates'][0];

    $db
        ->onDuplicate(['lat', 'lng', 'loc'])
        ->insert('crossings_osm', [
            'osm_id' => $osm_id,
            'lat' => $lat,
            'lng' => $lng,
            'loc' => $db->func('Point(?, ?)', [$lng, $lat]),
        ]);
}