<?php
use \Jacwright\RestServer\RestException;

class Controller
{
    /**
     * @var /MysqliDb $db
     */
    public $db;

    private $user;
    private $config;

    private $json;

    private $resultRowLimit;

    private function forbidden() {
        header('HTTP/1.0 403 Forbidden');
        exit();
    }

    private function bad() {
        header('HTTP/1.0 400 Bad Request');
        exit();
    }

    public function __construct()
    {
        global $db;

        $this->db = &$db;
    }


    public function getResultRowLimit() {
        return $this->resultRowLimit;
    }

    public function getJsonRequest() {
        return $this->json;
    }

    public function constructResult($code, $message = 'Неизвестная ошибка') {
        return array(
            'result'    => array(
                'code'      =>  $code,
                'message'   =>  $message
            )
        );
    }

    public function authorize()
    {
        return true;
    }
}