<?php

class Application_Model_Db_SudokuGames extends Application_Model_Db_GameAbstract
{

    const TABLE_NAME = 'sudoku_games';

    public function getOne(array $parameters = array())
    {
        $data = parent::getOne($parameters);
        try {
            $data['parameters'] = Zend_Json::decode($data['parameters']);
        } catch (Exception $e) {
            // TODO: add logs
            $data['paramters'] = array();
        }
        return $data;
    }

    public function getAll(array $parameters = array(), array $order = array())
    {
        $data = parent::getAll($parameters, $order);
        foreach ($data as $key => $row) {
            try {
                $row['parameters'] = Zend_Json::decode($row['parameters']);
            } catch (Exception $e) {
                // TODO: add logs
                $row['paramters'] = array();
            }
            $data[$key] = $row;
        }
        return $data;
    }

    public function insert(array $data)
    {
        $now = (new \DateTime('NOW', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $data = array(
            'user_id'    => $data['user_id'],
            'difficulty' => $data['difficulty']['code'],
            'parameters' => isset($data['parameters']) ? $data['parameters'] : array(),
            'created'    => $now,
            'updated'    => $now,
        );
        $data['parameters'] = Zend_Json::encode($data['parameters']);
        $result = $this->_db->insert(self::TABLE_NAME, $data);
        if ($result) {
            $result = $this->_db->lastInsertId();
        }
        return $result;
    }

    public function update($id, array $data)
    {
        $update = array();
        foreach (array('state', 'difficulty', 'duration', 'parameters') as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }
        if (isset($update['parameters'])) {
            $update['parameters'] = Zend_Json::encode($update['parameters']);
        }
        $update['updated'] = (new \DateTime('NOW', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $result = false;
        if (!empty($update)) {
            $result = (bool)$this->_db->update(static::TABLE_NAME, $update, array('id = ?' => $id));
        }
        return $result;
    }

}