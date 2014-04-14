<?php

class Application_Model_Db_User_Sessions extends Application_Model_Db_Abstract
{

    const TABLE_NAME = 'user_sessions';

    public function insert(array $data)
    {
        $data = [
            'user_id'    => isset($data['user_id']) ? $data['user_id'] : 0,
            'session_id' => isset($data['session_id']) ? $data['session_id'] : '',
            'ip'         => isset($data['ip']) ? $data['ip'] : '',
            'created'    => $this->getNow(),
        ];
        $result = $this->_db->insert(static::TABLE_NAME, $data);
        if ($result) {
            $result = $this->_db->lastInsertId();
        }
        return $result;
    }

    public function update($id, array $data)
    {
        return false;
    }

    public function delete($id)
    {
        return false;
    }

}