<?php
/**
 * ファイルアップロード付スーパークラス
 * update 141106 a.ide
 */
require_once ABSTRACTZEND_PATH . "/models/AbstractBizBase.php";

abstract class AbstractBizBaseUploadFile extends AbstractBizBase {

	protected $uploadDir = null;
	protected $maxFileSize = 0;
	protected $adapter = null;

	public $uploadSize = 0;

	/**
	 * constructor
	 */
	protected function __construct($modx = null, $idxController = null) {
		parent::__construct($modx, $idxController);
	}

	//

	public function getFileInfo() {
		return $this->adapter->getFileInfo();
	}

	//upload file

	protected function preUpload($request, $uploaddir, $ismkdir = false, $maxfilesize = 0) {
		try{
			extract($request[$this->serviceName]);	//MAX_FILE_SIZE

			//アップロードディレクトリのチェック
			if (empty($uploaddir)) {
				throw new Exception("uploaddir is null.");
			}
			if (!file_exists($uploaddir)){
				if ($ismkdir) {
					mkdir($uploaddir);
//					chmod($uploaddir, 777);
				}else{
					require_once 'Zend/Exception.php';
					throw new Zend_Exception("no directory. $uploaddir");
				}
			}

			$this->uploadDir = $uploaddir;
			if ($maxfilesize > 0) {
				$this->maxFileSize = $maxfilesize;

			}else if (isset($MAX_FILE_SIZE)) {
				$this->maxFileSize = $MAX_FILE_SIZE;

			}else{
				$this->maxFileSize = 20971852;	//default: 2M
			}

			require_once ABSTRACTZEND_PATH.'/controllers/lib/file/TransferAdapterHttp.php';
			$this->adapter = new FileTransferAdapterHttp();

			return $this->adapter;

		}catch(Exception $ex) {
			throw $ex;
		}
	}

	protected function uploadFiles($uploaddir = null) {
		try{
			//ファイル保存先
			$this->uploadDir = (empty($uploaddir)) ? $this->uploadDir : $uploaddir;
			if (empty($this->uploadDir)) {
				throw new Exception("uploaddir is null.");
			}

			//サイズチェック
			if ($this->maxFileSize > 0) {
				$sumsize = 0;
				foreach($this->adapter->getFileInfo() as $k => $info){
					extract($info);
					$sumsize += $size;
				}
				if ($sumsize > $this->maxFileSize) {
					throw new Exception("upload size over. {$sumsize} max=".$this->maxFileSize);
				}
			}else{
				throw new Exception("maxFileSize is 0");
			}

			//ファイルアップロード実行(ファイルを保存)
			if (!file_exists($this->uploadDir)) {
				throw new Exception("no directory. ".$this->uploadDir);
			}
			$this->adapter->setDestination($this->uploadDir);
			if (!$this->adapter->receive()) {
				require_once 'Zend/Exception.php';
				throw new Zend_Exception("cannt upload. $uploaddir");
			}

			$this->uploadSize = $sumsize;
			return true;

		}catch(exception $ex){
			throw $ex;
		}
	}

}
?>
