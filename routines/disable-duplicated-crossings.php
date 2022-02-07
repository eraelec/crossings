<?php
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../app/globals.php';

$crossings = $db
    ->join('crossings_manual cm', 'c.osm_id = cm.osm_id', 'LEFT')
    ->where('cm.disabled IS NULL')
    ->orderBy('station_closest_distance')
    ->get('crossings c');

$disabled = [];

foreach ($crossings as $crossing) {
    // пропускаем обработанный
    if (isset($disabled[$crossing['id']]))
        continue;

    $crossingsSameStation = $db
        ->where('station_closest', $crossing['station_closest'])
        ->get('crossings');

    foreach ($crossingsSameStation as $crossingSameStation) {
        // пропускаем текущий
        if ($crossingSameStation['id'] == $crossing['id'])
            continue;

        $diff = abs($crossingSameStation['station_closest_distance'] - $crossing['station_closest_distance']);

        if ($diff <= 10) {
            $disabled[$crossingSameStation['id']] = 1;
            $db
                ->onDuplicate(['disabled', 'duplicate'])
                ->insert('crossings_manual', [
                    'osm_id' => $crossingSameStation['osm_id'],
                    'disabled' => 1,
                    'duplicate' => 1
                ]);
        }
    }
}
var_dump($disabled);