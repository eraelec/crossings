<?php
include_once __DIR__ . '/../../vendor/autoload.php';
include_once __DIR__ . '/../../app/globals.php';
$station = array();
$station = $db
    ->map('id')
    ->where('division', 3)
    ->where('lat IS NOT NULL')
    ->where('lng IS NOT NULL')
    ->where('title NOT LIKE "% км%"')
    ->get('railway_division_station', null, 'id, title, lat, lng');

$rail = $db
    ->get('railway_division_station_neighbor');


$crossings = $db
    ->map('id')
    ->join('crossings_manual cm', 'c.osm_id = cm.osm_id', 'LEFT')
//    ->where('cm.disabled IS NULL')
    ->orderBy('station_closest_distance')
    ->get('crossings c', null, 'c.id, c.lat, c.lng, c.title');
?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <link rel="stylesheet" href="/assets/css/main.css" />
    <script src="/bower_components/jquery/dist/jquery.min.js" type="text/javascript"></script>
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
</head>
<body>
<div id="map" style="width: 1900px; height: 1000px;"></div>
</body>
<script>
    var rail = JSON.parse('<?php echo json_encode($rail); ?>');
    var station = JSON.parse('<?php echo json_encode($station); ?>');
    var crossings = JSON.parse('<?php echo json_encode($crossings); ?>');
    var myMap;
    var placemark;
    ymaps.ready(init);
    function init () {
        var geoObjects = [], preopen_placemark = false,
            preopen_uid = 0;
        // Создание экземпляра карты и его привязка к контейнеру с
        // заданным id ("eq-map").
        myMap = new ymaps.Map('map', {
            // При инициализации карты обязательно нужно указать
            // её центр и коэффициент масштабирования.
            center: [59.82701312, 30.31519104],
            zoom: 9,
            controls: ['zoomControl', 'typeSelector']
        });

        clusterer = new ymaps.Clusterer({
            preset: 'islands#blueClusterIcons',
            groupByCoordinates: false,
            clusterDisableClickZoom: true,
            clusterHideIconOnBalloonOpen: false,
            geoObjectHideIconOnBalloonOpen: false
        });
        clusterer.createCluster = function (center, geoObjects) {
            // Создаем метку-кластер с помощью стандартной реализации метода.
            var clusterPlacemark = ymaps.Clusterer.prototype.createCluster.call(this, center, geoObjects),
                geoObjects = clusterPlacemark.getGeoObjects(),
                preset = 'islands#blueClusterIcons';
            // Проверим - нет ли в наших метках красной
            geoObjects.forEach(function(entry){
                if (entry.options.get('preset') == 'islands#redCircleIcon') {
                    // Нашли - тогда сами раскрасимся в красный
                    preset = 'islands#redClusterIcons';
                    return 1;
                }
            });
            clusterPlacemark.options.set('preset', preset);
            return clusterPlacemark;
        };

        getStationPointData = function (index) {
            return {
                balloonContentHeader: station[index].title
            };
        };
        getStationPointOptions = function (index) {
            var style = 'islands#blueCircleIcon';
            return {
                preset: style
            };
        };

        geoObjects = [];

        // Добавим станции
        var i = 0;
        $.each(station, function(){
            geoObjects[i] = new ymaps.Placemark([this.lat, this.lng], getStationPointData(this.id), getStationPointOptions(this.id));
            i++;
        });



        getCrossingPointData = function (index) {
            return {
                balloonContentHeader: crossings[index].title
            };
        };
        getCrossingPointOptions = function (index) {
            var style = 'islands#redCircleIcon';
            return {
                preset: style
            };
        };
        // Добавим переезды
        $.each(crossings, function(){
            geoObjects[i] = new ymaps.Placemark([this.lat, this.lng], getCrossingPointData(this.id), getCrossingPointOptions(this.id));
            i++;
        });

        clusterer.add(geoObjects);
        myMap.geoObjects.add(clusterer);

        // Нарисуем линки
        $.each(rail, function(){
            station_from = station[this.station];
            station_to = station[this.neighbor];
            if (typeof station_from !== typeof undefined && typeof station_to !== typeof undefined) {
                var myPolyline = new ymaps.Polyline([
                    // Указываем координаты вершин ломаной.
                    [station_from.lat, station_from.lng],
                    [station_to.lat, station_to.lng]
                ], {
                    balloonContent: ''
                }, {
                    strokeColor: "#1f98ff",
                    // Ширина линии.
                    strokeWidth: 4,
                    // Коэффициент прозрачности.
                    strokeOpacity: 1
                });

                myMap.geoObjects.add(myPolyline);
            } else {
                console.log('ERROR: undefined eq id ' + (typeof station_from == typeof undefined ? this.station_from : this.station_to));
            }
        });

        // Открываем запрошеную точку
        if (preopen_placemark !== false) {
            /*myMap.setCenter(geoObjects[preopen_placemark].geometry.getBounds()[0], 15, {
             checkZoomRange: true, //контролируем доступность масштаба

             });*/
            var coords = geoObjects[preopen_placemark].geometry.getCoordinates();
            placemark = geoObjects[preopen_placemark];
            myMap.zoomRange.get(coords).then(function (range) {
                myMap.setCenter(coords, range[1]);

                // Откроем балун на третьей метке в массиве.
                var objectState = clusterer.getObjectState(geoObjects[preopen_placemark]);
                if (objectState.isClustered) {
                    // Если метка находится в кластере, выставим ее в качестве активного объекта.
                    // Тогда она будет "выбрана" в открытом балуне кластера.
                    objectState.cluster.state.set('activeObject', geoObjects[preopen_placemark]);
                    clusterer.balloon.open(objectState.cluster);
                } else if (objectState.isShown) {
                    // Если метка не попала в кластер и видна на карте, откроем ее балун.
                    geoObjects[preopen_placemark].balloon.open();
                }
            });
        } else {
            // Выставляем масштаб карты чтобы были видны все группы.
            myMap.setBounds(clusterer.getBounds(), {
                checkZoomRange: true
            });
        }

    }
</script>
</html>