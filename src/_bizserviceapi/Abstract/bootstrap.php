<?php
/**
 * default bootstrap
 */
//define('APPLICATION_ENVIRONMENT', 'production');
//
// for development(axljp)
//
define('APPLICATION_ENVIRONMENT', 'development');
//
// for fj
//define('APPLICATION_ENVIRONMENT', 'fj_localhost');

//
// zend env & library path
//
defined('ABSTRACTZEND_PATH')
	or define('ABSTRACTZEND_PATH', dirname(__FILE__));

$libpath = ABSTRACTZEND_PATH;
if (APPLICATION_ENVIRONMENT == "production") {
	$libpath = ABSTRACTZEND_PATH .'/../../../../_library/'. PATH_SEPARATOR;

}else if (APPLICATION_ENVIRONMENT == "fj_localhost") {
	$libpath = ABSTRACTZEND_PATH .'/../../../../../../../localhost_modx108/_library/'. PATH_SEPARATOR;

}else{	//development
	$libpath = ABSTRACTZEND_PATH .'/../../../_library/'. PATH_SEPARATOR;
}
set_include_path($libpath . PATH_SEPARATOR . get_include_path());


// コンポーネントをロードする
require_once 'Zend/Controller/Front.php';

// FRONT CONTROLLER - Get the front controller.
// The Zend_Front_Controller class implements the Singleton pattern, which is a
// design pattern used to ensure there is only one instance of
// Zend_Front_Controller created on each request.
$frontController = Zend_Controller_Front::getInstance();

// CONTROLLER DIRECTORY SETUP - Point the front controller to your action
// controller directory.
$frontController->setControllerDirectory(APPLICATION_PATH . '/controllers');

// APPLICATION ENVIRONMENT - Set the current environment
// Set a variable in the front controller indicating the current environment --
// commonly one of development, staging, testing, production, but wholly
// dependent on your organization and site's needs.
$frontController->setParam('env', APPLICATION_ENVIRONMENT);


//set_include_path(ABSTRACTZEND_PATH .'/../../_library/'. PATH_SEPARATOR . get_include_path());
// CONFIGURATION - Setup the configuration object
require_once("Zend/Config/Ini.php");
$configuration = new Zend_Config_Ini(ABSTRACTZEND_PATH . '/config/system.ini', APPLICATION_ENVIRONMENT);
//$sessionconfig = new Zend_Config_Ini(APL_PATH . '/config/app_session.ini', APPLICATION_ENVIRONMENT);

// DATABASE ADAPTER - Setup the database adapter
require_once("Zend/Db/Table.php");
$dbAdapter = Zend_Db::factory($configuration->database);

// DATABASE TABLE SETUP - Setup the Database Table Adapter
Zend_Db_Table_Abstract::setDefaultAdapter($dbAdapter);

// REGISTRY - setup the application registry
require_once("Zend/Registry.php");
$registry = Zend_Registry::getInstance();
$registry->configuration = $configuration;
$registry->dbAdapter = $dbAdapter;
//$registry->sessionconfig = $sessionconfig;

// CLEANUP - remove items from global scope
unset($frontController, $view, $configuration, $dbAdapter, $registry, $libpath);
?>
