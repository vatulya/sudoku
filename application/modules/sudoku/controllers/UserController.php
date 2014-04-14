<?php

class Sudoku_UserController extends Zend_Controller_Action
{

    public $ajaxable = [
        'login'    => ['json'],
        'logout'   => ['html', 'json'],
        'register' => ['json'],
    ];

    /**
     * @var Application_Service_User
     */
    protected $serviceUser;

    public function init()
    {
        $this->_helper->getHelper('AjaxContext')->initContext();
    }

    public function preDispatch()
    {
        $this->serviceUser = Application_Service_User::getInstance();
    }

    public function indexAction()
    {
    }

    public function uLoginAction()
    {
        $token = $this->_request->getPost('token');
        $host  = $this->view->getHelper('ServerUrl')->getHost();

        $uLoginUser = $this->serviceUser->ULogin($token, $host);
        if (empty($uLoginUser)) {
            $this->redirect($this->_helper->Url->url(
                [
                    'controller' => 'index',
                    'action'     => 'index'
                ],
                'sudoku',
                true
            ));
        }
        $user = $this->serviceUser->getByData($uLoginUser);
        if (empty($user)) {
            $userId = $this->serviceUser->register($uLoginUser);
            if ($userId) {
                $user = $this->serviceUser->getById($userId);
            }
        }

        if (!empty($user)) {
            $this->serviceUser->getAuth()->login($user);
        }
        $this->redirect($this->_helper->Url->url(
            [
                'controller' => 'index',
                'action'     => 'index'
            ],
            'sudoku',
            true
        ));
    }

    public function loginAction()
    {
        $loginOrEmail = $this->_getParam('login_email');
        $password     = $this->_getParam('password');

        $userData = [
            'login'    => $loginOrEmail,
            'email'    => $loginOrEmail,
            'password' => $password,
        ];

        try {
            $errors = $this->serviceUser->getAuth()->login($userData);
        } catch (Exception $e) {
            $errors[] = [
                'name'  => 'Login form',
                'title' => 'System error',
                'text'  => 'Something wrong. System error',
            ];
        }

        if (empty($errors)) {
            $this->view->success = true;
        } else {
            $this->view->messages = $errors;
        }
    }

    public function logoutAction()
    {
        $errors = $this->serviceUser->getAuth()->logout();
        $redirector = new Zend_Controller_Action_Helper_Redirector();
        $redirector->gotoUrlAndExit('/');
    }

    public function registerAction()
    {
        $errors = [];
        try {
            $userData = $this->getAllParams();
            $check = $this->serviceUser->getByData($userData);
            if (empty($check)) {
                throw new RuntimeException('User exists');
            }
            if (!empty($userData['email'])) {
                $emailValidator = new Zend_Validate_EmailAddress();
                if (!$emailValidator->isValid($userData['email'])) {
                    throw new RuntimeException('Email invalid');
                }
            }
            if (isset($userData['password']) && isset($userData['password_repeat'])) {
                if ($userData['password'] != $userData['password_repeat']) {
                    throw new RuntimeException('Password wrong');
                }
            }

            $userId = $this->serviceUser->register($userData);
            if ($userId) {
                $errors = $this->serviceUser->getAuth()->login(['id' => $userId]);
            }
        } catch (Zend_Controller_Exception $ze) {
            $errors[] = [
                'name' => '',
                'title' => 'System error',
                'text' => $ze->getMessage(),
            ];
        } catch (Exception $e) {
            $errors[] = [
                'name' => '',
                'title' => 'System error',
                'text' => 'Something wrong. System error',
            ];
        }
        if (!empty($errors)) {
            $this->view->messages = $errors;
        }
    }

}