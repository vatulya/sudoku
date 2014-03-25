<?php

class My_WebSocket_Listener_Sudoku extends My_WebSocket_Listener_Abstract
{

    const LOG_PREFIX = '[LISTENER SUDOKU] ';

    /**
     * @var Application_Service_Game_Sudoku
     */
    protected $service;

    public function __construct()
    {
        $this->service = Application_Service_Game_Sudoku::getInstance();
    }

    public function onClientReceivedData(array $data = [])
    {
        if (empty($data['game_id']) || empty($data['action'])) {
            $this->getServer()->getLogger()->error(static::LOG_PREFIX . 'Wrong data');
            return false;
        }
        $gameId = $data['game_id'];
        $action = $data['action'] . 'Action';
        if (!method_exists($this, $action)) {
            $this->getServer()->getLogger()->error(static::LOG_PREFIX . 'Wrong action "' . $action . '"');
            return false;
        }

        unset($data['game_id'], $data['action']);
        $this->getServer()->getLogger()->debug(static::LOG_PREFIX . 'Call action "' . $action . '". Data: ' . Zend_Json::encode($data));
        try {
            $this->$action($gameId, $data);
        } catch (Exception $e) {
            $this->getServer()->getLogger()->error(static::LOG_PREFIX . 'Action error: ' . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @param $gameId
     * @param array $data
     * @return bool
     */
    protected function pingAction($gameId, array $data)
    {
        $this->service->load($gameId)->ping();
        return true;
    }

    /**
     * @param $gameId
     * @param array $data
     * @return bool
     */
    protected function checkFieldAction($gameId, array $data)
    {
        $game = $this->service->load($gameId);
        $errors = $this->service->checkGameSolution($game);
        $resolved = false;
        if (!is_array($errors)) {
            $resolved = (bool)$errors;
        }
        $data = [
            'errors'   => $errors,
            'resolved' => $resolved,
        ];
        $this->send('sudoku', 'checkField', $data);
    }

}