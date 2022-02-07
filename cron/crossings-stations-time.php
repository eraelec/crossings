<?php
include_once __DIR__ . '/../inc/globals.php';

/*
 * ФУНКЦИОНАЛ ПЕРЕНЕС В СКРИПТ ПОДСЧЁТА РАСПИСАНИЯ
 *
 * Рассчитываем время проезда поезда от переездов до ближайших станций.
 *
 * При расчете времени проезда поезда берется среднее значение из расписания, но обычно в городе около 37км/ч
 */

$crossings = $db
    ->where('station', 0) // переезд между станциями
    ->where('station_left_time', 0)
    ->where('station_right_time', 0)
    ->where('station_left_distance', 0, '>')
    ->where('station_right_distance', 0, '>')
    ->where('id', 3)
    ->get('crossings');
foreach ($crossings as $crossing) {
    $crossing_neighbour_station_distance = $crossing['station_left_distance'] + $crossing['station_right_distance'];
    $schedule_left = $db
        ->join('trains t', 't.id = s.train', 'LEFT')
        ->where('s.station', $crossing['station_left'])
        ->orderBy('s.departure')
        ->get('schedule s', null, 't.number, s.arrival, s.departure, t.transport_type');
    $schedule_right = $db
        ->join('trains t', 't.id = s.train', 'LEFT')
        ->where('s.station', $crossing['station_right'])
        ->orderBy('s.departure')
        ->get('schedule s', null, 't.number, s.arrival, s.departure, t.transport_type');
    $trains_left = array();
    $trains_right = array();
    $trains = array();
    foreach ($schedule_left as $s) {
        $trains_left[$s['number']] = array(
            'arrival' => strtotime(date('Y-m-d')." ". $s['arrival']),
            'departure' => strtotime(date('Y-m-d')." ". $s['departure']),
            'transport_type'    =>  $s['transport_type']
        );
    }
    foreach ($schedule_right as $s) {
        $trains_right[$s['number']] = array(
            'arrival' => strtotime(date('Y-m-d')." ". $s['arrival']),
            'departure' => strtotime(date('Y-m-d')." ". $s['departure']),
            'transport_type'    =>  $s['transport_type']
        );
    }
    $avg_time_crossing_pass = 0;
    $_avg_time_crossing_pass = array();
    foreach ($trains_left as $train_number => $train_schedule_left) {
        $train_schedule_right = $trains_right[$train_number];
        $time = 0;
        if ($train_schedule_right['departure'] < $train_schedule_left['arrival']) {
            // Если поезд отправился с правой станции раньше, чем пришел на левую - значит он
            // доберется до переезда за время от правой станции
            $time = $train_schedule_left['arrival'] - $train_schedule_right['departure'];
        } elseif ($train_schedule_left['departure'] < $train_schedule_right['arrival']) {
            // Если поезд отправился с левой станции раньше, чем пришел на правую - значит он
            // доберется до переезда за время от левой станции
            $time = $train_schedule_right['arrival'] - $train_schedule_left['departure'];
        }
        $_avg_time_crossing_pass[] = $time;
        $avg_time_crossing_pass += $time;
    }
    $avg_time_crossing_pass = $avg_time_crossing_pass / count($_avg_time_crossing_pass);
    echo $avg_time_crossing_pass;
}