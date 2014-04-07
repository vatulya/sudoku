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

    protected static $modelDb = 'Sudoku_Games';
    protected static $modelDbLogs = 'Sudoku_Logs';

    protected static $service = 'Sudoku';

    /**
     * @param string $coords
     * @param int $number
     * @return bool
     */
    public function setCellNumber($coords, $number)
    {
        list($oldParameters, $newParameters) = $this->setCellsNumbers([$coords => $number]);
        $this->addLog(Application_Model_Db_Sudoku_Logs::ACTION_TYPE_SET_CELLS_NUMBERS, $oldParameters, $newParameters);
    }

    /**
     * @param array $cellsNumbers coords => number
     * @return bool
     */
    protected function setCellsNumbers(array $cellsNumbers)
    {
        $newParameters = [];
        $oldParameters = [];
        $checkedCells = $this->getParameter(static::PARAMETER_KEY_CHECKED_CELLS) ?: array();

        foreach ($cellsNumbers as $coords => $number) {
            if (!$this->isCorrectCellNumber($coords, $number)) {
                unset($cellsNumbers[$coords]);
                continue;
            }
            $oldNumber = isset($checkedCells[$coords]) ? $checkedCells[$coords] : 0;
            $checkedCells[$coords] = (int)$number;

            $newParameters[$coords] = (int)$number;
            $oldParameters[$coords] = (int)$oldNumber;
        }

        if (!empty($newParameters) && !empty($oldParameters)) {
            array_filter($checkedCells);
            $this->setParameter(static::PARAMETER_KEY_CHECKED_CELLS, $checkedCells);

            $newParameters = [
                'cells' => $newParameters,
            ];
            $oldParameters = [
                'cells' => $oldParameters,
            ];
        }
        return [$oldParameters, $newParameters];
    }

    /**
     * @param string $coords
     * @param int $number
     * @return bool
     */
    protected function isCorrectCellNumber($coords, $number)
    {
        settype($number, 'int');
        if (!$this->getService()->checkCoords($coords)) {
            // Wrong coords
            return false;
        }
        if ($number < 0 || $number > 9) {
            // Wrong value
            return false;
        }
        $openCells = $this->getParameter(static::PARAMETER_KEY_OPEN_CELLS) ?: [];
        if (isset($openCells[$coords])) {
            // This cell already filled
            return false;
        }
        return true;
    }

    public function clearBoard()
    {
        $newParameters = ['cells' => []];
        $oldParameters = $this->getParameter(static::PARAMETER_KEY_CHECKED_CELLS) ?: [];
        $oldParameters = ['cells' => $oldParameters];

        $this->setParameter(static::PARAMETER_KEY_CHECKED_CELLS, []);
        $this->addLog(Application_Model_Db_Sudoku_Logs::ACTION_TYPE_CLEAR_BOARD, $oldParameters, $newParameters);
        return true;
    }

    /************** HASH *******************/

    /**
     * @return string
     */
    public function getHash()
    {
        $board['openCells'] = $this->getParameter(static::PARAMETER_KEY_OPEN_CELLS);
        $board['checkedCells'] = $this->getParameter(static::PARAMETER_KEY_CHECKED_CELLS);

        ksort($board['openCells']);
        ksort($board['checkedCells']);

        $hash = md5(json_encode($board));
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
                case Application_Model_Db_Sudoku_Logs::ACTION_TYPE_SET_CELLS_NUMBERS:
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

    /************** LOGS *******************/

    protected function getLogs()
    {
        $where = [
            'game_id' => $this->getId(),
        ];
        $order = [
            'created DESC',
        ];
        return $this->getModelDbLogs()->getAll($where, $order);
    }

    protected function addLog($actionType, array $oldParameters = [], array $newParameters = [])
    {
        $data = [
            'game_id'        => $this->getId(),
            'action_type'    => $actionType,
            'new_parameters' => $newParameters,
            'old_parameters' => $oldParameters,
        ];
        return $this->getModelDbLogs()->insert($data);
    }

    protected static function initModelDbLogs()
    {
        if (is_string(static::$modelDbLogs)) {
            $class = 'Application_Model_Db_' . static::$modelDbLogs;
            static::$modelDbLogs = new $class();
        }
    }

    /**
     * @return Application_Model_Db_Abstract
     */
    protected static function getModelDbLogs()
    {
        if (is_string(static::$modelDbLogs)) {
            static::initModelDbLogs();
        }
        return static::$modelDbLogs;
    }

}
