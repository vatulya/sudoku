<?php

class Application_Model_Db_UsersOther extends Application_Model_Db_Abstract
{

    const TABLE_NAME = 'users_other';

    public function getUserById($id)
    {
        $select = $this->_db->select()
            ->from(array('u' => self::TABLE_NAME))
            ->where('u.id = ?', $id);
        $result = $this->_db->fetchRow($select);
        return $result;
    }

    public function getUserByNetworkAndId($network, $id)
    {
        $select = $this->_db->select()
            ->from(array('u' => self::TABLE_NAME))
            ->where('u.network = ?', $network)
            ->where('u.network_id = ?', $id);
        $result = $this->_db->fetchRow($select);
        return $result;
    }

    public function getAllUsers()
    {
        $select = $this->_db->select()
            ->from(array('u' => self::TABLE_NAME))
            ->order(array('u.email ASC', 'u.login ASC'));
        $result = $this->_db->fetchAll($select);
        return $result;
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

}