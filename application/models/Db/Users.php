<?php

class Application_Model_Db_Users extends Application_Model_Db_Abstract
{

    const TABLE_NAME = 'users';

    const DEFAULT_USER_FULL_NAME = 'mr. Anonymous';

    protected $_hiddenFields = [
        'password',
    ];

    protected $_hideFields = true;

    public function getOne(array $parameters = [], array $order = [])
    {
        $result = parent::getOne($parameters, $order);
        if ($result && $this->_hideFields) {
            $result = $this->hideFields($result);
            $this->_hideFields = true;
        }
        return $result;
    }

    public function getAll(array $parameters = [], array $order = ['full_name ASC'])
    {
        $result = parent::getAll($parameters, $order);
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
        $data = [
            'role_id'    => isset($data['role_id']) ? $data['role_id'] : Application_Service_User::ROLE_GUEST,
            'email'      => isset($data['email']) ? $data['email'] : '',
            'login'      => isset($data['login']) ? $data['login'] : '',
            'network'    => isset($data['network']) ? $data['network'] : '',
            'network_id' => isset($data['network_id']) ? $data['network_id'] : '',
            'full_name'  => isset($data['full_name']) ? $data['full_name'] : self::DEFAULT_USER_FULL_NAME,
            'password'   => isset($data['password']) ? $data['password'] : '',
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

    /**
     * @return Application_Model_Db_Users
     */
    public function withHiddenFields()
    {
        $this->_hideFields = false;
        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
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