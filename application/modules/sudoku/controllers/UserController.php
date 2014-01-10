<?php

class Sudoku_UserController extends Zend_Controller_Action
{

    public $ajaxable = array(
        'login'    => array('json'),
        'register' => array('json'),
    );

    /**
     * @var Application_Model_User
     */
    protected $_modelUser;

    public function init()
    {
        $this->_helper->getHelper('AjaxContext')->initContext();
    }

    public function preDispatch()
    {
        $this->_modelUser = Application_Model_User::getInstance();
    }

    public function indexAction()
    {
    }

    public function loginAction()
    {
        $loginEmail = $this->_getParam('login_email');
        $password = $this->_getParam('password');
        $errors = array();
        try {
            /** @var Application_Model_Auth $auth */
            $errors = Application_Model_Auth::getInstance()->login($loginEmail, $password);
        } catch (Exception $e) {
            $errors[] = array(
                'name'  => 'Login form',
                'title' => 'System error',
                'text'  => 'Something wrong. System error',
            );
        }

        if (!empty($errors)) {
            $this->view->errors = $errors;
        }
    }

    public function registerAction()
    {
        $login = $this->_getParam('login');
        $email = $this->_getParam('email');
        $password = $this->_getParam('password');
        $password2 = $this->_getParam('password_repeat');
        $userData = array(
            'login'     => $login,
            'email'     => $email,
            'password'  => $password,
            'password2' => $password2,
        );
        try {
            $errors = $this->_modelUser->register($userData);
        } catch (Exception $e) {
            $errors[] = array(
                'name' => '',
                'title' => 'System error',
                'text' => 'Something wrong. System error',
            );
        }
        if (empty($errors)) {
            /** @var Application_Model_Auth $auth */
            $user = Application_Model_Auth::getInstance()->login($login, $password);
        } else {
            $this->view->errors = $errors;
        }
    }

}