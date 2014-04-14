<?php

class My_WebSocket_Listener_Sudoku extends My_WebSocket_Listener_Abstract
{

    const LOG_PREFIX = '[LISTENER SUDOKU] ';

    const DATA_KEY_GAME_HASH = '_game_hash';
    const DATA_KEY_ACTION = '_action';
    const DATA_KEY_HASH = '_hash';

    /**
     * @var Application_Service_Game_Sudoku
     */
    protected $service;

    /**
     * @var Application_Model_Game_Sudoku
     */
    protected $game;

    protected $skipCheckBoardForActions = ['loadBoardAction'];

    public function __construct()
    {
        $this->service = Application_Service_Game_Sudoku::getInstance();
    }

    public function onClientReceivedDataFromClient(array $data = [])
    {
        if (
            empty($data[static::DATA_KEY_GAME_HASH])
            || empty($data[static::DATA_KEY_ACTION])
            || empty($data[static::DATA_KEY_HASH])
        ) {
            $this->getServer()->getLogger()->error(static::LOG_PREFIX . 'Wrong data');
            return false;
        }
        $gameHash = $data[static::DATA_KEY_GAME_HASH];
        $action = $data[static::DATA_KEY_ACTION] . 'Action';
        $hash = $data[static::DATA_KEY_HASH];
        if (!method_exists($this, $action)) {
            $this->getServer()->getLogger()->error(static::LOG_PREFIX . 'Wrong action "' . $action . '"');
            return false;
        }

        try {
            $userId = $this->getUserId();
            $this->game = $this->service->loadByUserIdAndGameHash($userId, $gameHash);
        } catch (Exception $e) {
            $this->getServer()->getLogger()->error(static::LOG_PREFIX . 'Wrong user or game. Error: ' . $e->getMessage());
            return false;
        }

        unset($data[static::DATA_KEY_GAME_HASH], $data[static::DATA_KEY_ACTION], $data[static::DATA_KEY_HASH]);
        $this->getServer()->getLogger()->debug(static::LOG_PREFIX . 'Call action "' . $action . '". Data: ' . Zend_Json::encode($data));
        try {
            $this->$action($data);
        } catch (Exception $e) {
            $this->getServer()->getLogger()->error(static::LOG_PREFIX . 'Action error: ' . $e->getMessage());
            return false;
        }
        if (!in_array($action, $this->skipCheckBoardForActions)) {
            if (!$this->service->checkBoard($this->game->getId(), $hash)) {
                $this->getServer()->getLogger()->error(static::LOG_PREFIX . 'Check Board error');
                $this->send('sudoku', 'forceRefresh', ['reason' => 'Synchronization error']);
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function loadBoardAction(array $data)
    {
        $data = $this->game->getParameters();
        $this->send('sudoku', 'loadBoard', $data, $this->getSystemData());
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function pingAction(array $data)
    {
        $this->game->ping();
        $this->send('sudoku', '', [], $this->getSystemData());
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function startAction(array $data)
    {
        $this->game->start();
        $this->send('sudoku', '', [], $this->getSystemData());
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function checkBoardAction(array $data)
    {
        $errors = $this->service->checkGameSolution($this->game);
        $resolved = false;
        if (!is_array($errors)) {
            $resolved = (bool)$errors;
        }
        $data = [
            'errors'   => $errors,
            'resolved' => $resolved,
        ];
        $this->send('sudoku', 'checkField', $data, $this->getSystemData());
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function setCellNumberAction(array $data)
    {
        $coords = !empty($data['coords']) ? $data['coords'] : '';
        $number = !empty($data['number']) ? $data['number'] : '';
        $this->game->setCellNumber($coords, $number);
        $this->send('sudoku', 'setCellNumber', [], $this->getSystemData());
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function clearBoardAction(array $data)
    {
        $this->game->clearBoard();
        $this->send('sudoku', 'clearBoard', [], $this->getSystemData());
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function undoMoveAction(array $data)
    {
        $this->game->undoMove();
        $this->send('sudoku', 'undoMove', [], $this->getSystemData());
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function redoMoveAction(array $data)
    {
        $this->game->redoMove();
        $this->send('sudoku', 'redoMove', [], $this->getSystemData());
        return true;
    }

    /**
     * @return array
     */
    protected function getSystemData()
    {
        $data = [];
        $moves = $this->game->getUndoRedoMoves();
        $data['gameHash'] = $this->game->getHash();
        $data['undoMove'] = $moves['undo'];
        $data['redoMove'] = $moves['redo'];
        $data['duration'] = $this->game->getDuration();
        return $data;
    }

}