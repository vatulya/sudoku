<?php
date_default_timezone_set('UTC');
ini_set('session.gc_maxlifetime', 43200); // does't remove sessions till 6 hours

// Define path to application directory
defined('APPLICATION_PATH')
|| define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

require_once APPLICATION_PATH . '/../vendor/autoload.php';

/** Zend_Application */
require_once 'Zend/Application.php';

// Creating application
$application = new Zend_Application(
    APPLICATION_ENV,
    array(
        'config' => array(
            APPLICATION_PATH . '/configs/application.yaml',
        )
    )
);

$application->setBootstrap(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'BootstrapConsole.php', 'BootstrapConsole');

// Bootstrapping resources
$application->bootstrap();
