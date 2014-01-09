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

    }

    public function postDispatch()
    {
        /** @var Application_Model_Auth $auth */
        $auth = Application_Model_Auth::getInstance();
        $this->view->user = $auth->getCurrentUser();
    }

    public function indexAction()
    {
        /** @var Application_Model_Sudoku $sudoku */
        $sudoku = Application_Model_Sudoku::getInstance();

        $difficulties = $sudoku->getAllDifficulties();
        $this->view->difficulties = $difficulties;

        /** @var Zend_Controller_Request_Abstract $request */
        $request = $this->_request;
        $difficulty = $request->getParam('difficulty');
        if (!isset($difficulties[$difficulty])) {
            $difficulty = $sudoku::DEFAULT_GAME_DIFFICULTY;
        }
        $this->view->currentDifficulty = $difficulty;

        $game = $sudoku->createGame($difficulty);
        $this->view->sudoku = $game;
    }

    public function checkFieldAction()
    {
        $cells = $this->_getParam('cells');
        /** @var Application_Model_Sudoku $sudoku */
        $sudoku = Application_Model_Sudoku::getInstance();
        $errors = $sudoku->checkGameSolution($cells);
        if (is_array($errors)) {
            $this->view->errors = $errors;
        } else {
            $this->view->resolved = (bool)$errors; // TRUE if resolved
        }
    }

}