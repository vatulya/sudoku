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

    }

    public function indexAction()
    {
    }

    public function loginAction()
    {
        $loginEmail = $this->_getParam('login_email');
        $password = $this->_getParam('password');
        /** @var Application_Model_Auth $auth */
        $auth = Application_Model_Auth::getInstance();
        $result = $auth->login($loginEmail, $password);
        /** @var Application_Model_User $user */
        $user = Application_Model_User::getInstance();


        if (is_array($errors)) {
            $this->view->errors = $errors;
        } else {
            $this->view->resolved = (bool)$errors; // TRUE if resolved
        }
    }

}