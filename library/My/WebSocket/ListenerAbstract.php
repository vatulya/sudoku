<?php

abstract class My_WebSocket_ListenerAbstract
{

    public function __call($method, $arguments)
    {
        if (!isset($arguments[0]) || !$arguments[0] instanceof My_WebSocket_Server) {
            throw new Exception('Error! Wrong call WebServer listener\'s method "' . $method . '"');
        }
        /** @var My_WebSocket_Server $server */
        $server = $arguments[0];
        $event = lcfirst(substr($method, 2));
        if (!in_array($event, $server->getAllowedEvents())) {
            throw new Exception('Error! Wrong WebServer listener event "' . $event. '"');
        }
        if (method_exists($this, $method)) {
            call_user_func([$this, $method], $arguments);
        }
        return $this;
    }

}