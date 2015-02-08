<?php
/**
 * ZendFWの利用：URLから直接ZendFWアプリを実行するときに利用する。
 * MODxからは利用不可。
 * 2015/1/18 a.ide create
 */
require_once 'Zend/Controller/Action.php';
require_once 'Zend/Exception.php';

//ビュークラス
require_once ABSTRACTZEND_PATH.'/controllers/lib/View.php';

/**
 * ZendFWのコントローラー
 */
class ApiController extends Zend_Controller_Action{

	public $serviceName = null;
	public $aplPath = null;
	public $viewPath = null;

	public $sysConfig = null;

	//
	public function phpinfoAction() {
		print phpinfo();
	}

	//
	public function indexAction() {
		try{
			//config
			$registry = Zend_Registry::getInstance();
			$this->sysConfig = $registry->configuration;

			//リクエストパラメーターの取得
			$request = $this->getNewRequest($this->getRequest()->getParams());

			//apiのインスタンス化と実行
			$service = $this->getBizservice($request);
			$res = $service->exec($request);

			echo $res;

		}catch(Exception $ex) {
			echo "ERR: ".$ex->getMessage();
			throw $ex;
		}
	}


	/////private

	//ZendFW固有のリクエストは省く
	private function getNewRequest($request = array()) {
		$zendfw = array("controller", "action", "module");
		$newrequest = array();
		foreach ($request as $key => $value) {
			if (in_array($key, $zendfw)) {
				continue;
			}
			$newrequest[$key] = $value;
		}
		return $newrequest;
	}

	//apiインスタンスの実行
	private function getBizservice($request = array()) {
		try{
			$baseurl = $this->getRequest()->getBaseUrl();
			$requesturi = $this->getRequest()->getRequestUri();

			//ファイル存在チェック
			$wk = str_replace($baseurl, "", $requesturi);
			$wks = explode("?", $wk, 2);
			$paths = explode("/", $wks[0]);
			$cnt = count($paths);
			if ($cnt < 4 ) {
				throw new Zend_Exception("unknown apiname {$wk}");
			}

			$this->serviceName = $paths[3];
			if ($cnt < 5) {
				$procpath = "";
				$classname = "index";

			}else if ($cnt < 6) {
				$procpath = "";
				$classname = (empty($paths[4])) ? "index" : $paths[4];

			}else{
				if (empty($paths[5])) {
					$procpath = "";
					$classname = (empty($paths[4])) ? "index" : $paths[4];
				}else{
					$procpath = "/{$paths[4]}";
					$classname = $paths[5];
				}
			}

			$file = ABSTRACTZEND_PATH."/../..";
			$file = "{$file}/".$this->serviceName."/application/models{$procpath}/{$classname}.php";
			if (!file_exists($file)) {
				throw new Zend_Exception("unknown file {$file}");
			}

			//クラス存在チェック（ビジネスロジックの実行準備）
			require_once($file);
			if (!class_exists($classname)) {
				throw new Zend_Exception("unknown class {$classname}");
			}

			//共通情報の設定
			$this->aplPath = ABSTRACTZEND_PATH."/../../".$this->serviceName."/application";
			$this->viewPath = $this->aplPath."/views/{$procpath}";

			//クラスのインスタンス化
			return new $classname($this);

		}catch(Exception $ex) {
			throw $ex;
		}
	}


}
?>
