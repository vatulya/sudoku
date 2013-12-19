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

    public function indexAction()
    {
        /** @var Application_Model_Sudoku $sudoku */
        $sudoku = Application_Model_Sudoku::getInstance();
        $openCells = $sudoku->createGame();
        $this->view->openCells = $openCells;
    }

    public function checkFieldAction()
    {
        $cells = $this->_getParam('cells');
        /** @var Application_Model_Sudoku $sudoku */
        $sudoku = Application_Model_Sudoku::getInstance();
        $errors = $sudoku->checkField($cells);
        $this->view->errors = $errors;
    }

}