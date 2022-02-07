<?php
define('DB_HOST', getenv('DB_HOST'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWD', getenv('DB_PASSWD'));
define('DB_NAME', getenv('DB_NAME'));

define('YANDEX_RASP_KEY', getenv("YANDEX_RASP_KEY"));

try {
    $db = new MysqliDb(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
} catch (Exception $e) {
    print $e->getMessage();
}
