<?php
/**
 * Zend FW application
 * 14/5/9 a.ide
 * 
 * MODxの以下の実行用パラメータークラス。site_contentテーブルのデータを更新するのみ
 * ・save_content.processor.php
 * ・delete_content.processor.php
 * 
 * 制限
 * ・テンプレート変数 未対応
 * ・グローバル設定 グループ管理機能を利用する「$use_udperms = 1」に未対応
 * 
 */
class SaveContentItems {
	static $ACTION_SAVE = 5;
	static $ACTION_DELETE = 6;
	
	static $MODE_INSERT = 4;
	static $MODE_UPDATE = 27;

	protected $modx = null;

	public $post = Array(
		"a" => 0,
		"id" => 0,
		"pid" => 0,
		"mode" => 0,
		"MAX_FILE_SIZE" => 2097152,
		"refresh_preview" => 0,
		"newtemplate" => "",	//※保留中
		"stay" => "",
		"pagetitle" => "",
		"longtitle" => "",
		"description" => "",
		"alias" => "",
		"introtext" => "",
		"template" => 6,
		"menutitle" => "",
		"menuindex" => 0,
		"hidemenucheck" => "on",
		"hidemenu" => 0,
		"parent" => 141,
		"ta" => "",
		"which_editor" => "none",
		"published" => 0,
		"pub_date" => "",
		"unpub_date" => "",
		"type" => "document",
		"contentType" => "text/html",
		"content_dispo" => 0,
		"link_attributes" => "",
		"isfolder" => 0,
		"richtextcheck" => "on",
		"richtext" => 1,
		"donthitcheck" => "on",
		"donthit" => 0,
		"searchablecheck" => "on",
		"searchable" => 1,
		"cacheable" => 0,
		"syncsitecheck" => "on",
		"syncsite" => 1,
		"chkalldocs" => "on",
		"save" => "送信"
	);
	
	public function getId() {
		return $this->post["id"];
	}
	
	public $isDocument = false;

	/**
	 * コンストラクタ
	 */
	public function __construct($modx) {
		$this->modx = $modx;
		//デフォルトはオープニングドキュメント
		$this->post["pid"] = $modx->config["site_start"];
		$this->post["parent"] = $modx->config["site_start"];
		//デフォルトのテンプレートID
		$this->post["template"] = $modx->config["default_template"];
	}

	/**
	 * ドキュメント公開/非公開にする
	 */
	public function setPublished($id, $ison = true) {
		try{
			$doc = $this->getDocument($id, true);
			if ($doc === false) {
				return false;
			}
			//公開/非公開フラグの設定
			$request = array();
			$request["id"] = $id;
			$request["published"] = ($ison) ? 1 : 0;
			return $this->saveContent($request);
	
		}catch(exception $ex) {
			throw $ex;
		}
	}

	/**
	 * ドキュメント メニュー表示/非表示にする
	 */
	public function setShowMenu($id, $ison = true) {
		try{
			$doc = $this->getDocument($id, true);
			if ($doc === false) {
				return false;
			}
			//表示/非表示フラグの設定
			$request = array();
			$request["id"] = $id;
			$request["hidemenu"] = ($ison) ? 0 : 1;
			return $this->saveContent($request);
	
		}catch(exception $ex) {
			throw $ex;
		}
	}



	/**
	 * ドキュメントの検索
	 */
//	function getDocument($id= 0, $fields= '*', $published= 1, $deleted= 0)
 	public function getDocument($id, $isforce = false) {
		try{
			//非公開ドキュメントも検索対象
			$published = ($isforce) ? 9 : 1;
			//検索
			$doc = $this->modx->getDocument($id, "*", $published);
			if ($doc === false) {
				$this->isDocument = false;
				return false;
			}
			$this->isDocument = true;
			foreach ($doc as $key => $value) {
				if (array_key_exists($key, $this->post)) {
					if ($key == "parent") {
						$this->post["pid"] = $value;
					}
					$this->post[$key] = $value;
				}else{
					if ($key == "content") {
						$this->post["ta"] = $value;
					}
				}
			}
			return $doc;
			
		}catch(exception $ex) {
			throw $ex;
		}
	}

	/**
	 * 更新処理の実行
	 * ※修正の場合は事前にgetDocument()を実行すること
	 */
	public function saveContent($request = array(), $isNew = false) {
		try{
			//更新モード＆読込phpファイル
			$this->post["a"] = SaveContentItems::$ACTION_SAVE;
			//更新チェック
			if ($isNew) {
				$this->post["mode"] = SaveContentItems::$MODE_INSERT;
				
			}else{
				$this->post["mode"] = SaveContentItems::$MODE_UPDATE;
				if (!$this->isDocument) {
					return false;
				}
			}

			//$_POST再設定
			foreach ($request as $key => $value) {
				if ($key == "content") {
					$_POST["ta"] = $value;
				}else{
					$_POST[$key] = $value;
				}
			}
			//$_POST設定
			foreach ($this->post as $key => $value) {
				if (!array_key_exists($key, $_POST)) {
					$_POST[$key] = $value;
				}
			}

			//MODxの更新処理を実行
			require_once ABSTRACTZEND_PATH ."/models/lib/saveContent/save_content.processor.php";
			//
			$this->post["id"] = (isset($newid)) ? $newid : $id;
			return $this->post["id"];

		}catch(exception $ex) {
			throw $ex;
		}
	}
	
	/**
	 * 削除処理の実行(削除フラグを設定)
	 * ※事前にgetDocumentで検索を実行すること
	 */	
	public function deleteContentById($id) {
		try{
			if ($id == 0) {
				return false;
			}
//保留		if ($this->isDocument === false) {
//				return;
//			}
			//削除＆読込phpファイル
			$this->post["a"] = SaveContentItems::$ACTION_DELETE;
//			$this->post["id"] = $id;
			//MODxの更新処理を実行
			require_once ABSTRACTZEND_PATH ."/models/lib/saveContent/delete_content.processor.php";
			//
			return $id;
			
		}catch(exception $ex){
			throw $ex;
		}
	}

}
?>
