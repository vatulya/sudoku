<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    protected function _initUserSession()
    {
        $session = new Zend_Session_Namespace();
        $session->authRole = Application_Model_Auth::getRole();
    }

    protected function _initAcl()
    {
        return include APPLICATION_PATH . '/configs/acl.php';
    }

    protected function _initRoute()
    {

        $router = Zend_Controller_Front::getInstance()->getRouter();
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/routes.ini', 'production');
        $router->addConfig($config, 'routes');
    }

}

