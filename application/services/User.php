<?php

/**
 * Class Application_Service_User
 *
 * @method Application_Model_Db_Users getModelDb()
 */
class Application_Service_User extends Application_Service_Abstract
{

    const DB_MODEL_NAME = 'Users';

    /**
     * @return array
     */
    public function getCurrentUser()
    {
        return My_Auth_User::getInstance()->getCurrentUser();
    }

    /**
     * @param $id
     * @return array
     */
    public function getById($id)
    {
        return $this->getModelDb()->getOne(array('id' => $id));
    }

    /**
     * @param $login
     * @return array
     */
    public function getByLogin($login)
    {
        return $this->getModelDb()->getOne(array('login' => $login));
    }

    /**
     * @param $email
     * @return array
     */
    public function getByEmail($email)
    {
        return $this->getModelDb()->getOne(array('email' => $email));
    }

    /**
     * @param array $userData
     * @return array
     */
    public function register(array $userData)
    {
        $errors = array();
        if (empty($userData['login']) && empty($userData['email'])) {
            $errors[] = array(
                'name' => 'login-email',
                'title' => 'Login or Email',
                'text' => 'You should enter Login or Email',
            );
        } elseif (!empty($userData['email'])) {
            $emailValidator = new Zend_Validate_EmailAddress();
            if (!$emailValidator->isValid($userData['email'])) {
                $errors[] = array(
                    'name' => 'login-email',
                    'title' => 'Email',
                    'text' => 'Incorrect Email',
                );
            }
        }
        if (empty($userData['password']) || empty($userData['password_repeat'])) {
            $errors[] = array(
                'name' => 'password',
                'title' => 'Password',
                'text' => 'You should enter Password and repeat password.',
            );
        } elseif ($userData['password'] != $userData['password_repeat']) {
            $errors[] = array(
                'name' => 'password-repeat',
                'title' => 'Password',
                'text' => 'Repeat password must be the same as Password',
            );
        }

        if (empty($errors)) {
            if (!empty($userData['login'])) {
                $check = $this->getModelDb()->getOne(array('login' => $userData['login']));
                if ($check) {
                    $errors[] = array(
                        'name' => 'login-email',
                        'title' => 'Login',
                        'text' => 'Sorry. This Login already exists in database. Please choose another.',
                    );
                }
            }
            if (!empty($userData['email'])) {
                $check = $this->getModelDb()->getOne(array('email' => $userData['email']));
                if ($check) {
                    $errors[] = array(
                        'name' => 'login-email',
                        'title' => 'Email',
                        'text' => 'Sorry. This Email already exists in database. Please choose another.',
                    );
                }
            }

            $nonEncodedPassword = $userData['password'];
            if (empty($errors)) {
                $userData['password'] = My_Auth_Adapter_Main::encodePassword($userData['password']);
                $result = $this->getModelDb()->insert($userData);
                if (!$result) {
                    $errors[] = array(
                        'name' => 'register',
                        'title' => 'System',
                        'text' => 'Something wrong. Error.',
                    );
                }
            }
            if (empty($errors)) {
                $loginOrEmail = $userData['login'] ?: $userData['email'];
                $errors = My_Auth_User::getInstance()->login($loginOrEmail, $nonEncodedPassword);
                if (!empty($errors)) {
                    array_unshift($errors, array(
                        'name' => 'system',
                        'title' => 'System',
                        'text' => 'Something wrong. You have registered. Auto-login error. Please try login manually.',
                    ));
                }
            }
        }

        return $errors;
    }

    /**
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function changePassword($userId, $newPassword)
    {
        $newPasswordEncoded = My_Auth_Adapter_Main::encodePassword($newPassword);
        $result = $this->getModelDb()->update($userId, array('password' => $newPasswordEncoded));
        return $result;
    }

}
