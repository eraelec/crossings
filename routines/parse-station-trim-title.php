<?php
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../app/globals.php';

foreach ($db->get('railway_division_station', null, 'id, title') as $s) {
    $s['title']=str_replace(chr(194).chr(160), " ", $s['title']);
    $trimmed = trim(trim(preg_replace('/\t+/', '', $s['title'])));
    if ($s['title'] != $trimmed) {
        $db->where('id', $s['id'])->update('railway_division_station', array(
            'title' =>  $trimmed
        ));
    }
}