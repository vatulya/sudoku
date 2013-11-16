<?php

class Application_Model_User extends Application_Model_Abstract
{

    const USER_CHECK_IN = 'in';
    const USER_CHECK_OUT = 'out';

    const USER_DEFAULT_OWNER = 'Eigen';

    protected $_modelDb;

    protected $_hiddenFields = array(
        'password',
    );

    public function __construct()
    {
        $this->_modelDb = new Application_Model_Db_Users();
    }

    protected function _filterHiddenFields(array $array)
    {
        foreach ($this->_hiddenFields as $field) {
            if (isset($array[$field])) {
                unset($array[$field]);
            }
        }
        return $array;
    }

    public function getUserByEmail($email)
    {
        $user = $this->_modelDb->getUserByEmail($email);
        if ( ! $user) {
            $user = array();
        }
        $user = $this->_filterHiddenFields($user);
        return $user;
    }

    public function getUserById($id, $filterFields = true)
    {
        $user = $this->_modelDb->getUserById($id);
        if ($filterFields && is_array($user)) {
            $user = $this->_filterHiddenFields($user);
        }
        return $user;
    }

    public function getAllUsers()
    {
        $users = $this->_modelDb->getAllUsers();
        foreach ($users as $key => $user) {
            $user = $this->_filterHiddenFields($user);
            $users[$key] = $user;
        }
        return $users;
    }

    public function getAllUsersByGroup($groupId, DateTime $checkingDate = null)
    {
        $users = $this->_modelDb->getAllUsersByGroup($groupId, $checkingDate);
        foreach ($users as $key => $user) {
            $user = $this->_filterHiddenFields($user);
            $users[$key] = $user;
        }
        return $users;
    }

    public function getAllUsersFromHistory($groupId, $year, $week)
    {
        $historyDateInterval = My_DateTime::getWeekHistoryDateInterval($year, $week);
        $modelPlanning = new Application_Model_Db_User_Planning();
        $usersNormalized = array();
        $users = $modelPlanning->getUsersByDateInterval($groupId, $historyDateInterval['start'], $historyDateInterval['end']);
        foreach ($users as $user) {
            $user = $this->_filterHiddenFields($user);
            $usersNormalized[$user['id']] = $user;
            $usersNormalized[$user['id']]['user_id'] = $user['id'];
            //$usersNormalized[$user['id']] = $this->getUserById($user['id']);
        }
        $currentDate = new My_DateTime();
        $currentDate = $currentDate->getTimestamp();
        $endWeek = My_DateTime::getTimestampNextWeekByYearWeek($year, $week);
        if ($endWeek > $currentDate) {
            $modelUser = new Application_Model_User();
            $users = $modelUser->getAllUsersByGroup($groupId);
            foreach ($users as $user) {
                $usersNormalized[$user['id']] = $user;
            }
        }
        return $usersNormalized;
    }

    public function getAllUsersForYear($year)
    {
        $modelPlanning = new Application_Model_Db_User_Planning();
        $yearDateInterval = My_DateTime::getYearDateInterval($year);
        $users = $modelPlanning->getUsersByDateInterval(false, $yearDateInterval['start'], $yearDateInterval['end']);
        foreach ($users as $key => $user) {
            $user = $this->_filterHiddenFields($user);
            $users[$key] = $user;
        }
        return $users;
    }

    public function userCheck($userId, $check)
    {
        $now = new My_DateTime();
        $user = $this->_modelDb->getUserById($userId, $now);
        if (empty($user)) {
            throw new Exception('Error! Wrong user ID.');
        }
        $check = ($check == Application_Model_User::USER_CHECK_IN || $check == Application_Model_User::USER_CHECK_OUT ? $check : null);
        if ( ! $check) {
            return $user;
        }
        $modelUserCheck = new Application_Model_Db_User_Checks();

        $lastCheck = $modelUserCheck->getUserLastCheck($userId);
        if (empty($lastCheck)) {
            // No checks today. We can do check IN only.
            if ($check == Application_Model_User::USER_CHECK_IN) {
                $modelUserCheck->userCheckIn($userId);
            } else {
                throw new Exception('Error! User can\'t check OUT if no action check IN in this day.');
            }
        } else {
            // We have some checks today.
            if ( ! empty($lastCheck['check_out'])) {
                // Last check session is closed. We can start new.
                if ($check == Application_Model_User::USER_CHECK_IN) {
                    $modelUserCheck->userCheckIn($userId);
                } else {
                    throw new Exception('Error! User can\'t check OUT if no action check IN in latest session.');
                }
            } else {
                // We have started session without check-OUT.
                if ($check == Application_Model_User::USER_CHECK_OUT) {
                    $modelUserCheck->userCheckOut($userId);
                } else {
                    throw new Exception('Error! User can\'t check IN if no action check OUT in latest session.');
                }
            }
        }

        $user = $this->_modelDb->getUserById($user['id'], $now); // update user data
        return $user;
    }

