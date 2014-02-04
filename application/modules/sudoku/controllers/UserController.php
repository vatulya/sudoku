<?php

class Sudoku_UserController extends Zend_Controller_Action
{

    public $ajaxable = array(
        'login'    => array('json'),
        'logout'   => array('html', 'json'),
        'register' => array('json'),
    );

    /**
     * @var Application_Service_User
     */
    protected $_serviceUser;

    public function init()
    {
        $this->_helper->getHelper('AjaxContext')->initContext();
    }

    public function preDispatch()
    {
        $this->_serviceUser = Application_Service_User::getInstance();
    }

    public function indexAction()
    {
    }

    public function uLoginAction()
    {
        $token = $this->_request->getPost('token');
        $host  = $this->view->getHelper('ServerUrl')->getHost();

        $user = Application_Service_ULogin::getInstance()->login($token, $host);
        if (empty($user)) {
            $this->redirect($this->_helper->Url->url(
                array(
                    'controller' => 'index',
                    'action'     => 'index'
                ),
                'sudoku',
                true
            ));
        }

        My_Auth_User::getInstance()->loginOther($user);
        $this->redirect($this->_helper->Url->url(
            array(
                'controller' => 'index',
                'action'     => 'index'
            ),
            'sudoku',
            true
        ));
    }

    public function loginAction()
    {
        $loginOrEmail = $this->_getParam('login_email');
        $password     = $this->_getParam('password');

        try {
            $errors = My_Auth_User::getInstance()->login($loginOrEmail, $password);
        } catch (Exception $e) {
            $errors[] = array(
                'name'  => 'Login form',
                'title' => 'System error',
                'text'  => 'Something wrong. System error',
            );
        }

        if (empty($errors)) {
            $this->view->success = true;
        } else {
            $this->view->messages = $errors;
        }
    }

    public function logoutAction()
    {
        $errors = My_Auth_User::getInstance()->logout();
        $redirector = new Zend_Controller_Action_Helper_Redirector();
        $redirector->gotoUrlAndExit('/');
    }

    public function registerAction()
    {
        try {
            $errors = $this->_serviceUser->register($this->getAllParams());
        } catch (Exception $e) {
            $errors[] = array(
                'name' => '',
                'title' => 'System error',
                'text' => 'Something wrong. System error',
            );
        }
        if (!empty($errors)) {
            $this->view->messages = $errors;
        }
    }

}