<?php

class CrossingController extends Controller
{
    /**
     * @url GET /crossing/closest
     * @throws Exception
     */
    public function getClosest()
    {
        $_REQUEST['lat'] = (float)$_REQUEST['lat'];
        $_REQUEST['lng'] = (float)$_REQUEST['lng'];

        $needId = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : null;

        $return = array(
            'ok'    =>  0,
            'error' =>  'неизвестная ошибка'
        );

        if ($_REQUEST['lat'] > 0 && $_REQUEST['lng'] > 0) {

            $crossings = $this->db->rawQuery("SELECT c.id, c.osm_id, c.title, c.station_closest, c.station_closest_distance, " .
                "c.lat, c.lng, " .
                "ST_Distance_Sphere(c.loc, POINT(?, ?)) distance " .
                "FROM crossings c " .
                "LEFT JOIN crossings_manual cm ON (c.osm_id = cm.osm_id) " .
                "WHERE cm.disabled IS NULL " .
                "ORDER BY distance LIMIT 6", array(
                floatval($_REQUEST['lng']),
                floatval($_REQUEST['lat'])
            ));
            $crossing = null;


            if ($needId) {
                foreach ($crossings as $_) {
                    if ($_['id'] == $needId)
                        $crossing = $_;
                }
            }
            if (!$crossing) {
                $crossing = $crossings[0];
            }


            if ($crossings && $crossing) {


                $return['ok'] = 1;

                $return['id'] = $crossing['id'];
                $return['distance'] = round($crossing['distance']);
                $return['osm_id'] = $crossing['osm_id'];
                $return['title'] = $crossing['title'];
                $return['lat'] = $crossing['lat'];
                $return['lng'] = $crossing['lng'];

                if (isset($_REQUEST['with_schedule']) && $crossing['station_closest'] > 0) {
                    $station_closest = $this->db->where('id', $crossing['station_closest'])->getOne('railway_division_station');
                    if ($station_closest) {
                        $return['station_closest'] = $station_closest['id'];
                        $return['station_closest_distance'] = $crossing['station_closest_distance'];
                        // Переезд находится непосредственно на станции - опираемся на ее расписание
                        $times = array();
//                    if ($crossing['station'] == 1) {
//                        $allegro = array(
//                            '6:50',
//                            '8:12',
//                            '8:15',
//                            '10:40',
//                            '14:06',
//                            '14:20',
//                            '15:40',
//                            '18:25',
//                            '19:20',
//                            '21:50',
//                            '23:20',
//                            '20:51',
//                        );
//                        foreach ($allegro as $a) {
//                            $a = date('H:i:00', strtotime($a));
//                            $start = strtotime(date('Y-m-d')." ". $a) - 60*10;
//                            $end = strtotime(date('Y-m-d')." ". $a) + 60;
//                            $times[] = array(strtotime(date('Y-m-d')." ". $a), $start, $end, 'allegro');
//                        }
//                    }
                        $schedule = $this->db
                            ->where('station', $station_closest['id'])
                            ->orderBy('departure')
                            ->get('schedule');
                        if ($schedule) {
                            foreach ($schedule as $s) {

                                $diff = abs(strtotime($s['arrival']) - strtotime($s['departure']));

                                if ($diff <= 60*5) { // && $return['station_closest_distance'] < 300
                                    // train goes through station, seems to crossing would be closed all time
                                    $start = strtotime(date('Y-m-d') . " " . $s['arrival']) - 60;
                                    $end = strtotime(date('Y-m-d') . " " . $s['departure']) + 60;

                                    $times[$s['arrival']] = array(strtotime(date('Y-m-d') . " " . $s['arrival']), $start, $end, 'suburban', $diff);
                                } else {
                                    $start = strtotime(date('Y-m-d') . " " . $s['arrival']) - 60;
                                    $end = strtotime(date('Y-m-d') . " " . $s['arrival']) + 60;

                                    $times[$s['arrival']] = array(strtotime(date('Y-m-d') . " " . $s['arrival']), $start, $end, 'suburban', $diff);

                                    $start = strtotime(date('Y-m-d') . " " . $s['departure']) - 60;
                                    $end = strtotime(date('Y-m-d') . " " . $s['departure']) + 60;
                                    $times[$s['departure']] = array(strtotime(date('Y-m-d') . " " . $s['departure']), $start, $end, 'suburban', $diff);
                                }



                            }
                        }
                        // $times
                        $currentTime = date('H:i:s');

                        $schedule = [];

                        foreach ($times as $time => $data) {
//                            if ($time > $currentTime)
                                $schedule[$time] = $data;
                        }

                        foreach ($times as $time => $data) {
                            $data[0] += 60*60*24;
                            $data[1] += 60*60*24;
                            $data[2] += 60*60*24;
//                            if ($time < $currentTime)
                                $schedule['next ' . $time] = $data;
                        }
                        $return['schedule'] = $schedule;
                    }
                }
                if (count($crossings)) {
                    $return['list'] = [];
                    foreach ($crossings as $crossing) {
                        $return['list'][] = [
                            'id' => $crossing['id'],
                            'title' => $crossing['title'],
                            'distance' => round($crossing['distance']),
                            'lat' => $crossing['lat'],
                            'lng' => $crossing['lng']

                        ];
                    }
                }
            } else {
                $return['error'] = "не удалось определить ближайший";
            }
        } else {
            $return['error'] = "не переданы координаты";
        }

        if ($return['ok'] != 0) {
            unset($return['error']);
        }
        return $return;
    }
}