    public function getParametersByUserId($userId, $year = '')
    {
        $modelUserParameters = new Application_Model_Db_User_Parameters();
        $userParameters = $modelUserParameters->getParametersByUserId($userId, $year = '');
        return $userParameters;
    }

    public function savePassword($userId, $newPassword, $currentPassword = '', $force = false)
    {
        /*
         * $currentPassword can be empty if we don't check it. This need if some admin changed password for some user.
         * $force - this param for future logic.
         */
        $user = $this->_modelDb->getUserById($userId);
        $newPassword = trim($newPassword);
        $modelAuth = new Application_Model_Auth();
        $result = false;
        // TODO: refactor it
        if ($force && ! empty($newPassword)) {
            $result = $modelAuth->_changePassword($userId, $newPassword);
        } else {
            if ( ! empty($user)) {
                if ( ! empty($newPassword)) {
                    $currentPasswordEncoded = Application_Model_AuthAdapter::encodePassword($currentPassword);
                    if (empty($currentPassword) || $user['password'] == $currentPasswordEncoded) {
                        $result = $modelAuth->_changePassword($userId, $newPassword);
                    } else {
                        throw new Exception('Error! Wrong old password.');
                    }
                } else {
                    throw new Exception('Error! Wrong new password.');
                }
            } else {
                throw new Exception('Error! Wrong user ID.');
            }
        }
        return $result;
    }

    public function saveField($userId, $field, $value)
    {
        $result = $this->_modelDb->saveField($userId, $field, $value);
        return $result;
    }

    public function saveRole($userId, $role)
    {
        $result = false;
        $allowedRoles = Application_Model_Auth::getAllowedRoles();
        if ( ! empty($allowedRoles[$role])) {
            $result = $this->_modelDb->saveRole($userId, $role);
        } else {
            throw new Exception('Error! This user don\'t have permissions.');
        }
        return $result;
    }

    public function saveGroups($userId, array $groups)
    {
        $modelUserGroups = new Application_Model_Db_User_Groups();
        $modelGroup = new Application_Model_Group();

        $userGroupsOld = $modelGroup->getGroupsByUserId($userId);
        $userGroupsOld = array_map(function($group) {
            return (int)$group['id'];
        }, $userGroupsOld);
        $userGroupsNew = array_map(function($group) {
            return $group['group'];
        }, $groups);
        $userGroupsDiff = array_diff($userGroupsOld, $userGroupsNew);
        $result = $modelUserGroups->saveUserGroups($userId, $groups);
        if ($result) {
            $modelDbGroupPlanning = new Application_Model_Db_Group_Plannings();
            foreach ($userGroupsDiff as $groupId) {
                $modelDbGroupPlanning->saveGroupPlanning($userId, $groupId, array()); // delete planning for this user in this group
            }
            $user = $this->getUserById($userId);
            $adminGroups = $modelUserGroups->getUserGroupsAdmin($userId);
            if (count($adminGroups)) {
                if ($user['role'] < Application_Model_Auth::ROLE_GROUP_ADMIN) {
                    $result = $this->saveRole($userId, Application_Model_Auth::ROLE_GROUP_ADMIN);
                }
            } else {
                if ($user['role'] == Application_Model_Auth::ROLE_GROUP_ADMIN) {
                    $result = $this->saveRole($userId, Application_Model_Auth::ROLE_USER);
                }
            }
        }
        return $result;
    }

