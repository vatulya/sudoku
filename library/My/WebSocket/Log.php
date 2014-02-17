<?php

class My_WebSocket_Log extends My_WebSocket_ListenerAbstract
{

    protected $filename;

    public function __construct($filename)
    {
        if (!file_exists($filename)) {
            throw new Exception('Error! File "' . $filename . '" doesn\'t exist');
        }
        if (!is_writable($filename)) {
            throw new Exception('Error! File "' . $filename . '" isn\'t writable');
        }
        $this->filename = $filename;
    }

    public function __call($method, $arguments)
    {
        parent::__call($method, $arguments);
        array_shift($arguments); // remove My_WebSocket_Server
        $this->writeLog($method, $arguments);
    }

    public function writeLog($event, array $date)
    {
        $event = substr($event, 2);
        $string = $event . '. Data: ' . print_r($date, true);
        file_put_contents($this->filename, $string, FILE_APPEND);
        return $this;
    }

}