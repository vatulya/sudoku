<?php

class Application_Model_Db_Sudoku_Logs extends Application_Model_Db_GameAbstract
{

    const TABLE_NAME = 'sudoku_logs';

    const ACTION_TYPE_SET_CELLS_NUMBERS = 'setCellsNumbers';
    const ACTION_TYPE_CLEAR_BOARD       = 'clearBoard';
    const ACTION_TYPE_UNDO              = 'undo';
    const ACTION_TYPE_REDO              = 'redo';

    public function getOne(array $parameters = [])
    {
        $data = parent::getOne($parameters);
        try {
            $data['new_parameters'] = Zend_Json::decode($data['new_parameters']);
        } catch (Exception $e) {
            // TODO: add logs
            $data['new_parameters'] = [];
        }
        try {
            $data['old_parameters'] = Zend_Json::decode($data['old_parameters']);
        } catch (Exception $e) {
            // TODO: add logs
            $data['old_parameters'] = [];
        }
        return $data;
    }

    public function getAll(array $parameters = [], array $order = [])
    {
        $data = parent::getAll($parameters, $order);
        foreach ($data as $key => $row) {
            try {
                $row['new_parameters'] = Zend_Json::decode($row['new_parameters']);
            } catch (Exception $e) {
                // TODO: add logs
                $row['new_parameters'] = [];
            }
            try {
                $row['old_parameters'] = Zend_Json::decode($row['old_parameters']);
            } catch (Exception $e) {
                // TODO: add logs
                $row['old_parameters'] = [];
            }
            $data[$key] = $row;
        }
        return $data;
    }

    public function insert(array $data)
    {
        if (!in_array($data['action_type'], $this->getAllowedActionTypes())) {
            // TODO: add logs
            return false;
        }
        $data = [
            'game_id'        => $data['game_id'],
            'created'        => $this->getNow(),
            'action_type'    => $data['action_type'],
            'new_parameters' => isset($data['new_parameters']) ? $data['new_parameters'] : [],
            'old_parameters' => isset($data['old_parameters']) ? $data['old_parameters'] : [],
        ];
        $data['new_parameters'] = Zend_Json::encode($data['new_parameters']);
        $data['old_parameters'] = Zend_Json::encode($data['old_parameters']);
        $result = $this->_db->insert(self::TABLE_NAME, $data);
        if ($result) {
            $result = $this->_db->lastInsertId();
        }
        return $result;
    }

    public function update($id, array $data)
    {
        return false;
    }

    static public function getAllowedActionTypes()
    {
        return [
            self::ACTION_TYPE_SET_CELLS_NUMBERS,
            self::ACTION_TYPE_CLEAR_BOARD,
            self::ACTION_TYPE_UNDO,
            self::ACTION_TYPE_REDO,
        ];
    }

}