    public function create(array $user)
    {
        $result = false;
        if ( ! empty($user['email']) && Application_Model_Auth::getRole() >= Application_Model_Auth::ROLE_ADMIN) {
            $user['password'] = Application_Model_AuthAdapter::encodePassword($user['password']);
            $result = $this->_modelDb->insert($user);
        }
        return $result;
    }

    public function setAdmin($userId)
    {
        $user = $this->getUserById($userId);
        $newRole = false;
        if ($user['role'] < Application_Model_Auth::ROLE_ADMIN) {
            $newRole = Application_Model_Auth::ROLE_ADMIN;
        } elseif ($user['role'] == Application_Model_Auth::ROLE_ADMIN) {
            $modelUserGroups = new Application_Model_Db_User_Groups();
            $adminGroups = $modelUserGroups->getUserGroupsAdmin($userId);
            if (count($adminGroups) > 0) {
                $newRole = Application_Model_Auth::ROLE_GROUP_ADMIN;
            } else {
                $newRole = Application_Model_Auth::ROLE_USER;
            }
        }
        $result = $this->_modelDb->setRole($userId, $newRole);
        return $result;
    }

    public function delete($userId)
    {
        $result = $this->_modelDb->delete($userId);
        return $result;
    }

    public function getWorkHoursByDate($userId, $date)
    {
        $modelGroup = new Application_Model_Group();
        $modelDbPlanning = new Application_Model_Db_User_Planning();

        $groups = $modelGroup->getGroupsByUserId($userId);
        $workHours = 0;
        foreach ($groups as $group) {
            $dayPlan = $modelDbPlanning->getUserDayPlanByGroup($userId, $group['id'], $date);
            if ( ! empty($dayPlan) && $dayPlan['status1'] === Application_Model_Planning::STATUS_DAY_GREEN) {
                // TODO: move this code to separate method
                $start = new DateTime($dayPlan['time_start']);
                $end   = new DateTime($dayPlan['time_end']);
                $diff = $end->diff($start);
                $workHours += $diff->format('%h'); // 8
                $decimalMinutes = $diff->format('%i');
                $decimalMinutes = $decimalMinutes / 60;
                $workHours += $decimalMinutes; // 8.11111
            }
        }
        $workHours = sprintf('%01.2f', $workHours); // 8.11
        return $workHours;
    }

    public function getUsedFreeTime($userId)
    {
        $parameters = $this->getParametersByUserId($userId);
        return $parameters['used_free_time'];
    }

    public function getAdditionalFreeTime($userId, $year)
    {
        $parameters = $this->getParametersByUserId($userId, $year);
        return $parameters['additional_free_time'];
    }

    public function getAllowedFreeTime($userId, $year)
    {
        $parameters = $this->getParametersByUserId($userId, $year);
        return $parameters['total_free_time'] - $parameters['used_free_time'] + $parameters['additional_free_time'];
    }

    public function getTotalFreeTime($userId)
    {
        $parameters = $this->getParametersByUserId($userId);
        return $parameters['total_free_time'];
    }

    public function saveRegularWorkHours($userId, $hours, $year)
    {
        $result = false;
        $modelParameters = new Application_Model_Parameter();
        if ($hours > 0 && $hours <= $modelParameters->getDefaultWorkHours() && $userId > 0) {
            $modelUserParameters = new Application_Model_Db_User_Parameters();
            $result = $modelUserParameters->setRegularWorkHours($userId, $hours, $year);
        }
        return $result;
    }

    public function addTotalFreeHoursForDayToAllUsers()
    {
        $parameters = new Application_Model_Parameter();
        $defaultWorkHours = $parameters->getDefaultWorkHours(); //40
        $defaultTotalFreeHours = $parameters->getDefaultTotalFreeHours(); //216
        $users = $this->getAllUsers();
        foreach ($users as $user) {
            //$user = new Application_Model_User();
            $currentTotalFreeTime = $this->getTotalFreeTime($user['id']);
            $user = $this->getUserById($user['id']);
            $dbParameters = new Application_Model_Db_User_Parameters();
            //  default_total_free_hours / 365 * regular_work_hours / $defaultWorkHours = free_time_for_day
            //  216 / 365 * 20 / 40 = 0.3 at one day
            $newTotalFreeTime = $currentTotalFreeTime +  $defaultTotalFreeHours/365*$user['regular_work_hours']/$defaultWorkHours * 3600;
            $dbParameters->setTotalFreeTime($user['id'], $newTotalFreeTime);
        }

        return true;
    }

