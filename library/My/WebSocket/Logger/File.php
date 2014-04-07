<?php

class My_WebSocket_Logger_File extends My_WebSocket_Logger_Abstract
{

    protected $filename;

    public function __construct($filename)
    {
        if (!file_exists($filename)) {
            if (!fopen($filename, 'w+')) {
                throw new Exception('Error! File "' . $filename . '" doesn\'t exist and Script can\'t create');
            }
        }
        if (!is_writable($filename)) {
            throw new Exception('Error! File "' . $filename . '" isn\'t writable');
        }
        $this->filename = $filename;
    }

    public function log($string, $type = self::LEVEL_DEBUG)
    {
        $datetime = date('Y-m-d H:i:s');
        $group = array_search($type, $this->allowedLogMethods);
        $message = sprintf('[%s][%s] %s', $datetime, $group, $string) . "\r\n";
        file_put_contents($this->filename, $message, FILE_APPEND);
        return $this;
    }

}