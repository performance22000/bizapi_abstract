<?php
/**
 * modified 141104 a.ide 以下の対応によりメソッドをオーバーライドで修正
 * ・for proxy issued: http://www.symantec.com/docs/TECH210784対応
 * ・Zend_Http_Client_Adapter_Curlアダプターのバグ修正(ロードするクラスファイルごと変更)
 *
 * ・additional and OVERRIDE functions
 *
 */

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Http
 * @subpackage Client
 * @version    $Id$
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @see Zend_Http_Client
 */
require_once 'Zend/Http/Client.php';

/**
 * Zend_Http_Client is an implementation of an HTTP client in PHP. The client
 * supports basic features like sending different HTTP requests and handling
 * redirections, as well as more advanced features like proxy settings, HTTP
 * authentication and cookie persistence (using a Zend_Http_CookieJar object)
 *
 * @todo Implement proxy settings
 * @category   Zend
 * @package    Zend_Http
 * @subpackage Client
 * @throws     Zend_Http_Client_Exception
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class HttpClient extends Zend_Http_Client
{
	//http adapter
	const ADAPTER_CURL = "CURL";	//"Zend_Http_Client_Adapter_Curl";

	//content-type
	const CONTENT_TYPE_XML = "text/xml";
	const CONTENT_TYPE_JSON = "application/json";


	//content-type flg
	public $isContentTypeXml = false;
	public $isContentTypeJson = false;



    /**
     * Constructor method. Will create a new HTTP client. Accepts the target
     * URL and optionally configuration array.
     *
     * @param Zend_Uri_Http|string $uri
     * @param [option]array $config Configuration key-value pairs.
	 * @param [option]string proxyurl "http://userid:password@example.proxy.com/"
	 * @param [option]isnossl bool true...no check cert
     */
    public function __construct($uri = null, $config = null, $proxyurl = null, $isnossl = false)
    {
    	if (empty($config)) {
    		$config = array();
    	}
    	//default adapter
    	if (!isset($config["adapter"])) {
    		$config["adapter"] = self::ADAPTER_CURL;
    	}

    	//set to config
		$this->setProxy($proxyurl);
		if ($isnossl) {
			$this->setNoCheckSSL();
		}

    	parent::__construct($uri, $config);
    }

	//add functions
	/**
	 * set proxy
	 */
	public function setProxy($proxyurl) {
		if (empty($proxyurl)) {
			return;
		}
		$url = parse_url($proxyurl);
		extract($url);
		$this->config['proxy_host'] = $host;
		$this->config['proxy_port'] = (isset($port)) ? $port : 8080;
		if (isset($user) && isset($pass)) {
			$this->config['proxy_user'] = $user;
			$this->config['proxy_pass'] = $pass;
		}
		return $this->config;
	}
	/**
	 * no check SSLcert
	 */
	public function setNoCheckSSL() {
		$curloptions = array(
			CURLOPT_SSL_VERIFYPEER => false,	//nocheck ssl
			CURLOPT_SSL_VERIFYHOST => 0,		//nocheck ssl
		);
		return $this->setCurlOtions($curloptions);
	}

	/**
	 * set Curl options
	 */
	public function setCurlOtions($curloptions = array()) {
		if (isset($this->config["curloptions"])) {
			foreach ($curloptions as $k => $v) {
				$this->config["curloptions"][$k] = $v;
			}
		}else{
			$this->config["curloptions"] = $curloptions;
		}
		return $this->config["curloptions"];
	}

	/**
	 * set Body
	 */
	public function setBody($body, $contenttype = null) {
		$this->setRawData($body, $contenttype);
	}

	/**
	 * content-type
	 */
	public function checkContentType($contenttype = null) {
		$type = (empty($contenttype)) ? $this->getHeader("content-type") : strtolower($contenttype);
		if (empty($type)) {
			return;
		}
		$this->isContentTypeJson = ($type == self::CONTENT_TYPE_JSON);
		$this->isContentTypeXml = ($type == self::CONTENT_TYPE_XML);
	}


	//bug fix

    /**
	 * OVERRIDE: 141104 a.ide
	 *
	 *
     * Load the connection adapter
     *
     * While this method is not called more than one for a client, it is
     * seperated from ->request() to preserve logic and readability
     *
     * @param Zend_Http_Client_Adapter_Interface|string $adapter
     * @return null
     * @throws Zend_Http_Client_Exception
     */
    public function setAdapter($adapter)
    {
    	if (is_string($adapter)) {
			try {
				//mod141104 a.ide Zend_Http_Client_Adapter_Curlアダプターのバグ修正対応
				//Zend_Loader::loadClass($adapter);
				if ($adapter === self::ADAPTER_CURL) {
					require_once ABSTRACTZEND_PATH."/controllers/lib/http/Adapter/Curl.php";
					$adapter = "HttpClientAdapterCurl";

				}else{
					Zend_Loader::loadClass($adapter);
				}


            } catch (Zend_Exception $e) {
                /** @see Zend_Http_Client_Exception */
                require_once 'Zend/Http/Client/Exception.php';
                throw new Zend_Http_Client_Exception("Unable to load adapter '$adapter': {$e->getMessage()}", 0, $e);
            }

            $adapter = new $adapter;
        }

        if (! $adapter instanceof Zend_Http_Client_Adapter_Interface) {
            /** @see Zend_Http_Client_Exception */
            require_once 'Zend/Http/Client/Exception.php';
            throw new Zend_Http_Client_Exception('Passed adapter is not a HTTP connection adapter');
        }

        $this->adapter = $adapter;
        $config = $this->config;
        unset($config['adapter']);
        $this->adapter->setConfig($config);
    }

    /**
	 * OVERRIDE: 141104 a.ide
	 *
	 *
	 * Prepare the request body (for POST and PUT requests)
     *
     * @return string
     * @throws Zend_Http_Client_Exception
     */
    protected function _prepareBody()
    {
        // According to RFC2616, a TRACE request should not have a body.
        if ($this->method == self::TRACE) {
            return '';
        }

        if (isset($this->raw_post_data) && is_resource($this->raw_post_data)) {
            return $this->raw_post_data;
        }
        // If mbstring overloads substr and strlen functions, we have to
        // override it's internal encoding
        if (function_exists('mb_internal_encoding') &&
           ((int) ini_get('mbstring.func_overload')) & 2) {

            $mbIntEnc = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        }

        // If we have raw_post_data set, just use it as the body.
        if (isset($this->raw_post_data)) {

			//mod141104 a.ide for proxy issued: http://www.symantec.com/docs/TECH210784対応
			//$this->setHeaders(self::CONTENT_LENGTH, strlen($this->raw_post_data));
			if (!array_key_exists("proxy_host", $this->config)) {
	            $this->setHeaders(self::CONTENT_LENGTH, strlen($this->raw_post_data));
			}

            if (isset($mbIntEnc)) {
                mb_internal_encoding($mbIntEnc);
            }

            return $this->raw_post_data;
        }

        $body = '';

        // If we have files to upload, force enctype to multipart/form-data
        if (count ($this->files) > 0) {
            $this->setEncType(self::ENC_FORMDATA);
        }

        // If we have POST parameters or files, encode and add them to the body
        if (count($this->paramsPost) > 0 || count($this->files) > 0) {
            switch($this->enctype) {
                case self::ENC_FORMDATA:
                    // Encode body as multipart/form-data
                    $boundary = '---ZENDHTTPCLIENT-' . md5(microtime());
                    $this->setHeaders(self::CONTENT_TYPE, self::ENC_FORMDATA . "; boundary={$boundary}");

                    // Encode all files and POST vars in the order they were given
                    foreach ($this->body_field_order as $fieldName=>$fieldType) {
                        switch ($fieldType) {
                            case self::VTYPE_FILE:
                                foreach ($this->files as $file) {
                                    if ($file['formname']===$fieldName) {
                                        $fhead = array(self::CONTENT_TYPE => $file['ctype']);
                                        $body .= self::encodeFormData($boundary, $file['formname'], $file['data'], $file['filename'], $fhead);
                                    }
                                }
                                break;
                            case self::VTYPE_SCALAR:
                                if (isset($this->paramsPost[$fieldName])) {
                                    if (is_array($this->paramsPost[$fieldName])) {
                                        $flattened = self::_flattenParametersArray($this->paramsPost[$fieldName], $fieldName);
                                        foreach ($flattened as $pp) {
                                            $body .= self::encodeFormData($boundary, $pp[0], $pp[1]);
                                        }
                                    } else {
                                        $body .= self::encodeFormData($boundary, $fieldName, $this->paramsPost[$fieldName]);
                                    }
                                }
                                break;
                        }
                    }

                    $body .= "--{$boundary}--\r\n";
                    break;

                case self::ENC_URLENCODED:
                    // Encode body as application/x-www-form-urlencoded
                    $this->setHeaders(self::CONTENT_TYPE, self::ENC_URLENCODED);
                    $body = http_build_query($this->paramsPost, '', '&');
                    break;

                default:
                    if (isset($mbIntEnc)) {
                        mb_internal_encoding($mbIntEnc);
                    }

                    /** @see Zend_Http_Client_Exception */
                    require_once 'Zend/Http/Client/Exception.php';
                    throw new Zend_Http_Client_Exception("Cannot handle content type '{$this->enctype}' automatically." .
                        " Please use Zend_Http_Client::setRawData to send this kind of content.");
                    break;
            }
        }

        // Set the Content-Length if we have a body or if request is POST/PUT
        if ($body || $this->method == self::POST || $this->method == self::PUT) {
            $this->setHeaders(self::CONTENT_LENGTH, strlen($body));
        }

        if (isset($mbIntEnc)) {
            mb_internal_encoding($mbIntEnc);
        }

        return $body;
    }

}
