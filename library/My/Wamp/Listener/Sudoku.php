<?php

use Ratchet\ConnectionInterface;

class My_Wamp_Listener_Sudoku extends My_Wamp_Listener_Abstract
{

    const DATA_KEY_GAME_HASH = '_game_hash';
    const DATA_KEY_ACTION = '_action';
    const DATA_KEY_HASH = '_hash';
    const DATA_KEY_SESSION = '_session';

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

    public function onMessage(ConnectionInterface $user, My_Wamp_Response $response, array $data = [])
    {
        $this->setUser($user);
        $this->setResponse($response);

        try {

            if (
                empty($data[static::DATA_KEY_GAME_HASH])
                || empty($data[static::DATA_KEY_ACTION])
                || empty($data[static::DATA_KEY_HASH])
                || empty($data[static::DATA_KEY_SESSION])
            ) {
                throw new Exception('Wrong request');
            }
            $gameHash = $data[static::DATA_KEY_GAME_HASH];
            $action = $data[static::DATA_KEY_ACTION] . 'Action';
            $hash = $data[static::DATA_KEY_HASH];
            $sessionId = $data[static::DATA_KEY_SESSION];
            if (!method_exists($this, $action)) {
                throw new Exception('Wrong Action');
            }

            $userId = $this->getUserId($sessionId);
            $this->game = $this->service->loadByUserIdAndGameHash($userId, $gameHash);

            unset($data[static::DATA_KEY_GAME_HASH], $data[static::DATA_KEY_ACTION], $data[static::DATA_KEY_HASH], $data[static::DATA_KEY_SESSION]);
            $this->$action($data);

            if (!in_array($action, $this->skipCheckBoardForActions)) {
                if (!$this->service->checkBoard($this->game->getId(), $hash)) {
                    $this->send('sudoku', 'forceRefresh', ['reason' => 'Synchronization error']);
                    throw new Exception('Synchronization error');
                }
            }

        } catch (\Exception $e) {
            $response->send(['error' => $e->getMessage()]);
            return false;
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
    protected function pauseAction(array $data)
    {
        $this->game->pause();
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
    protected function setCellAction(array $data)
    {
        $this->service->applyGameState($this->game, $data);
        $this->send('sudoku', 'setCell', [], $this->getSystemData());
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
        $this->service->applyGameState($this->game, $data, Application_Model_Db_Sudoku_Logs::ACTION_TYPE_UNDO);
        $this->send('sudoku', 'undoMove', [], $this->getSystemData());
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function redoMoveAction(array $data)
    {
        $this->service->applyGameState($this->game, $data, Application_Model_Db_Sudoku_Logs::ACTION_TYPE_REDO);
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
        $data['microtime'] = microtime(true);
        return $data;
    }

}