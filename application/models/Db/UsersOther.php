<?php

class Application_Model_Db_UsersOther extends Application_Model_Db_Abstract
{

    const TABLE_NAME = 'users_other';

    public function getAll(array $parameters = array(), array $order = array('email ASC', 'login ASC'))
    {
        return parent::getAll($parameters, $order);
    }

    public function insert(array $data)
    {
        $now = new \DateTime('NOW', new \DateTimeZone('UTC'));
        $data = array(
            'network'    => isset($data['network']) ? $data['network'] : '',
            'network_id' => isset($data['network_id']) ? $data['network_id'] : '',
            'email'      => isset($data['email']) ? $data['email'] : '',
            'login'      => isset($data['login']) ? $data['login'] : '',
            'created'    => $now->format('Y-m-d H:i:s'),
        );
        $result = $this->_db->insert(self::TABLE_NAME, $data);
        if ($result) {
            $result = $this->_db->lastInsertId();
        }
        return $result;
    }

    public function update($id, array $data)
    {
        $update = array();
        foreach (array('password') as $field) {
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