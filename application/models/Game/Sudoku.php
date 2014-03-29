<?php

/**
 * Class Application_Model_Game_Sudoku
 *
 * @method Application_Service_Game_Sudoku getService()
 */
class Application_Model_Game_Sudoku extends Application_Model_Game_Abstract
{

    protected static $modelDb = 'Sudoku_Games';
    protected static $modelDbLogs = 'Sudoku_Logs';

    protected static $service = 'Sudoku';

    public function setCellNumber($coords, $number)
    {
        $openCells = $this->getParameter('openCells') ?: array();
        $checkedCells = $this->getParameter('checkedCells') ?: array();
        if (!$this->getService()->checkCoords($coords)) {
            // Wrong coords
            return false;
        }
        if ($number < 0 || $number > 9) {
            // Wrong value
            return false;
        }
        if (isset($openCells[$coords])) {
            // This cell already filled
            return false;
        }
        $oldNumber = isset($checkedCells[$coords]) ? $checkedCells[$coords] : 0;
        $checkedCells[$coords] = (int)$number;
        array_filter($checkedCells);
        $this->setParameter('checkedCells', $checkedCells);

        $newParameters = [
            'cells' => [
                $coords => (int)$number,
            ]
        ];
        $oldParameters = [
            'cells' => [
                $coords => $oldNumber,
            ],
        ];
        $this->addLog(Application_Model_Db_Sudoku_Logs::ACTION_TYPE_SET_CELLS_NUMBERS, $newParameters, $oldParameters);
        return true;
    }

    public function clearBoard()
    {
        $newParameters = ['cells' => []];
        $oldParameters = $this->getParameter('checkedCells') ?: [];
        $oldParameters = ['cells' => $oldParameters];

        $this->setParameter('checkedCells', []);
        $this->addLog(Application_Model_Db_Sudoku_Logs::ACTION_TYPE_CLEAR_BOARD, $newParameters, $oldParameters);
        return true;
    }

    /************** LOGS *******************/

    protected function addLog($actionType, array $newParameters = [], array $oldParameters = [])
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
