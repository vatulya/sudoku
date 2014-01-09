<?php

class Sudoku_UserController extends Zend_Controller_Action
{

    public $ajaxable = array(
        'login'    => array('json'),
        'register' => array('json'),
    );

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
        /** @var Application_Model_Auth $auth */
        $user = Application_Model_Auth::getInstance()->login($loginEmail, $password);
        $errors = array();
        if (!$user) {
            $errors[] = array(
                'name'  => 'Login form',
                'title' => 'Login',
                'text'  => 'Wrong login or password',
            );
        }

        if (!empty($errors)) {
            $this->view->errors = $errors;
        }
    }

}