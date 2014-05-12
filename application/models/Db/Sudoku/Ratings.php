<?php

class Application_Model_Db_Sudoku_Ratings extends Application_Model_Db_GameAbstract
{

    const TABLE_NAME = 'sudoku_ratings';

    public function insert(array $data)
    {
        $now = $this->getNow();
        $data = [
            'user_id'              => $data['user_id'],
            'difficulty'           => $data['difficulty']['id'],
            'rating'               => $data['rating'],
            'position'             => 0,
            'faster_game_hash'     => '',
            'faster_game_duration' => 0,
            'updated'              => $now,
        ];
        $result = $this->_db->insert(self::TABLE_NAME, $data);
        return $result;
    }

    public function update($id, array $data)
    {
        $update = [];
        foreach (['rating', 'position', 'faster_game_hash', 'faster_game_duration'] as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }
        $update['updated'] = $this->getNow();
        $result = false;
        if (!empty($update)) {
            $result = (bool)$this->_db->update(static::TABLE_NAME, $update, ['id = ?' => $id]);
        }
        return $result;
    }

}