    public function getUserCheckings($userId, $date)
    {

        $date = My_DateTime::factory($date);
        if ($date) {
            $checkins = $this->_modelDb->getUserCheckins($userId, $date);
        } else {
            throw new Exception('Error! Wrong date.');
        }
        return $checkins;
    }

    public function saveUserCheck($userId, $date, array $checks)
    {
        if (empty($date)) {
            throw new Exception('Error! Wrong date.');
        } else {
            $date = My_DateTime::factory($date);
        }
        if ( ! $date) {
            throw new Exception('Error! Wrong date.');
        }
        $user = $this->getUserById($userId);
        if ( ! $user) {
            throw new Exception('Error! Wrong user ID.');
        }

        $modelUserCheck = new Application_Model_Db_User_Checks();
        $errors = array();
        $toSave = array();
        foreach ($checks as $check) {
            if (empty($check['id'])) {
                continue;
            }
            $error = '';
            if ( ! empty($check['check_in']) && ! empty($check['check_in']['hours']) && ! empty($check['check_in']['mins'])) {
                $in = $check['check_in']['hours'] . ':' . $check['check_in']['mins'] . ':00';
                $in = My_DateTime::factory($in);
            }
            if ( ! empty($check['check_out']) && ! empty($check['check_out']['hours']) && ! empty($check['check_out']['mins'])) {
                $out = $check['check_out']['hours'] . ':' . $check['check_out']['mins'] . ':00';
                $out = My_DateTime::factory($out);
            }
            if ($in && $out) {
                $compare = My_DateTime::compare($in, $out);
                if ($compare <= 0) {
                    $c = $modelUserCheck->getById($check['id']);
                    if ( ! empty($c) && ! empty($c['user_id']) && $c['user_id'] == $user['id']) {
                        $toSave[$check['id']] = array(
                            'id'        => $check['id'],
                            'check_in'  => $in,
                            'check_out' => $out,
                        );
                    } else {
                        $error = 'Error! Wrong check ID or user ID.';
                    }
                } else {
                    $error = 'Error! Wrong time check IN or OUT. IN can\'t be less then OUT.';
                }
            } else {
                $error = 'Error! Wrong time check IN or OUT.';
            }
            if ( ! empty($error)) {
                $errors[$check['id']] = $error;
            }
        }
        if (empty($errors) && ! empty($toSave)) {
            foreach ($toSave as $toSaveCheck) {
                $modelUserCheck->update($toSaveCheck['id'], $toSaveCheck['check_in'], $toSaveCheck['check_out']);
            }
        }
        return $errors;
    }

    public function getUserWorkData($userId, $date)
    {
        if (empty($date)) {
            throw new Exception('Error! Wrong date.');
        } else {
            $date = My_DateTime::factory($date);
        }
        if ( ! $date) {
            throw new Exception('Error! Wrong date.');
        }
        $user = $this->getUserById($userId);
        if ( ! $user) {
            throw new Exception('Error! Wrong user ID.');
        }

        $day = Application_Model_Day::factory($date, $userId);

        $plan = $day->getWorkTime();
        $done = $day->getWorkedTime();
        $overtime = $done - $plan;
        if ($overtime < 0) {
            $overtime = 0;
        }

        $data = array(
            'work_hours_plan'     => $plan,
            'work_hours_done'     => $done,
            'work_hours_overtime' => $overtime,
        );

        return $data;
    }

    public function recalculateFreeHours($userId, $year)
    {
        $userHistory = new Application_Model_Db_User_History();
        $freeTime = $userHistory->getUsedFreeTimeByUserYear($year, $userId);
        $userParameter = new Application_Model_Db_User_Parameters();
        return $userParameter->setUsedFreeTime($userId, $freeTime['year_vacation_time']);
    }

}
