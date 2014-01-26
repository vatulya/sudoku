<?php

/**
 * Class Application_Model_User
 *
 * @method Application_Model_Db_Users getModelDb()
 */
class Application_Model_User extends Application_Model_Abstract
{

    const DB_MODEL_NAME = 'Users';

    /**
     * @var Application_Model_Db_Users
     */
    protected $_modelDb;

    public function getById($id)
    {
        return $this->getModelDb()->getById($id);
    }

    /**
     * @param array $userData
     * @return array|bool
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
        if (empty($userData['password']) || empty($userData['password2'])) {
            $errors[] = array(
                'name' => 'password',
                'title' => 'Password',
                'text' => 'You should enter Password and repeat password',
            );
        } elseif ($userData['password'] != $userData['password2']) {
            $errors[] = array(
                'name' => 'password-repeat',
                'title' => 'Password',
                'text' => 'Repeat password must be the same as Password',
            );
        }

        if (empty($errors)) {
            if (!empty($userData['login'])) {
                $check = $this->getModelDb()->getByLogin($userData['login']);
                if ($check) {
                    $errors[] = array(
                        'name' => 'login-email',
                        'title' => 'Login',
                        'text' => 'Sorry. This Login already exists in database. Please choose another',
                    );
                }
            }
            if (!empty($userData['email'])) {
                $check = $this->getModelDb()->getByEmail($userData['email']);
                if ($check) {
                    $errors[] = array(
                        'name' => 'login-email',
                        'title' => 'Email',
                        'text' => 'Sorry. This Email already exists in database. Please choose another',
                    );
                }
            }

            if (empty($errors)) {
                $userData['password'] = Application_Model_AuthAdapter::encodePassword($userData['password']);
                unset($userData['password2']);
                $result = $this->getModelDb()->insert($userData);
            }
        }

        return $errors;
    }

}
