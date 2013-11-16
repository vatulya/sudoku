<?php

class Sudoku_IndexController extends Zend_Controller_Action
{

    public $ajaxable = array(
        'index' => array('html'),
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
        $a = 1;
    }

}