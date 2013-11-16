<?php

class Planner_IndexController extends My_Controller_Action
{

    public $ajaxable = array(
        'index' => array('html'),
    );

    protected $_modelUser;

    public function init()
    {
        $this->_modelUser = new Application_Model_User();
        $group = $this->_getParam('group');
        $allowed = false;
        if ($this->_me['role'] >= Application_Model_Auth::ROLE_ADMIN) {
            $allowed = true;
        } elseif ($group && in_array($group, $this->_me['admin_groups'])) {
            $allowed = true;
        }
        if ( ! $allowed) {
            $this->_setParam('user', $this->_me['id']);
        }
        parent::init();
    }

    public function indexAction()
    {
        $this->_forward('index', 'checking', null, array('controller' => 'checking'));
    }

}