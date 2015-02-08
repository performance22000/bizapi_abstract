<?php
/**
 * AbstractBizBase: super class for Zend Framework Application model
 */
define('VIEW_PATH', APL_PATH . '/views');

require_once ABSTRACTZEND_PATH . "/models/Shared.php";

abstract class AbstractBizBase {

	public $modx = null;
	public $idxController = null;
	public $serviceName = null;

	protected $aplPath = null;
	protected $viewPath = null;
	protected $formAction = null;
	protected $apiName = null;

	protected $sysconfig = null;
	protected $aplconfig = null;

	//post送信でsubmitされたか
	protected $isSubmit = false;

	//ログイン情報
	protected $isLogined = false;		//ログイン済
	protected $isLoginedMgr = false;	//Mgrユーザログイン済
	protected $isLoginedWeb = false;	//Webユーザログイン済

	//セション情報
	protected $webInternalKey = 0;	//WebユーザID(内部ID)
	protected $webUsername = null;	//WebユーザのログインID
	protected $webFullname = null;	//Webユーザのフル名
	protected $webUserEmail = null;	//Webユーザのメールアドレス

	protected $veriword = null;		//captchaコード保持用($_SESSION['veriword'])

	protected $zendId = null;		//1ページに複数のZendFwスニペットよびだしに対応

	/**
	 * sub class
	 */
	public abstract function exec($request = array());

	/**
	 * constructor
	 */
	protected function __construct($modx = null, $idxController = null) {
		$this->modx = $modx;
		$this->idxController = $idxController;
		if ($modx != null) {
			$this->formAction = $modx->config['base_url'];
			$this->formAction .= "?id=" . $modx->documentIdentifier;
			//ログイン済チェック
			$this->checkLogin();
		}

		if ($idxController != null) {
			$this->aplPath = $idxController->aplPath;
			$this->serviceName = $idxController->serviceName;
			$this->apiName = $idxController->apiName;
			$this->isSubmit = $idxController->isSubmit;

		}else{
			$this->aplPath = APL_PATH;
		}
		$this->viewPath = $this->aplPath ."/views";
		//
		if (isset($_SESSION['veriword'])) {
			$this->veriword = $_SESSION['veriword'];
		}else{
			$this->veriword = false;
		}
		//
		$this->sysconfig = Zend_Registry::getInstance()->configuration;
		if (file_exists($this->aplPath . '/config/app.ini')){
			$this->aplconfig = new Zend_Config_Ini($this->aplPath . '/config/app.ini', APPLICATION_ENVIRONMENT);
		}
		//1ページ内に複数のZendFw呼出の場合の識別子
		$this->zendId = $modx->event->params["id"];

	}

	/////private methods
	private function checkLogin() {
		if (!isset($_SESSION)) {
			return false;
		}
		if (isset($_SESSION["webValidated"])) {
			$this->webInternalKey = $_SESSION["webInternalKey"];
			$this->webUsername = $_SESSION["webShortname"];
			$this->webFullname = $_SESSION["webFullname"];
			$this->webEmail =  $_SESSION["webEmail"];
			$this->isLoginedWeb = true;

		}else if (isset($_SESSION["mgrValidated"])) {
			$this->isLoginedMgr = true;

		}else{
			return false;
		}

		$this->isLogined = true;
		return true;
	}

	/////SESSION
	protected function setSessionInfoFromWebUser($fullname, $email) {
		$this->webFullname = $fullname;
		$this->webEmail =  $email;
		$_SESSION["webFullname"] = $fullname;
		$_SESSION["webEmail"] = $email;
	}
	//captchaコード比較
	protected function isCaptchaCode($captchacode = null) {
		if ($this->veriword === false) {
			return true;
		}
		if ($captchacode !== $this->veriword) {
			return false;
		}
		return true;
	}


	//MODx Placeholder
	/**
	 * 1ページに複数のZendFwスニペット呼出に対応
	 */
	protected function setPlaceholder($search, $replace, $zendid = null) {
		if (!empty($zendid)) {
			$search = "{$zendid}_{$search}";
		}else if (!empty($this->zendId)) {
			$search = $this->zendId ."_{$search}";
		}
		$this->modx->setPlaceholder($search, $replace);
	}

	//MODx View
	/**
	 * view object
	 * add141110 a.ide
	 */
	protected function createViewInstance($path = null) {
		$path = trim($path, "/");
		$view = new View();
		$view->setScriptPath($this->viewPath."/{$path}");
		return $view;
	}



	/////config
	/**
	 * apl config
	 * mod141027 a.ide
	 */
	protected function getConfig($inifilename = "app.ini", $applicationenv = APPLICATION_ENVIRONMENT, $envsuffix = null) {
		$config = null;
		$inifilename = (empty($inifilename)) ? "app.ini" : $inifilename;
		$applicationenv = (empty($applicationenv)) ? APPLICATION_ENVIRONMENT : $applicationenv;
		if (!empty($envsuffix)) {
			$applicationenv .= $envsuffix;
		}
		if (file_exists($this->aplPath . "/config/{$inifilename}")){
			$config = new Zend_Config_Ini($this->aplPath . "/config/{$inifilename}", $applicationenv);
		}else{
			return false;
		}
		return $config;
	}


	/////entrycheck
 	//長さチェク
 	protected static function isLength($request, $items, $itemname = null) {
 		$isok = true;
		//個別チェック
		if ($itemname != null) {
 			if (array_key_exists($itemname, $items)) {
				if ((strlen($request[$itemname]) > $items[$itemname])) {
					return false;
				}
				return true;
			}
			return true;
		}
		//配列をチェック
 		foreach ($request as $key => $value) {
 			if (!array_key_exists($key, $items)) {
 				continue;
 			}
			if ((strlen($value) > $items[$key])) {
	 			$isok = false;
			}
 		}
 		return $isok;
 	}



	/////DBアクセス
	/**
	 * dbアクセス ※未使用確認済 140516
	 */
/*
	protected function executeModxDB($syori = "select", $rows = array(), $where = "") {
		require_once $this->aplPath . "/models/libmodx/updateSiteContent.php";
		try{
			$db = $this->getDb();	//AbstractBizBase
			$updatedb = new UpdateSiteContent($db);
			if ($syori == "insert") {
				foreach($rows as $k => $row) {
					$updatedb->insert($row);
				}
			}else if ($syori == "update") {
				foreach($rows as $k => $row) {
					$updatedb->update($row, $where);
				}
			}else{
//				updatedb->select();
				return false;
			}
			$this->closeDb($db);
			return true;

		}catch(exception $ex){
			throw $ex;
		}
	}
 */

	protected function getDb() {
		return Zend_Registry::getInstance()->dbAdapter;
	}

	protected function closeStatement($stmt) {
		try{
			$stmt->closeCursor();
		}catch(exception $ex){
			throw $ex;
		}
	}

	protected function closeDb($db) {
		try{
			$db->closeConnection();
		}catch(exception $ex){
			throw $ex;
		}
	}

}
?>
