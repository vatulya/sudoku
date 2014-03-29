<?php

class My_WebSocket_Listener_Sudoku extends My_WebSocket_Listener_Abstract
{

    const LOG_PREFIX = '[LISTENER SUDOKU] ';

    const DATA_KEY_GAME_ID = '_game_id';
    const DATA_KEY_ACTION = '_action';

    /**
     * @var Application_Service_Game_Sudoku
     */
    protected $service;

    public function __construct()
    {
        $this->service = Application_Service_Game_Sudoku::getInstance();
    }

    public function onClientReceivedDataFromClient(array $data = [])
    {
        if (empty($data[static::DATA_KEY_GAME_ID]) || empty($data[static::DATA_KEY_ACTION])) {
            $this->getServer()->getLogger()->error(static::LOG_PREFIX . 'Wrong data');
            return false;
        }
        $gameId = $data[static::DATA_KEY_GAME_ID];
        $action = $data[static::DATA_KEY_ACTION] . 'Action';
        if (!method_exists($this, $action)) {
            $this->getServer()->getLogger()->error(static::LOG_PREFIX . 'Wrong action "' . $action . '"');
            return false;
        }

        unset($data[static::DATA_KEY_GAME_ID], $data[static::DATA_KEY_ACTION]);
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
    protected function checkBoardAction($gameId, array $data)
    {
        $errors = $this->service->checkGameSolution($this->service->load($gameId));
        $resolved = false;
        if (!is_array($errors)) {
            $resolved = (bool)$errors;
        }
        $data = [
            'errors'   => $errors,
            'resolved' => $resolved,
        ];
        $this->send('sudoku', 'checkField', $data);
        return true;
    }

    /**
     * @param $gameId
     * @param array $data
     * @return bool
     */
    protected function setCellNumberAction($gameId, array $data)
    {
        $coords = !empty($data['coords']) ? $data['coords'] : '';
        $number = !empty($data['number']) ? $data['number'] : '';
        $this->service->load($gameId)->setCellNumber($coords, $number);
        return true;
    }

    /**
     * @param $gameId
     * @param array $data
     * @return bool
     */
    protected function clearBoardAction($gameId, array $data)
    {
        $this->service->load($gameId)->clearBoard();
        return true;
    }

}