<?php

class Sudoku_IndexController extends Zend_Controller_Action
{

    public $ajaxable = array(
        'index' => array('html'),
        'check-field' => array('json'),
    );

    protected $_modelUser;

    public function init()
    {
        $this->_helper->getHelper('AjaxContext')->initContext();
    }

    public function preDispatch()
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
        $this->view->uLoginData = Application_Model_ULogin::getLoginData($uLoginRedirectUrl);
    }

    public function postDispatch()
    {
        $this->view->user = Application_Model_Auth::getInstance()->getCurrentUser();
    }

    public function indexAction()
    {
        /** @var Application_Model_Game_Abstract $sudoku */
        $sudoku = Application_Model_Game_Abstract::factory(Application_Model_Game_Sudoku::GAME_CODE);

        $difficulties = $sudoku->getAllDifficulties();
        $this->view->difficulties = $difficulties;

        /** @var Zend_Controller_Request_Abstract $request */
        $request = $this->_request;
        $difficulty = $request->getParam('difficulty');
        $sudoku->setDifficulty($difficulty);
        $this->view->currentDifficulty = $sudoku->getDifficulty();

        $sudoku->createGame(Application_Model_Auth::getInstance()->getCurrentUser());
        $this->view->sudoku = $sudoku;
    }

    public function checkFieldAction()
    {
        $cells = $this->_getParam('cells');
        /** @var Application_Model_Game_Abstract $sudoku */
        $sudoku = Application_Model_Game_Abstract::factory(Application_Model_Game_Sudoku::GAME_CODE);
        $errors = $sudoku->checkGameSolution($cells);
        if (is_array($errors)) {
            $this->view->errors = $errors;
        } else {
            $this->view->resolved = (bool)$errors; // TRUE if resolved
        }
    }

}