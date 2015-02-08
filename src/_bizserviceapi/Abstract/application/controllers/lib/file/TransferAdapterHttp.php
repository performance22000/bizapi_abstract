<?php
/**
 * upd 141106 a.ide Zend_File_Transfer_Adapter_Httpの差分のみ実装
 * add 121106 a.ide Zend_File_Transfer_Adapter_Httpの複製。日本語ファイル名対応
 * use at AbstractBizBaseUploadFile.php
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
 * @category  Zend
 * @package   Zend_File_Transfer
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 * @version   $Id: Http.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_File_Transfer_Adapter_Http
 */
require_once 'Zend/File/Transfer/Adapter/Http.php';

/**
 * File transfer adapter class for the HTTP protocol
 *
 * @category  Zend
 * @package   Zend_File_Transfer
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */
class FileTransferAdapterHttp extends Zend_File_Transfer_Adapter_Http
{
    /**
     * Constructor for Http File Transfers
     *
     * @param array $options OPTIONAL Options to set
     */
    public function __construct($options = array())
    {
    	parent::__construct($options);
    }

    /**
	 * OVERRIDE: 121106 a.ide 日本語ファイル名対応
	 *
	 *
     * Receive the file from the client (Upload)
     *
     * @param  string|array $files (Optional) Files to receive
     * @return bool
     */
    public function receive($files = null)
    {
        if (!$this->isValid($files)) {
            return false;
        }

        $check = $this->_getFiles($files);
        foreach ($check as $file => $content) {
            if (!$content['received']) {
                $directory   = '';
                $destination = $this->getDestination($file);
                if ($destination !== null) {
                    $directory = $destination . DIRECTORY_SEPARATOR;
                }

                $filename = $directory . $content['name'];
                $rename   = $this->getFilter('Rename');
                if ($rename !== null) {
                    $tmp = $rename->getNewName($content['tmp_name']);
                    if ($tmp != $content['tmp_name']) {
                        $filename = $tmp;
                    }

                    if (dirname($filename) == '.') {
                        $filename = $directory . $filename;
                    }

                    $key = array_search(get_class($rename), $this->_files[$file]['filters']);
                    unset($this->_files[$file]['filters'][$key]);
                }

				/** add 2012/11/6 a.ide 日本語ファイル名対応 */
				$filename = mb_convert_encoding($filename, "SJIS", "AUTO");
				/** end */
                // Should never return false when it's tested by the upload validator
                if (!move_uploaded_file($content['tmp_name'], $filename)) {
                    if ($content['options']['ignoreNoFile']) {
                        $this->_files[$file]['received'] = true;
                        $this->_files[$file]['filtered'] = true;
                        continue;
                    }

                    $this->_files[$file]['received'] = false;
                    return false;
                }

                if ($rename !== null) {
                    $this->_files[$file]['destination'] = dirname($filename);
                    $this->_files[$file]['name']        = basename($filename);
                }

                $this->_files[$file]['tmp_name'] = $filename;
                $this->_files[$file]['received'] = true;
            }

            if (!$content['filtered']) {
                if (!$this->_filter($file)) {
                    $this->_files[$file]['filtered'] = false;
                    return false;
                }

                $this->_files[$file]['filtered'] = true;
            }
        }

        return true;
    }

}
