<?php
/**
 * mod141104 a.ide 以下の対応によりメソッドをオーバーライドで修正
 * ・proxyの場合のcurlオプション設定のバグ修正
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
 * @subpackage Client_Adapter
 * @version    $Id$
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @see Zend_Http_Client_Adapter_Curl
 */
require_once 'Zend/Http/Client/Adapter/Curl.php';

/**
 * An adapter class for Zend_Http_Client based on the curl extension.
 * Curl requires libcurl. See for full requirements the PHP manual: http://php.net/curl
 *
 * @category   Zend
 * @package    Zend_Http
 * @subpackage Client_Adapter
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class HttpClientAdapterCurl extends Zend_Http_Client_Adapter_Curl
{
    /**
	 * OVERRIDE: mod141104 a.ide
	 *
	 *
     * Set the configuration array for the adapter
     *
     * @throws Zend_Http_Client_Adapter_Exception
     * @param  Zend_Config | array $config
     * @return Zend_Http_Client_Adapter_Curl
     */
    public function setConfig($config = array())
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();

        } elseif (! is_array($config)) {
            require_once 'Zend/Http/Client/Adapter/Exception.php';
            throw new Zend_Http_Client_Adapter_Exception(
                'Array or Zend_Config object expected, got ' . gettype($config)
            );
        }

        if(isset($config['proxy_user']) && isset($config['proxy_pass'])) {
            $this->setCurlOption(CURLOPT_PROXYUSERPWD, $config['proxy_user'].":".$config['proxy_pass']);
            unset($config['proxy_user'], $config['proxy_pass']);
        }

        foreach ($config as $k => $v) {
            $option = strtolower($k);
            switch($option) {
                case 'proxy_host':
                    $this->setCurlOption(CURLOPT_PROXY, $v);
                    break;
                case 'proxy_port':
                    $this->setCurlOption(CURLOPT_PROXYPORT, $v);
                    break;

				//add141104 a.ide curloptionsの設定を追加(バグでは)
				case 'curloptions':
					foreach ($v as $opt => $val) {
						$this->setCurlOption($opt, $val);
					}
					break;

                default:
                    $this->_config[$option] = $v;
                    break;
            }
        }

        return $this;
    }
}
