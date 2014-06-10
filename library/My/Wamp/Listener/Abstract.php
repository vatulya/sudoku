<?php

use Ratchet\ConnectionInterface;

abstract class My_Wamp_Listener_Abstract
{

    const DATA_KEY_MODULE = '_module';
    const DATA_KEY_ACTION = '_action';
    const DATA_KEY_SYSTEM = '_system';

    /**
     * @var ConnectionInterface
     */
    protected $user;

    /**
     * @var My_Wamp_Response
     */
    protected $response;

    protected static $userSessions = [];

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
     * @param My_Wamp_Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return My_Wamp_Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $sessionId
     * @return int
     */
    public function getUserId($sessionId)
    {
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

}