<?php

class CrossingControllerMultiple extends Controller
{
    /**
     * @url GET /crossing/closest
     * @throws Exception
     */
    public function getClosest()
    {
        $_REQUEST['lat'] = (float)$_REQUEST['lat'];
        $_REQUEST['lng'] = (float)$_REQUEST['lng'];

        $return = array(
            'ok'    =>  0,
            'error' =>  'неизвестная ошибка'
        );

        $crossing = $this->db->rawQuery("SELECT id, title, station, station_left, station_left_distance, station_right, station_right_distance, "
            . " ST_Distance_Sphere(loc, POINT(?, ?)) distance FROM crossings ORDER BY distance LIMIT 1", array(
            floatval($_REQUEST['lng']),
            floatval($_REQUEST['lat'])
        ));

        if ($crossing && $crossing[0]) {
            $crossing = $crossing[0];
            $return['ok'] = 1;
            $return['id'] = $crossing['id'];
            $return['title'] = $crossing['title'];
            if (isset($_REQUEST['with_schedule'])) {
                if ($crossing['station'] > 0) {
                    // Переезд находится непосредственно на станции - опираемся на ее расписание
                    $times = array();
                    if ($crossing['station'] == 1) {
                        $allegro = array(
                            '6:50',
                            '8:12',
                            '8:15',
                            '10:40',
                            '14:06',
                            '14:20',
                            '15:40',
                            '18:25',
                            '19:20',
                            '21:50',
                            '23:20',
                            '20:51',
                        );
                        foreach ($allegro as $a) {
                            $a = date('H:i:00', strtotime($a));
                            $start = strtotime(date('Y-m-d')." ". $a) - 60*10;
                            $end = strtotime(date('Y-m-d')." ". $a) + 60;
                            $times[] = array(strtotime(date('Y-m-d')." ". $a), $start, $end, 'allegro');
                        }
                    }
                    $schedule = $this->db->where('station', $crossing['station'])->orderBy('departure')->get('schedule');
                    if ($schedule) {
                        foreach ($schedule as $s) {
                            $start = strtotime(date('Y-m-d')." ". $s['arrival']) - 60;
                            $end = strtotime(date('Y-m-d')." ". $s['departure']) + 60;
                            $times[] = array(strtotime(date('Y-m-d')." ". $s['arrival']), $start, $end, $s['transport_type']);
                        }
                    }
                    $return['schedule'] = $times;
                } elseif ($crossing['station_left'] > 0 && $crossing['station_left_distance'] > 0 &&
                    $crossing['station_right'] > 0 && $crossing['station_right_distance'] > 0) {
                    // Переезд находится между двумя станциями
                    $times = array();
                    $schedule_left = $this->db
                        ->join('trains t', 't.id = s.train', 'LEFT')
                        ->where('s.station', $crossing['station_left'])
                        ->orderBy('s.departure')
                        ->get('schedule s', null, 't.number, s.arrival, s.departure, t.transport_type');
                    $schedule_right = $this->db
                        ->join('trains t', 't.id = s.train', 'LEFT')
                        ->where('s.station', $crossing['station_right'])
                        ->orderBy('s.departure')
                        ->get('schedule s', null, 't.number, s.arrival, s.departure, t.transport_type');
                    $trains_left = array();
                    $trains_right = array();
//                    $trains = array();
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
                    foreach ($trains_left as $train_number => $train_schedule_left) {
                        $train_schedule_right = $trains_right[$train_number];
                        $time = 0;
                        $delay = 0;
                        $type = '';
                        if ($train_schedule_right['departure'] < $train_schedule_left['arrival']) {
                            // Если поезд отправился с правой станции раньше, чем пришел на левую - значит он
                            // доберется до переезда за время от правой станции
                            $time = $train_schedule_right['departure'];// + $crossing['station_right_time'];
                            $type = $train_schedule_right['transport_type'];
                            // надо прибавить ко времени отправления часть времени проезда всего прогона между станциями
                            // пропорция вычисляется из расстояний между левой и правой станцией
                            $right_distance_proportion = $crossing['station_right_distance'] / ($crossing['station_left_distance'] + $crossing['station_right_distance']);
                            $delay = $train_schedule_left['arrival'] - $train_schedule_right['departure'];
                            $delay *= $right_distance_proportion;
                        } elseif ($train_schedule_left['departure'] < $train_schedule_right['arrival']) {
                            // Если поезд отправился с левой станции раньше, чем пришел на правую - значит он
                            // доберется до переезда за время от левой станции
                            $time = $train_schedule_left['departure'];// + $crossing['station_left_time'];
                            $type = $train_schedule_left['transport_type'];
                            $left_distance_proportion = $crossing['station_left_distance'] / ($crossing['station_left_distance'] + $crossing['station_right_distance']);
                            $delay = $train_schedule_right['arrival'] - $train_schedule_left['departure'];
                            $delay *= $left_distance_proportion;
                        }
                        $time += $delay;
                        $time = date('H:i:s', $time);
                        $start = strtotime(date('Y-m-d')." ". $time) - 60;
                        $end = strtotime(date('Y-m-d')." ". $time) + 60;
                        $times[] = array(strtotime(date('Y-m-d')." ". $time), $start, $end, $type);
                    }
                    $return['schedule'] = $times;
                }
            }
        } else {
            $return['error'] = "не удалось определить ближайший";
        }

        if ($return['ok'] != 0) {
            unset($return['error']);
        }
        return $return;
    }
}