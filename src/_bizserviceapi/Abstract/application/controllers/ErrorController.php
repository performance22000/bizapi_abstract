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
class ErrorController extends Zend_Controller_Action{

	public function indexAction() {
		print "ErrorController.indexAction()<br>\n";
	}

	public function errorAction() {
		print "ErrorController.errorAction()<br>\n";

		$message = "";
		foreach($this->getResponse()->getException() as $k => $ex) {
			$wks = explode("#", $ex);
			$com1 = "<b>";
			$com2 = "</b>";
			foreach($wks as $n => $wk) {
				$msg = "{$com1}{$wk}{$com2}<br>\n";
				$msg = str_replace("/home/alive", "", $msg);
				$message .= $msg;
				$com1 = "#";
				$com2 = "";
			}
//			break;
		}
		print "{$message}<br>\n";
//		print_r($this);
	}

}
?>
