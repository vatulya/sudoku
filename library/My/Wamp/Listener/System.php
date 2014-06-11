<?php

use Ratchet\ConnectionInterface;

class My_Wamp_Listener_System extends My_Wamp_Listener_Abstract
{

    const DATA_KEY_ACTION = '_action';
    const DATA_KEY_SESSION = '_session';

    public function __construct()
    {
        $this->service = Application_Service_Game_Sudoku::getInstance();
    }

    public function onMessage(ConnectionInterface $conn, My_Wamp_Response $response, array $data = [])
    {
        $this->setUser($conn);
        $this->setResponse($response);

        try {

            if (
                empty($data[static::DATA_KEY_ACTION])
                || empty($data[static::DATA_KEY_SESSION])
            ) {
                throw new Exception('Wrong request');
            }
            $action = $data[static::DATA_KEY_ACTION] . 'Action';
            $sessionId = $data[static::DATA_KEY_SESSION];
            if (!method_exists($this, $action)) {
                throw new Exception('Wrong Action');
            }

            $userId = $this->getUserId($sessionId);

            unset($data[static::DATA_KEY_ACTION], $data[static::DATA_KEY_SESSION]);
            $this->$action($data);

        } catch (\Exception $e) {
            $response->send(['error' => $e->getMessage()]);
            return false;
        }

        return true;
    }

    protected function stopScriptAction(array $data)
    {
        die('SCRIPT STOP by user\'s command');
    }

}