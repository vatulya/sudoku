<?php

class Sudoku_IndexController extends Zend_Controller_Action
{

    public $ajaxable = array(
        'index'       => array('html'),
        'check-field' => array('json'),
    );

    public function init()
    {
        $this->_helper->getHelper('AjaxContext')->initContext();
    }

    public function preDispatch()
    {
    }

    public function postDispatch()
    {
        $uLoginRedirectUrl = $this->view->serverUrl();
        $uLoginRedirectUrl .= $this->_helper->Url->url(
            array(
                'controller' => 'user',
                'action'     => 'u-login'
            ),
            'sudoku',
            true
        );
        $this->view->assign(array(
            'uLoginData'   => Application_Service_ULogin::getLoginData($uLoginRedirectUrl),
            'difficulties' => Application_Model_Game_Sudoku::getAllDifficulties(),
            'user'         => Application_Service_User::getInstance()->getCurrentUser(),
        ));
    }

    public function indexAction()
    {
        /** @var Application_Model_Game_Abstract $sudoku */
        $sudoku = new Application_Model_Game_Sudoku();

        $difficulty = $this->_request->getParam('difficulty');
        $sudoku->setDifficulty($difficulty);

        $sudoku->createGame(Application_Service_User::getInstance()->getCurrentUser());
        $sudoku->setState(Application_Model_Game_Abstract::STATE_IN_PROGRESS);

        $this->view->assign(array(
            'sudoku'            => $sudoku,
            'currentDifficulty' => $sudoku->getDifficulty(),
        ));
    }

    public function checkFieldAction()
    {
        $cells = $this->_getParam('cells');
        $errors = Application_Model_Game_Sudoku::checkGameSolution($cells);
        if (is_array($errors)) {
            $this->view->errors = $errors;
        } else {
            $this->view->resolved = (bool)$errors; // TRUE if resolved
        }
    }

}