<?php
define("CRLF", "\r\n");
define("POST", "POST");

include_once("HTTPResponse.inc.php");

/**
 * renew: 140916 a.ide httpリクエストをcurl_xx関数で実行するように変更
 */
class HTTPRequest {

	static $CONTENT_TYPE_HTML = "text/html;charset=\"utf-8\"";
	static $CONTENT_TYPE_XML = "text/xml;charset=\"utf-8\"";
	static $CONTENT_TYPE_JSON = "application/json";


	public $iserr = false;

	private $isContentTypeJson = false;
	private $isContentTypeHtml = false;

	public $url = null;

	public $proxyUrl = null;
	public $headers = array();
	public $curlOptions = array();

	public $isReturnTransfer = true;
	public $timeout = 30;

	public $response = null;

	/**
	 * constructor
	 */
	public function __construct($url, $config = array()) {
		$this->url = $url;
		//config
		foreach ($config as $k => $v) {
			$kk = strtolower($k);
			if ($kk == "proxy") {
				$this->proxyUrl = $v;

			}else if ($kk == "headers") {
				$this->setHeaders($v);

			}else if ($kk == "curloptions") {
				$this->curlOptions = $v;

			}else if ($kk == "returntransfer") {
				$this->isReturnTransfer = $v;

			}else if ($kk == "timeout") {
				$this->timeout = $v;
			}

		}
	}

	/**
	 * set header
	 */
	public function setHeaders($headers = array()) {
		if (!is_array($headers)) {
			return false;
		}
		foreach ($headers as $k => $v) {
			$this->headers[$k] = $v;

			$kk = strtolower($k);
			if ($kk == "content-type") {
				if ($v == "application/json") {
					$this->isContentTypeJson = true;
				}
			}
		}
	}

	/**
	 * basic credentials
	 */
	public function setCredentials($user = null, $passwd = null) {
		$this->headers["Authorization"] = "Basic ". base64_encode("{$user}:{$pass}");
	}


	/**
	 * send request
	 */
	public function send($method = "GET", $page = null, $body = null, $curloptions = array()) {
		$method = strtoupper($method);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);

		curl_setopt($ch, CURLOPT_USERAGENT, "PHP/" . phpversion());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, $this->isReturnTransfer);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_HEADER, true);

		//request string
		$querystring = null;
		if ($method == "POST") {
			if ($body != null) {
				if ($this->isContentTypeJson) {
					$body = json_encode($body);	//toString
				}
				$this->headers["Content-length"] = strlen($body);
			}
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

		}else if ($method == "PUT") {
			curl_setopt($ch, CURLOPT_PUT, true);
			curl_setopt($ch, CURLOPT_INFILE, $body);
			curl_setopt($ch, CURLOPT_INFILESIZE, strlen($body));

		}else{
			if (is_array($body)) {
				$comm = "?";
				foreach ($body as $k => $v) {
					$querystring .= "{$comm}{$k}={$v}";
					$comm = "&";
				}
				$querystring = urlencode($querystring);
			}else{
				if ($body != null) {
					$querystring = "?".urlencode($body);
				}
			}

			if ($page == null) {
				$url = parse_url($this->url);
				$page = isset($url["path"]) ? $url["path"] : "/";
			}
		}

		//request
		$header0 = "{$method} {$page}{$querystring} HTTP/1.1";
		//headers
		$headers = array( $header0 );
		foreach ($this->headers as $k => $v) {
			$headers[] = "{$k}: {$v}";
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		//proxy
		$this->setProxy($ch, $this->proxyUrl);

		//etc options
		curl_setopt_array($ch, $this->curlOptions);
		curl_setopt_array($ch, $curloptions);

		//execute
		$res = curl_exec($ch);

		//result
		if (curl_errno($ch)) {
			$res = "ERR 999 HTTPRequest.php".CRLF.CRLF;
			$res .= curl_error($ch) .CRLF;
		}else{
//			var_dump($data);
			curl_close($ch);
		}
		$this->response = new HTTPResponse($res);
		return $this->response;
	}



	/////

	/** proxy */
	private function setProxy($ch, $proxyurl) {
		if ($proxyurl == null) {
			return false;
		}
		//
		$url = parse_url($proxyurl);
		extract($url);
		curl_setopt($ch, CURLOPT_PROXY, $host);
		if (isset($port)) {
			curl_setopt($ch, CURLOPT_PROXYPORT, $port);
		}else{
			curl_setopt($ch, CURLOPT_PROXYPORT, 8080);
		}
		if ((isset($user)) && (isset($pass))) {
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$user}:{$pass}");
		}
		return $ch;
	}



	//////////sample code
/*
	private function sample() {
		$credentials = "username:password";
		// Read the XML to send to the Web Service
        $request_file = "./SampleRequest.xml";
        $fh = fopen($request_file, 'r');
        $xml_data = fread($fh, filesize($request_file));
        fclose($fh);

        $url = "http://www.example.com/services/calculation";
        $page = "/services/calculation";
        $headers = array(
            "POST ".$page." HTTP/1.0",
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: \"run\"",
            "Content-length: ".strlen($xml_data),
            "Authorization: Basic " . base64_encode($credentials)
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, $defined_vars['HTTP_USER_AGENT']);

        // Apply the XML to our curl call
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);

        $data = curl_exec($ch);

        if (curl_errno($ch)) {
            print "Error: " . curl_error($ch);
        } else {
            // Show me the result
            var_dump($data);
            curl_close($ch);
        }
	}
 */

}

?>