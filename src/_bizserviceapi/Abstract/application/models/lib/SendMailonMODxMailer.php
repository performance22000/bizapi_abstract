<?php
/**
 * Zend FW application
 * 14/5/24 a.ide
 * 
 * MODxの以下の実行用パラメータークラス。メール送信処理
 * ・MODxMailer.php
 * 
 */
class SendMailonMODxMailer {
	
	private $modx = null;
	
	public $isSend = false;	//実際にメール送信する
	
	/**
	 * コンストラクタ
	 */
	public function __construct($modx, $params, $msg, $issend = false) {
//		global $modx;
		if(isset($params) && is_string($params)) {
			if(strpos($params, '=') === false) {
				if(strpos($params,'@')!==false) $p['to']	  = $params;
				else                            $p['subject'] = $params;
			}else{
				$params_array = explode(',',$params);
				foreach($params_array as $k=>$v) {
					$k = trim($k);
					$v = trim($v);
					$p[$k] = $v;
				}
			}
		}else{
			$p = $params;
			unset($params);
		}
		if (isset($p['sendto'])) $p['to'] = $p['sendto'];
		
		if (isset($p['to']) && preg_match('@^[0-9]+$@', $p['to'])) {	//"to"がユーザIDの場合
			$userinfo = $modx->getUserInfo($p['to']);
			$p['to'] = $userinfo['email'];
		}
		if (isset($p['from']) && preg_match('@^[0-9]+$@', $p['from'])) {//"from"がユーザIDの場合
			$userinfo = $modx->getUserInfo($p['from']);
			$p['from']	 = $userinfo['email'];
			$p['fromname'] = $userinfo['username'];
		}
		if ($msg === '' && !isset($p['body'])) {	//$msgがある、かつ"body"がない場合
			$p['body'] = $_SERVER['REQUEST_URI'] . "\n" . $_SERVER['HTTP_USER_AGENT'] . "\n" . $_SERVER['HTTP_REFERER'];
		}
		elseif (is_string($msg) && 0 < strlen($msg)) $p['body'] = $msg;

		$modx->loadExtension('MODxMailer');
		$sendto = (!isset($p['to'])) ? $modx->config['emailsender'] : $p['to'];
		$sendto = explode(',', $sendto);
		foreach($sendto as $address) {
			list($name, $address) = $modx->mail->address_split($address);
			$modx->mail->AddAddress($address, $name);
		}
		if(isset($p['cc'])) {
//			$p['cc'] = explode(',', $sendto);
			$p['cc'] = explode(',', $p["cc"]);
			foreach($p['cc'] as $address) {
				list($name, $address) = $modx->mail->address_split($address);
				$modx->mail->AddCC($address, $name);
			}
		}
		if(isset($p['bcc'])) {
//			$p['bcc'] = explode(',', $sendto);
			$p['bcc'] = explode(',', $p["bcc"]);
			foreach($p['bcc'] as $address) {
				list($name, $address) = $modx->mail->address_split($address);
				$modx->mail->AddBCC($address, $name);
			}
		}
		if(isset($p['from'])) list($p['fromname'], $p['from']) = $modx->mail->address_split($p['from']);
		$modx->mail->From	 = (!isset($p['from']))  ? $modx->config['emailsender']  : $p['from'];
		$modx->mail->FromName = (!isset($p['fromname'])) ? $modx->config['site_name'] : $p['fromname'];
		$modx->mail->Subject  = (!isset($p['subject']))  ? $modx->config['emailsubject'] : $p['subject'];
		$modx->mail->Body	 = $p['body'];
		//
		$this->modx = $modx;
		$this->isSend = $issend;
		return;
	}

	public function sendmail() {
		if ($this->modx === null) {
			return false;
		}
		if ($this->isSend) {
			$rs = $this->modx->mail->send();
		}else{
			$rs = true;
		}
		return $rs;
	}
	
	
}
?>
