<?php

class Application_Model_Db_Sudoku_Games extends Application_Model_Db_GameAbstract
{

    const TABLE_NAME = 'sudoku_games';

    public function getOne(array $parameters = [], array $order = [])
    {
        $data = parent::getOne($parameters, $order);
        try {
            $data['parameters'] = Zend_Json::decode($data['parameters']);
        } catch (Exception $e) {
            // TODO: add logs
            $data['paramters'] = [];
        }
        return $data;
    }

    public function getAll(array $parameters = [], array $order = [], $limit = 0, $offset = 0)
    {
        $data = parent::getAll($parameters, $order, $limit, $offset);
        foreach ($data as $key => $row) {
            try {
                $row['parameters'] = Zend_Json::decode($row['parameters']);
            } catch (Exception $e) {
                // TODO: add logs
                $row['paramters'] = [];
            }
            $data[$key] = $row;
        }
        return $data;
    }

    public function insert(array $data)
    {
        $now = $this->getNow();
        $data = [
            'user_id'    => $data['user_id'],
            'difficulty' => $data['difficulty']['id'],
            'parameters' => isset($data['parameters']) ? $data['parameters'] : [],
            'created'    => $now,
            'updated'    => $now,
            'hash'       => $data['hash'],
        ];
        $data['parameters'] = Zend_Json::encode($data['parameters']);
        $result = $this->_db->insert(self::TABLE_NAME, $data);
        if ($result) {
            $result = $this->_db->lastInsertId();
        }
        return $result;
    }

    public function update($id, array $data)
    {
        $update = [];
        foreach (['state', 'difficulty', 'duration', 'parameters'] as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }
        if (isset($update['parameters'])) {
            $update['parameters'] = Zend_Json::encode($update['parameters']);
        }
        $update['updated'] = $this->getNow();
        $result = false;
        if (!empty($update)) {
            $result = (bool)$this->_db->update(static::TABLE_NAME, $update, ['id = ?' => $id]);
        }
        return $result;
    }

}