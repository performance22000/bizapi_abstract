<?php
/**
 * http response class
 */
class HTTPResponse {

	public $iserr = false;
	public $status = null;
	public $header = array();
	public $body = null;

	public $isContentTypeHtml = false;
	public $isContentTypeJson = false;
	public $isContentTypeXml = false;

	/**
	 * constructor
	 */
	public function __construct($response = null) {
		if ($response == null) {
			$this->iserr = true;
			return;
		}
		//header and body
		$wks = explode(CRLF.CRLF, $response, 2);
		//body
		if (count($wks) >= 2) {
			$this->body = $wks[1];
		}

		//header
		$lines = explode(CRLF, $wks[0]);
		$cnt = 0;
		foreach ($lines as $line) {
			$cols = explode(": ", $line, 2);
			if ($cols == NULL) {
				continue;
			}
			if (count($cols) == 1) {
				$this->status = $cols[0];
				continue;
			}
			if (count($cols) == 2) {
				$this->header[$cols[0]] = $cols[1];
			}
			$k = strtolower($cols[0]);
			$v = strtolower($cols[1]);
			if ($k == "content-length") {
				$this->size = $cols[1];
			}
			if ($k ==  "content-type") {
				if (strpos($v, "application/json") !== false) {
					$this->isContentTypeJson = true;
					$this->body = json_decode($this->body);

//debug a.ide
//if (!isset($this->body->access)) {
//	print_r($this->body);
//}

				}else if (strpos($v, "application/xml") !== false) {
					$this->isContentTypeXml = true;
				}else{
					$this->isContentTypeHtml = true;
				}
			}
		}

		//check error
		$stss = explode(" ", $this->status);
		if (count($stss) >= 2) {
//			if (($stss[1] < 200) || ($stss[1] >= 300)) {
			if ($stss[1] >= 300) {
				$this->iserr = true;
			}
		}
	}

	/**
	 * エラーフラグの再設定
	 * ※apiによって正常終了コードが異なるので・・・
	 */
	public function resetErrCode($rescodes = array()) {
		$stss = explode(" ", $this->status);
		if (count($stss) < 2) {
			return;
		}
		foreach ($rescodes as $code) {
			if ($stss[1] == $code) {
				$this->iserr = false;
				return;
			}
		}
		return;
	}

}
?>