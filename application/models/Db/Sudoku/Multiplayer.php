<?php

class Application_Model_Db_Sudoku_Multiplayer extends Application_Model_Db_GameAbstract
{

    const TABLE_NAME = 'sudoku_multiplayer';

    public function insert(array $data)
    {
        $now = $this->getNow();
        $data = [
            'user_id'       => $data['user_id'],
            'difficulty_id' => $data['difficulty']['id'],
            'state'         => Application_Service_Multiplayer_Abstract::STATE_NEW,
            'created'       => $now,
        ];
        $result = $this->_db->insert(self::TABLE_NAME, $data);
        if ($result) {
            $result = $this->_db->lastInsertId();
        }
        return $result;
    }

    public function update($id, array $data)
    {
        $update = [];
        foreach (['state'] as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }
        $result = false;
        if (!empty($update)) {
            $result = (bool)$this->_db->update(static::TABLE_NAME, $update, ['id = ?' => $id]);
        }
        return $result;
    }

}