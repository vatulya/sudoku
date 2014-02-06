<?php

class Application_Model_Db_SudokuGames extends Application_Model_Db_Abstract
{

    const TABLE_NAME = 'sudoku_games';

    public function insert(array $data)
    {
        $now = new \DateTime('NOW', new \DateTimeZone('UTC'));
        $data = array(
            'user_id'    => $data['user']['id'] ?: 0,
            'user_type'  => !empty($data['network_id']) ? Application_Service_User::USER_TYPE_OTHER : Application_Service_User::USER_TYPE_MAIN,
            'difficulty' => $data['difficulty']['code'],
            'parameters' => isset($data['parameters']) ? $data['parameters'] : array(),
            'created'    => $now->format('Y-m-d H:i:s'),
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
        $result = false;
        if (!empty($update)) {
            $result = (bool)$this->_db->update(static::TABLE_NAME, $data, array('id' => $id));
        }
        return $result;
    }

    public function delete($id)
    {
        return false;
    }

}