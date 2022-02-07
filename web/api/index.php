<?php
session_start();
include_once __DIR__ . '/../../vendor/autoload.php';
include_once __DIR__ . '/../../app/globals.php';

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
    header('Access-Control-Allow-Headers: accept, content-type, x-xsrf-token');
    exit(0);
}

require_once __DIR__ . '/../../src/Controller/Controller.php';
require_once __DIR__ . '/../../src/Controller/CrossingController.php';

header('Content-Type: application/json');

$server = new \Jacwright\RestServer\RestServer();
$server->addClass('CrossingController');
$server->handle();