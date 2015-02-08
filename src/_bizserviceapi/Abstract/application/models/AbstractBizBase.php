<?php
/**
 * AbstractBizBase: super class for Zend Framework Application model
 */
require_once ABSTRACTZEND_PATH."/models/Functions.php";

abstract class AbstractBizBase {

	protected $projectName = "TOOLS";

	protected $serviceName = null;
	protected $aplPath = null;
	protected $viewPath = null;

	protected $sysConfig = null;
	protected $aplConfig = null;

	/**
	 * sub class
	 */
	public abstract function exec($request = array());

	/**
	 * constructor
	 */
	protected function __construct($controller) {
		$this->serviceName = $controller->serviceName;
		$this->aplPath = $controller->aplPath;
		$this->viewPath = $controller->viewPath;

		$this->sysConfig = $controller->sysConfig;

		$inifile = $this->aplPath.'/config/app.ini';
		if (file_exists($inifile)) {
			$this->aplConfig = new Zend_Config_Ini($inifile, APPLICATION_ENVIRONMENT);
		}
	}

}
?>
