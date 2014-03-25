<?php

class My_WebSocket_Logger_FileConsole extends My_WebSocket_Logger_File
{

    public function log($string, $type = self::LEVEL_DEBUG)
    {
        $datetime = date('Y-m-d H:i:s');
        $group = array_search($type, $this->allowedLogMethods);
        $message = sprintf('[%s][%s] %s', $datetime, $group, $string);
        echo $message . "\n\r";
        parent::log($string, $type);
        return $this;
    }

}