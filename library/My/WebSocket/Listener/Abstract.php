<?php

use Ratchet\ConnectionInterface;

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
     * @var ConnectionInterface
     */
    protected $user;

    protected $response;

    protected static $userSessions = [];

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
        $data[static::DATA_KEY_MODULE] = $module;
        $data[static::DATA_KEY_ACTION] = $action;
        $data[static::DATA_KEY_SYSTEM] = $system;
        $this->getResponse()->send($data);
    }

    /**
     * @param ConnectionInterface $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return ConnectionInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        $sessionId = $this->getUserSessionId();
        if (empty(self::$userSessions[$sessionId])) {
            $userSessionsDbModel = new Application_Model_Db_User_Sessions();
            $userId = $userSessionsDbModel->getOne(['session_id' => $sessionId], ['created DESC']);
            $userId = $userId['user_id'];
            self::$userSessions[$sessionId] = $userId;
        } else {
            $userId = self::$userSessions[$sessionId];
        }
        return (int)$userId;
    }

    public function getUserSessionId()
    {
        $user = $this->getUser();
        $cookiesRows = $user->WebSocket->request->getHeader('cookie');
        $cookies = [];
        foreach ($cookiesRows as $cookie) {
            list ($key, $value) = explode('=', $cookie);
            $cookies[$key] = $value;
        }
        return $cookies[ini_get('session.name')];
    }

}