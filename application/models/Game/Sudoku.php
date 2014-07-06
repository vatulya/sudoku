<?php

/**
 * Class Application_Model_Game_Sudoku
 *
 * @method Application_Service_Game_Sudoku getService()
 */
class Application_Model_Game_Sudoku extends Application_Model_Game_Abstract
{

    const PARAMETER_KEY_OPEN_CELLS    = 'openCells';
    const PARAMETER_KEY_CHECKED_CELLS = 'checkedCells';
    const PARAMETER_KEY_MARKED_CELLS  = 'markedCells';

    protected static $modelDb = 'Sudoku_Games';
    protected static $modelDbLogs = 'Sudoku_Logs';

    protected static $service = 'Sudoku';

    /**
     * @var int
     */
    protected $multiplayerId = 0;

    protected function init()
    {
        parent::init();
        $this->multiplayerId = (int)$this->data['multiplayer_id'];
    }

    /**
     * @return int
     */
    public function getMultiplayerId()
    {
        return $this->multiplayerId;
    }

    /**
     * @return bool
     */
    public function isMultiplayer()
    {
        return (bool)$this->getMultiplayerId();
    }

    /**
     * @param array $cells coords => [number => 1, marks => [1,2,3]]
     * @param string $logAction like Application_Model_Db_Sudoku_Logs::ACTION_TYPE_SET_CELLS
     * @return bool
     */
    public function setCells(array $cells, $logAction = Application_Model_Db_Sudoku_Logs::ACTION_TYPE_SET_CELLS)
    {
        $oldParameters = $newParameters = [];

        $openCells    = $this->getParameter(static::PARAMETER_KEY_OPEN_CELLS) ?: [];
        $checkedCells = $this->getParameter(static::PARAMETER_KEY_CHECKED_CELLS) ?: [];
        $markedCells  = $this->getParameter(static::PARAMETER_KEY_MARKED_CELLS) ?: [];

        foreach ($cells as $coords => $data) {
            if (!$this->getService()->checkCoords($coords)) {
                // TODO: error
                unset($cells[$coords]);
                continue;
            }
            if (isset($openCells[$coords])) {
                // TODO: error
                unset($cells[$coords]);
                continue;
            }
            if (isset($data['number']) && !$this->getService()->checkNumber($data['number'])) {
                // TODO: error
                unset($cells[$coords]);
                continue;
            }
            if (isset($data['marks'])) {
                $data['marks'] = array_filter($data['marks']);
                foreach ($data['marks'] as $mark) {
                    if (!$this->getService()->checkNumber($mark)) {
                        // TODO: error
                        unset($cells[$coords]);
                        continue 2;
                    }
                }
            }

            $newParameters[$coords] = $oldParameters[$coords] = [
                'number' => 0,
                'marks'  => [],
            ];
            if (isset($checkedCells[$coords])) {
                $oldParameters[$coords]['number'] = $checkedCells[$coords];
            }
            if (isset($markedCells[$coords])) {
                $oldParameters[$coords]['marks'] = $markedCells[$coords];
            }
            $newParameters[$coords]['number'] = isset($data['number']) ? $data['number'] : $oldParameters[$coords]['number'];
            $newParameters[$coords]['marks']  = isset($data['marks'])  ? $data['marks']  : $oldParameters[$coords]['marks'];

            // Set cells
            $checkedCells[$coords] = $newParameters[$coords]['number'];
            $markedCells[$coords]  = $newParameters[$coords]['marks'];
        }

        if (!empty($newParameters) || !empty($oldParameters)) {
            $checkedCells = array_filter($checkedCells);
            $markedCells = array_filter($markedCells);
            $this->setParameter(static::PARAMETER_KEY_CHECKED_CELLS, $checkedCells);
            $this->setParameter(static::PARAMETER_KEY_MARKED_CELLS, $markedCells);

            $this->addLog($logAction, $oldParameters, $newParameters);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function clearBoard()
    {
        $checkedCells = $this->getParameter(static::PARAMETER_KEY_CHECKED_CELLS) ?: [];
        $markedCells  = $this->getParameter(static::PARAMETER_KEY_MARKED_CELLS) ?: [];
        $newParameters = $oldParameters = [];

        $cells = array_unique(array_merge(array_keys($checkedCells), array_keys($markedCells)));
        foreach ($cells as $cell) {
            $oldParameters[$cell] = $newParameters = [
                'number' => 0,
                'marks'  => [],
            ];
            if (isset($checkedCells[$cell])) {
                $oldParameters[$cell]['number'] = $checkedCells[$cell];
            }
            if (isset($markedCells[$cell])) {
                $oldParameters[$cell]['marks'] = $markedCells[$cell];
            }
        }

        $this->setParameter(static::PARAMETER_KEY_CHECKED_CELLS, []);
        $this->setParameter(static::PARAMETER_KEY_MARKED_CELLS, []);

        $this->addLog(Application_Model_Db_Sudoku_Logs::ACTION_TYPE_CLEAR_BOARD, $oldParameters, $newParameters);
        return true;
    }

    /************** BOARD HASH *******************/

    /**
     * @return string
     */
    public function getBoardHash()
    {
        $board = $this->getParameter(static::PARAMETER_KEY_OPEN_CELLS) ?: [];
        $board += $this->getParameter(static::PARAMETER_KEY_CHECKED_CELLS) ?: [];
        $board += $this->getService()->getEmptyBoard();
        $board = array_map(function ($value) {
            return $value ? (string)$value : '0';
        }, $board);
        ksort($board);
        $board = array_values($board);
        $board = implode('', $board);
        $hash = md5($board);
        return $hash;
    }

    /************** UNDO REDO **************/

    /**
     * @return array
     */
    public function getUndoRedoMoves()
    {
        $moves = [
            'undo' => [],
            'redo' => [],
        ];
        $logs = $this->getLogs();
        $undos = 0;
        $redos = 0;
        $exit = false;
        foreach ($logs as $log) {
            switch ($log['action_type']) {
                case Application_Model_Db_Sudoku_Logs::ACTION_TYPE_SET_CELLS:
                    if (!empty($moves['undo'])) {
                        $exit = 1;
                        break;
                    }
                    if ($undos > 0) {
                        $undos--;
                    } else {
                        if (empty($moves['undo'])) {
                            $moves['undo'] = $log['old_parameters'];
                            $exit = true;
                        }
                    }
                    break;

                case Application_Model_Db_Sudoku_Logs::ACTION_TYPE_CLEAR_BOARD:
                    if (!empty($moves['undo'])) {
                        $exit = 1;
                        break;
                    }
                    if ($undos > 0) {
                        $undos--;
                    } else {
                        if (empty($moves['undo'])) {
                            $moves['undo'] = $log['old_parameters'];
                        }
                    }
                    break;

                case Application_Model_Db_Sudoku_Logs::ACTION_TYPE_UNDO:
                    $undos++;
                    if ($redos > 0) {
                        $redos--;
                    } else {
                        if (empty($moves['redo'])) {
                            $moves['redo'] = $log['old_parameters'];
                        }
                    }
                    break;

                case Application_Model_Db_Sudoku_Logs::ACTION_TYPE_REDO:
                    $redos++;
                    if ($undos > 0) {
                        $undos--;
                    } else {
                        if (empty($moves['undo'])) {
                            $moves['undo'] = $log['old_parameters'];
                        }
                    }
                    break;

                default:
                    break;
            }
            if ($exit || (!empty($moves['undo']) && !empty($moves['redo']))) {
                break;
            }
        }
        try {
            $moves['undo'] = is_string($moves['undo']) ? (array)Zend_Json::decode($moves['undo']) : (array)$moves['undo'];
        } catch (Exception $e) { $moves['undo'] = []; }
        try {
            $moves['redo'] = is_string($moves['redo']) ? (array)Zend_Json::decode($moves['redo']) : (array)$moves['redo'];
        } catch (Exception $e) { $moves['redo'] = []; }

        return $moves;
    }

    public function undoMove()
    {
        $moves = $this->getUndoRedoMoves();
        if (!empty($moves['undo']['cells'])) {
            list($oldParameters, $newParameters) = $this->setCellsNumbers($moves['undo']['cells']);
            $this->addLog(Application_Model_Db_Sudoku_Logs::ACTION_TYPE_UNDO, $oldParameters, $newParameters);
        }
    }

    public function redoMove()
    {
        $moves = $this->getUndoRedoMoves();
        if (!empty($moves['redo']['cells'])) {
            list($oldParameters, $newParameters) = $this->setCellsNumbers($moves['redo']['cells']);
            $this->addLog(Application_Model_Db_Sudoku_Logs::ACTION_TYPE_REDO, $oldParameters, $newParameters);
        }
    }

}
