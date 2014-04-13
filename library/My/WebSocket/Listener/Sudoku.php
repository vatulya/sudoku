<?php

class My_WebSocket_Listener_Sudoku extends My_WebSocket_Listener_Abstract
{

    const LOG_PREFIX = '[LISTENER SUDOKU] ';

    const DATA_KEY_GAME_ID = '_game_id';
    const DATA_KEY_ACTION = '_action';
    const DATA_KEY_HASH = '_hash';

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
        if (
            empty($data[static::DATA_KEY_GAME_ID])
            || empty($data[static::DATA_KEY_ACTION])
            || empty($data[static::DATA_KEY_HASH])
        ) {
            $this->getServer()->getLogger()->error(static::LOG_PREFIX . 'Wrong data');
            return false;
        }
        $gameId = $data[static::DATA_KEY_GAME_ID];
        $action = $data[static::DATA_KEY_ACTION] . 'Action';
        $hash = $data[static::DATA_KEY_HASH];
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
        if (!$this->service->checkBoard($gameId, $hash)) {
            $this->getServer()->getLogger()->error(static::LOG_PREFIX . 'Check Board error');
            $this->send('sudoku', 'forceRefresh', ['reason' => 'Synchronization error']);
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
        $this->send('sudoku', '', [], $this->getSystemData($gameId));
        return true;
    }

    /**
     * @param $gameId
     * @param array $data
     * @return bool
     */
    protected function startAction($gameId, array $data)
    {
        $this->service->load($gameId)->start();
        $this->send('sudoku', '', [], $this->getSystemData($gameId));
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
        $this->send('sudoku', 'checkField', $data, $this->getSystemData($gameId));
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
        $this->send('sudoku', 'setCellNumber', [], $this->getSystemData($gameId));
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
        $this->send('sudoku', 'clearBoard', [], $this->getSystemData($gameId));
        return true;
    }

    /**
     * @param $gameId
     * @param array $data
     * @return bool
     */
    protected function undoMoveAction($gameId, array $data)
    {
        $this->service->load($gameId)->undoMove();
        $this->send('sudoku', 'undoMove', [], $this->getSystemData($gameId));
        return true;
    }

    /**
     * @param $gameId
     * @param array $data
     * @return bool
     */
    protected function redoMoveAction($gameId, array $data)
    {
        $this->service->load($gameId)->redoMove();
        $this->send('sudoku', 'redoMove', [], $this->getSystemData($gameId));
        return true;
    }

    /**
     * @param int $gameId
     * @return array
     */
    protected function getSystemData($gameId)
    {
        $data = [];
        $game = $this->service->load($gameId);
        $moves = $game->getUndoRedoMoves();
        $data['gameHash'] = $game->getHash();
        $data['undoMove'] = $moves['undo'];
        $data['redoMove'] = $moves['redo'];
        $data['duration'] = $game->getDuration();
        return $data;
    }

}