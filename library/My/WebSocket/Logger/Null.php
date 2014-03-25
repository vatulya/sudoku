<?php

class My_WebSocket_Logger_Null extends My_WebSocket_Logger_Abstract
{

    public function log($string, $type = self::LEVEL_DEBUG)
    {
        return $this;
    }

}