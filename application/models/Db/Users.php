<?php

class Application_Model_Db_Users extends Application_Model_Db_Abstract
{

    const TABLE_NAME = 'users';

    protected $_hiddenFields = array(
        'password',
    );

    protected $_hideFields = true;

    protected $_allowedSaveFields = array(
        'email',
        'full_name', 'address', 'phone',
        'emergency_phone', 'emergency_full_name', 'birthday', 'owner', 'regular_work_hours',
    );

    public function getUserById($id)
    {
        $select = $this->_db->select()
            ->from(array('u' => self::TABLE_NAME))
            ->where('u.id = ?', $id);
        $result = $this->_db->fetchRow($select);
        if ($result && $this->_hideFields) {
            $result = $this->hideFields($result);
            $this->_hideFields = true;
        }
        return $result;
    }

    public function getUserByEmail($email)
    {
        $select = $this->_db->select()
            ->from(array('u' => self::TABLE_NAME))
            ->where('u.email = ?', $email);
        $result = $this->_db->fetchRow($select);
        if ($result && $this->_hideFields) {
            $result = $this->hideFields($result);
            $this->_hideFields = true;
        }
        return $result;
    }

    public function getUserByLogin($login)
    {
        $select = $this->_db->select()
            ->from(array('u' => self::TABLE_NAME))
            ->where('u.login = ?', $login);
        $result = $this->_db->fetchRow($select);
        if ($result && $this->_hideFields) {
            $result = $this->hideFields($result);
            $this->_hideFields = true;
        }
        return $result;
    }

    public function getAllUsers()
    {
        $select = $this->_db->select()
            ->from(array('u' => self::TABLE_NAME))
            ->order(array('u.full_name ASC'));
        $result = $this->_db->fetchAll($select);
        if ($result && $this->_hideFields) {
            foreach ($result as $key => $row) {
                $result[$key] = $this->hideFields($row);
            }
            $this->_hideFields = true;
        }
        return $result;
    }

    public function insert(array $data)
    {
        $now = new \DateTime('NOW', new \DateTimeZone('UTC'));
        $data = array(
            'email'    => isset($data['email']) ? $data['email'] : '',
            'login'    => isset($data['login']) ? $data['login'] : '',
            'password' => isset($data['password']) ? $data['password'] : '',
            'created'  => $now->format('Y-m-d H:i:s'),
        );
        $result = $this->_db->insert(self::TABLE_NAME, $data);
        if ($result) {
            $result = $this->_db->lastInsertId();
        }
        return $result;
    }

    public function withHiddenFields()
    {
        $this->_hideFields = false;
        return $this;
    }

    public function hideFields(array $array)
    {
        foreach ($this->_hiddenFields as $field) {
            if (isset($array[$field])) {
                unset($array[$field]);
            }
        }
        return $array;
    }

}