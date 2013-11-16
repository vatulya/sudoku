<?php

class Application_Model_Db_Users extends Application_Model_Db_Abstract
{

    const TABLE_NAME = 'users';

    protected $_allowedSaveFields = array(
        'email',
        'full_name', 'address', 'phone',
        'emergency_phone', 'emergency_full_name', 'birthday', 'owner', 'regular_work_hours',
    );

    public function getUserByEmail($email)
    {
        $select = $this->_db->select()
            ->from(array('u' => self::TABLE_NAME))
            ->where('u.email = ?', $email);
        $result = $this->_db->fetchRow($select);
        return $result;
    }

    public function getUserById($id, DateTime $checkingDate = null)
    {
        $select = $this->_db->select()
            ->from(array('u' => self::TABLE_NAME))
            ->where('u.id = ?', $id);
        $select = $this->_addCheckinByDate($select, $checkingDate);
        $result = $this->_db->fetchRow($select);
        return $result;
    }

    public function getAllUsers()
    {
        $select = $this->_db->select()
            ->from(array('u' => self::TABLE_NAME))
            ->order(array('u.full_name ASC'));
        $result = $this->_db->fetchAll($select);
        return $result;
    }

    public function getAllUsersByGroup($groupId, DateTime $checkingDate = null)
    {
        $select = $this->_db->select(array())
            ->from(array('ug' => Application_Model_Db_User_Groups::TABLE_NAME))
            ->join(array('u' => self::TABLE_NAME), 'ug.user_id = u.id', array('*'))
            ->where('ug.group_id = ?', $groupId)
            ->order(array('u.full_name ASC'));
        $select = $this->_addCheckinByDate($select, $checkingDate);
        $result = $this->_db->fetchAll($select);
        return $result;
    }

    public function saveRole($userId, $role)
    {
        $result = false;
        $checkUser = $this->getUserById($userId);
        if ($checkUser['role'] == $role) {
            $result = true;
        } else {
            $data = array(
                'role' => (int)$role,
            );
            $result = $this->_db->update(self::TABLE_NAME, $data, array('id = ?' => $userId));
        }
        return $result;
    }

    public function saveField($userId, $field, $value)
    {
        $checkUser = $this->getUserById($userId);
        $result = false;
        if (in_array($field, $this->_allowedSaveFields)) {
            $data = array(
                $field => $value,
            );
            if ($checkUser[$field] == $value) {
                $result = true;
            } else {
                $result = $this->_db->update(self::TABLE_NAME, $data, array('id = ?' => $userId));
            }
        }
        return $result;
    }

    public function savePassword($userId, $password)
    {
        $checkUser = $this->getUserById($userId);
        $result = false;
        $data = array(
            'password' => $password,
        );
        if ($checkUser['password'] == $password) {
            $result = true;
        } else {
            $result = $this->_db->update(self::TABLE_NAME, $data, array('id = ?' => $userId));
        }
        return $result;
    }

    public function insert(array $user)
    {
        try {
            if ( ! empty($date)) {
                $date = new DateTime($user['birthday']);
                $date = $date->format('Y-m-d');
            } else {
                $date = '0000-00-00';
            }
        } catch (Exception $e) {
            $date = '0000-00-00';
        }
        $data = array(
            'email'               => empty($user['email']) ? '' : $user['email'],
            'password'            => empty($user['password']) ? '' : $user['password'],
            'role'                => Application_Model_Auth::ROLE_USER,
            'full_name'           => empty($user['full_name']) ? '' : $user['full_name'],
            'address'             => empty($user['address']) ? '' : $user['address'],
            'phone'               => empty($user['phone']) ? '' : $user['phone'],
            'emergency_phone'     => empty($user['emergency_phone']) ? '' : $user['emergency_phone'],
            'emergency_full_name' => empty($user['emergency_full_name']) ? '' : $user['emergency_full_name'],
            'birthday'            => $date,
            'owner'               => empty($user['owner']) ? '' : $user['owner'],
            'created'             => date_create()->format('Y-m-d H:i:s'),
        );
        $result = $this->_db->insert(self::TABLE_NAME, $data);
        return $result;
    }

    public function setRole($userId, $role)
    {
        $role = (int)$role;
        $result = false;
        $allowedRoles = Application_Model_Auth::getAllowedRoles();
        if (array_key_exists($role, $allowedRoles)) {
            $check = $this->getUserById($userId);
            if ($check['role'] == $role) {
                $result = true;
            } else {
                if ($check['role'] != Application_Model_Auth::ROLE_SUPER_ADMIN) {
                    // Nobody can't change role for SUPER ADMIN
                    $data = array('role' => $role);
                    $result = $this->_db->update(self::TABLE_NAME, $data, array('id = ?' => $userId));
                }
            }
        }
        return $result;
    }

    public function delete($userId)
    {
        // TODO: make some archive logic for users and user data
        // $result - for debug
        $result = $this->_db->delete(self::TABLE_NAME, array('id = ?' => $userId));
        $result = $this->_db->delete(Application_Model_Db_User_Checks::TABLE_NAME, array('user_id = ?' => $userId));
        $result = $this->_db->delete(Application_Model_Db_User_Groups::TABLE_NAME, array('user_id = ?' => $userId));
        $result = $this->_db->delete(Application_Model_Db_User_Parameters::TABLE_NAME, array('user_id = ?' => $userId));
        $result = $this->_db->delete(Application_Model_Db_User_Requests::TABLE_NAME, array('user_id = ?' => $userId));
        return true;
    }

    public function getUserCheckins($userId, DateTime $date)
    {
        $select = $this->_db->select()
            ->from(array('uc' => Application_Model_Db_User_Checks::TABLE_NAME))
            ->where('uc.user_id = ?', $userId)
            ->where('uc.check_date = ?', $date->format('Y-m-d'))
            ->order(array('uc.check_in ASC'));
        $result = $this->_db->fetchAll($select);
        return $result;
    }

    protected function _addCheckinByDate($select, DateTime $checkingDate = null)
    {
        if ($checkingDate) {
            $query = 'DROP TEMPORARY TABLE IF EXISTS tmp_user_checkins_sort';
            $this->_db->query($query);

            $query = 'DROP TEMPORARY TABLE IF EXISTS tmp_user_checkins';
            $this->_db->query($query);

            // Prepare temporary table with latest check IN for all users
            $query = '
                CREATE TEMPORARY TABLE tmp_user_checkins_sort
                SELECT
                    uc.id,
                    uc.user_id,
                    uc.check_date,
                    uc.check_in,
                    uc.check_out
                FROM
                    ' . Application_Model_Db_User_Checks::TABLE_NAME . ' uc
                WHERE
                    uc.check_date = :date
                ORDER BY uc.check_in DESC
            ';
            $this->_db->query($query, array(':date' => $checkingDate->format('Y-m-d')));
            $this->_db->query('ALTER TABLE tmp_user_checkins_sort ADD INDEX(check_date)');

            $query = '
                CREATE TEMPORARY TABLE tmp_user_checkins
                SELECT
                    tucs.*
                FROM
                    tmp_user_checkins_sort tucs
                GROUP BY
                    tucs.check_date
            ';
            $this->_db->query($query);
            $this->_db->query('ALTER TABLE tmp_user_checkins_sort ADD INDEX(user_id)');

            $table     = array('tuc' => 'tmp_user_checkins');
            $condition = 'u.id = tuc.user_id';
            $fields    = array('tuc.check_date', 'tuc.check_in', 'tuc.check_out');
            $select->joinLeft($table, $condition, $fields);
        }
        return $select;
    }

}