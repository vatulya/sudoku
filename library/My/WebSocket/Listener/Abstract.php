<?php

abstract class My_WebSocket_Listener_Abstract
{

    const DATA_KEY_MODULE = '_module';
    const DATA_KEY_ACTION = '_action';
    const DATA_KEY_SYSTEM = '_system';

    /**
     * @var \My_WebSocket_Server
     */
    protected $server;

    /**
     * @var My_WebSocket_User
     */
    protected $user;

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

    /**
     * @param string $module
     * @param string $action
     * @param array $data
     * @param array $system
     */
    protected function send($module, $action, $data, $system = [])
    {
        if ($user = $this->getUser()) {
            $data[static::DATA_KEY_MODULE] = $module;
            $data[static::DATA_KEY_ACTION] = $action;
            $data[static::DATA_KEY_SYSTEM] = $system;
            $this->getServer()->send($user, $data);
        }
    }

    /**
     * @param \My_WebSocket_Server $server
     * @return $this
     */
    public function setServer(My_WebSocket_Server $server)
    {
        $this->server = $server;
        return $this;
    }

    /**
     * @return \My_WebSocket_Server
     * @throws \Exception
     */
    public function getServer()
    {
        if (is_null($this->server)) {
            throw new Exception('Property "server" is empty. You forgot call setServer?');
        }
        return $this->server;
    }

    /**
     * @param \My_WebSocket_User $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return \My_WebSocket_User
     */
    public function getUser()
    {
        return $this->user;
    }

}