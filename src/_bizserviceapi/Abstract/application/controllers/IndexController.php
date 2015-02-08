<?php
/**
 * ZendFWの利用：URLから直接ZendFWアプリを実行するときに利用する。
 * MODxからは利用不可。
 * 2015/1/18 a.ide create
 */
require_once 'Zend/Controller/Action.php';

//ビュークラス
require_once ABSTRACTZEND_PATH .'/controllers/lib/View.php';

/**
 * ZendFWのコントローラー
 */
class IndexController extends Zend_Controller_Action{

	public function indexAction() {
		print "IndexController.indexAction()<br>\n";

		//リクエストパラメーターの取得
		$request = $this->getRequest()->getParams();
		print_r($request);

	}

	public function apiAction() {
		print "IndexController.apiAction()<br>\n";

		//リクエストパラメーターの取得
		$request = $this->getRequest()->getParams();
		print_r($request);

	}

}
?>
