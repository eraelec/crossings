<?php
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../app/globals.php';

/*
 * Постулируем, что переезд может находится между двумя станциями,
 * т.е. иметь станцию слева от себя и справа.
 * Станция, справа от переезда - это основное депо, например Финсляндский вокзал в СПб.
 * Так же каждый ЖД рейс может быть направлением "слева_на_право" и "справа_на_лево".
 * Примем, что "справа_на_лево" - означает движение поезда из депо "куда-то".
 */

$train_number_to_id = array(); // Массив-кэш для ID ЖД рейсов

$_trains = $db->get('trains');
foreach ($_trains as $train) {
    $train_number_to_id[$train['number']] = $train['id'];
}

$req_copyright = "https://api.rasp.yandex.net/v1.0/copyright/?" .
    "apikey=" . YANDEX_RASP_KEY .
    "&format=json";

$req_rasp_for_station = "https://api.rasp.yandex.net/v1.0/schedule/?" .
    "apikey=" . YANDEX_RASP_KEY .
    "&format=json" .
    "&station=%s" .
    "&lang=ru" .
    "&date=" . date('Y-m-d');

if ($curl = curl_init()) {
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

    $stations = $db
        ->where('NOT yandex_id IS NULL')
        ->where('NOT osm_id IS NULL')
//        ->where('id', 38900)
//        ->orWhere('id', 38525)
        ->where('(schedule_updated IS NULL OR schedule_updated < DATE_SUB(NOW(), INTERVAL 1 MONTH))')
        ->get('railway_division_station', 10);

    foreach ($stations as $station) {
        $station['yandex_id'] = 's' . $station['yandex_id'];
        curl_setopt($curl, CURLOPT_URL, sprintf($req_rasp_for_station, $station['yandex_id']));
        $out = curl_exec($curl);
        $rasp = json_decode($out);
        if ($rasp && isset($rasp->schedule) && count($rasp->schedule) > 0) {
            $db->where('station', $station['id'])->delete('schedule'); // очистим существующее расписание по этой станции
            foreach ($rasp->schedule as $r) {
                if (!isset($train_number_to_id[$r->thread->number])) {
                    $train_number_to_id[$r->thread->number] = $db->insert('trains', array(
                        'uid'           =>  strval($r->thread->uid),
                        'number'        =>  strval($r->thread->number),
                        'title'         =>  strval($r->thread->title),
                        'short_title'   =>  strval($r->thread->short_title),
                        'days'          =>  strval($r->days),
                        'stops'         =>  strval($r->stops),
                        'is_fuzzy'      =>  intval($r->is_fuzzy),
                        'transport_type'=>  strval($r->thread->transport_type),
                        'express_type'  =>  isset($r->express_type) ? strval($r->express_type) : null
                    ));
                }
                $sh_id = $db->insert('schedule', array(
                    'station'       =>  $station['id'],
                    'train'         =>  $train_number_to_id[$r->thread->number],
                    'arrival'       =>  date('H:i:s', strtotime($r->arrival)),
                    'departure'     =>  date('H:i:s', strtotime($r->departure))
                ));
                if (!$sh_id) {
                    echo $db->getLastError();
                }
            }
        }
        $db
            ->where('id', $station['id'])
            ->update('railway_division_station', [
            'schedule_updated' => $db->now()
        ]);
    }
    curl_close($curl);
}