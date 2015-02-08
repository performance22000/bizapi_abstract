<?php
/**
 * 共通関数クラス
 * staticメソッドで用意しています。
 * このファイルは、AbstractBizBaseファイルで、requireされています。
 */
class Functions {

	/**
	 * ファイル名の日本語対応
	 * @param object $filename
	 */
	public static function convToFilename($filename) {
		return mb_convert_encoding($filename, "SJIS", "AUTO");
	}

	/**
	 * targetfilenameをzipファイルにする
	 * @param object $targetfilename
	 * @param object $archivename [optional]
	 * @param object $ext [optional]
	 * @param object $isdelete [optional]
	 * @return 
	 */
	public static function createZip($targetfilename, $archivename = null, $ext = ".zip", $isdelete = false) {
		try{
			//targetfileをzipファイルにする
			if ($archivename == null) {
				$archivename = $targetfilename;
			}
			if ($ext == null) {
				$ext = ".zip";
			}
			//zipファイルにする
			require_once("Zend/Filter/Compress/Zip.php");
			$comp = new Zend_Filter_Compress_Zip();
			$comp->setArchive("{$archivename}{$ext}");
			$comp->compress($targetfilename);
			$archivefilename = "{$archivename}{$ext}";
			//圧縮前のファイルを削除
			if ($isdelete) {
//				Functions::deleteFiles($targetfilename, true, true);
				Functions::deleteFiles($targetfilename);	//ファイルは削除
			}
			return $archivefilename;

		}catch(exception $ex) {
			throw $ex;
		}
	}

	/**
	 * メール送信
	 * 引数(htmlmails)を以下の配列にする。
	 * "toaddr" => 宛先アドレス
	 * "toname" => 宛名(実際は未使用)
	 * "subject" => タイトル
	 * "attach" => 添付ファイル名(フルパス)
	 * "body" => 本文
	 * @param object $htmlmails
	 * @param object $isresult [optional] ...処理結果(true:簡易、false:明細)
	 * @return 
	 */
	public static function sendMails($htmlmails, $isresult = true) {
		$mails = array();	//テスト用
		require_once APPLICATION_PATH . '/models/SendMail.php';
		foreach((array)$htmlmails as $cd => $mailinfo){
			extract($mailinfo);
			if (Functions::isNull($toaddr)) {
				$err = "ERR";
				$msg = ": アドレス不明。メール送信されていません。";
				$attachmsg = (isset($attach)) ? "attach:{$attach}\n" : "";
			}else{
				$err = "OK";
				$msg = "";
				$mail = new SendMail();
				$mail->addToAddress($toaddr);	//仕入先アドレスor担当者アドレス
				$mail->setSubject($subject);
				$mail->setBody($body);
				if (isset($attach)) {
					$mail->setAttachment($attach);
					$attachmsg = "attach:{$attach}\n";
				}else{
					$attachmsg = "";
				}
				try{
					$mail->send();
				}catch(exceptionr $ex){
					$err = "ERR";
					$msg = $ex->getMessage();
				}
			}
			if (Functions::isTrue($isresult)) {
				$mails[$cd] = "{$err}:($cd){$toname} {$toaddr} {$subject}{$msg}";
			}else{
				$mails[$cd] = "{$err}:{$msg}\n[($cd){$toname}] to:{$toaddr}\nsubject:{$subject}\n{$attachmsg}\n{$body}\n";
			}
		}
		return $mails;
	}
	
	
	/**
	 * 数値を3桁ごとにカンマ表示する
	 * @return
	 */
	public static function getNumberFormat($value) {
		if (!is_numeric($value)) {
			return $value;
		}
		return number_format($value);
	}

	/**
	 * 指定されたファイル名/ディレクトリを削除する。
	 * 引数がディレクトリ名の場合、直下のファイルは削除されます。
	 * @param object $dirfilename
	 * @param object $isdeletedir [optional] 引数指定のディレクトリを削除
	 * @param object $isrecursive [optional] 配下のサブディレクトリを再帰的に削除
	 * @param object $exts [optional] 削除対象のファイル拡張子
	 * @param object $filestrings [optional] ファイルに含まれる文字列(削除対象にする) ※テスト中
	 * @return
	 */
	public static function deleteFiles($dirfilename, $isdeletedir = false, $isrecursive = false, $exts = array(), $filestrings = array()) {
		$dirfilename = Functions::convToFilename($dirfilename);	//日本語対応 a.ide
		if (!file_exists($dirfilename)) {
			return;
		}
		if (is_dir($dirfilename)) {
			$filelist = scandir($dirfilename);
			foreach((array)$filelist as $k => $file) {
				if (($file == ".")||($file == "..")) {
					continue;
				}
				if (($isrecursive)&&(is_dir("{$dirfilename}/$file"))) {
					//再帰的にサブディレクトリも削除
					Functions::deleteFiles("{$dirfilename}/$file", $isdeletedir, $isrecursive, $exts, $filestrings);
				}else{
					Functions::isCheckFileAndDelete("{$dirfilename}/$file", $exts);
				}
			}
			if ($isdeletedir) {
				rmdir($dirfilename);
			}
		}else{
			Functions::isCheckFileAndDelete($dirfilename, $exts);
		}
		return;
	}
	private static function isCheckFileAndDelete($filename, $exts) {
		$isfind = true;
		foreach ($exts as $k => $v) {
			$p = strrpos(strtolower($filename), $v);
			if ($p !== false) {
				$isfind = true;
				break;
			}
			$isfind = false;
		}
		if ($isfind) {
			unlink("{$filename}");
//			print "{$filename}<br>\n";	//※デバッグ用 121212 a.ide
		}
		return $isfind;
	}
	
	/**
	 * "true" or "false" 判断
	 * @param object $value
	 * @param object $default [optional]
	 * @return true...true, "true", "aaa"
	 * false...false, "false", "", null
	 */
	public static function isTrue($value, $default = false) {
		if ($value) {
			if (strcasecmp($value, "true") == 0) {
				return true;
			}
			if (Functions::isNull($value)) {
				return false;
			}
			if ($value == 0) {
				return false;
			}
			return true;
		}else{
			//falseまたは定義されていない場合
			return $default;
		}
	}
	/**
	 * 改行コードを取り除く
	 * @param object $value
	 * @return
	 */
	public static function trimCRLF($value) {
		return str_replace(array("\r\n", "\n", "\r"), "", $value);
	}
	/**
	 * 日時表示 yyyy/mm/dd hh:mm
	 * @param object $value
	 * @param object $isdateonly [optional]
	 * @param object $datedelimit [optional]
	 * @param object $iszero [optional] true:"1", false(default):"01"
	 * @return
	 */
	public static function getDateTimeFormat($value, $isdateonly = false, $datedelimiter = "/", $iszero = false) {
		if ($isdateonly) {
			$wks = explode(" ", $value);	//日付と時間を分割
			if (($iszero)||($datedelimiter == ".")) {	//強制またはピリオド区切りの時は"0x"を"x"にする。
				$dlms = array("-0", "/", "-", ".");
			}else{
				$dlms = array("/", "-", ".");
			}
			$dt = str_replace($dlms, $datedelimiter, $wks[0]);
			return $dt;
		}
		$wks = explode(":", $value);
		if (count($wks) >= 3) {
			return $wks[0] .":". $wks[1];
		}else{
			return $value;
		}
	}
	/**
	 * 日付妥当性チェックと年、月、日の取得
	 * @param object $value "yyyy/mm/dd" or "yy/mm/dd" or ...
	 * @return 正常：array(yyyy, mm, dd)、エラー:array(null, null, null)
	 */
	public static function getDateArray($value) {
		$delimiter = array("/", ".", "-", ":", " ");
		$value = str_replace($delimiter, "/", $value);
		$wks = explode("/", $value);
		if (count($wks) != 3){
			return array(null, null, null);
		}
		$yy = (is_numeric($wks[0])) ? $wks[0] : 0;
		$mm = (is_numeric($wks[1])) ? $wks[1] : 0;
		$dd = (is_numeric($wks[2])) ? $wks[2] : 0;
		if (!checkdate($mm, $dd, $yy)){
			return array(null, null, null);
		}
		return $wks;
	}

	/**
	 * カナ検索用強調表示
	 * @param object $value
	 * @param object $iskeywords
	 * @param object $keywds
	 * @return
	 */
	public function replaceKeywordStrong($value, $keywds = null, $iskeywords = false) {
		$val = Functions::getNBSP($value);
		if (!$iskeywords){
			return $val;
		}
		if ($keywds == null){
			return $val;
		}
		//強調表示置換
		foreach((array)$keywds as $k => $v){
			$p = stripos($val, $v);
			if ($p !== false) {
				$val = str_replace($v, "<span class='kensaku'>{$v}</span>", $val);
			}
		}
		return $val;
	}


	/**
	 * 空の場合に、&nbsp;を設定
	 * @param object $value
	 * @return
	 */
	public static function getNBSP($value) {
		$val = trim($value);
		return Functions::isNull($val) ? "&nbsp;" : $value;
	}

	/**
	 * null or ゼロ文字チェック
	 * @param object $value
	 * @return
	 */
	public static function isNull($value) {
		if ((is_null($value))||($value == "")) {
			return true;
		}
		return false;
	}

	/**
	 * 文字数チェック
	 * @param object $value
	 * @param object $size [optional]
	 * @param object $iscont [optional] 全角を半角変換してからチェック
	 * @return
	 */
	public static function isSize($value, $size = 0, $iscont = true) {
		if ($size == 0) {
			return true;
		}
		if ($iscont) {
			$value = Functions::convTo($value);
		}
		if ($size >= strlen($value)) {
			return true;
		}
		return false;
	}

	/**
	 * 郵便番号チェック 9999999 or 999-9999
	 * @param object $postno
	 * @param object $isNullNG [optional]
	 * @return
	 */
	public static function isPostNo($value, $isNullNG = false) {
		if ($isNullNG) {
			if (Functions::isNull($value)) {
				return false;
			}
		}else{
			if (Functions::isNull($value)) {
				return true;
			}
		}
		//	print ("Error: 郵便番号を半角数字(ハイフンなし)で入力して下さい\n");
		if(preg_match("/^\d{7}$/", $value)){
			return true;
		}
		if(preg_match("/^\d{3}-?\d{4}$/", $value)){
			return true;
		}
		return false;
	}

	/**
	 * 電話FAX携帯番号チェック ハイフンなし/あり
	 * @param object $telfax
	 * @param object $isNullNG [optional]
	 * @return
	 */
	public static function isTelFax($value, $isNullNG = false) {
		if ($isNullNG) {
			if (Functions::isNull($value)) {
				return false;
			}
		}else{
			if (Functions::isNull($value)) {
				return true;
			}
		}
		if (preg_match("/^0\d{9,10}$/", $value)){
			return true;
		}
		if (preg_match("/^0\d{1,5}-\d{0,4}-?\d{4}$/", $value)) {
			return true;
		}
		if (preg_match("/^.{11,13}$/", $value)) {
			return true;
		}
		return false;
	}

	/**
	 * メールアドレスチェック
	 * @param object $mail [optional]
	 * @param object $isNullNG [optional]
	 * @return
	 */
	public static function isMail($value, $isNullNG = false) {
		if ($isNullNG) {
			if (Functions::isNull($value)) {
				return false;
			}
		}else{
			if (Functions::isNull($value)) {
				return true;
			}
		}
		if (preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $value)) {
			return true;
		}else{
			return false;
		}
	}

	/*
	 * 日付チェック
	 * 12.07.13 Add
	 *
     */
	public static function isDate($value, $isNullNG = false) {
		if ($isNullNG) {
			if (Functions::isNull($value)) {
				return false;
			}
		}else{
			if (Functions::isNull($value)) {
				return true;
			}
		}

		if (!(preg_match('|^\d{4}\/\d{1,2}\/\d{1,2}$|', $value)))
		{
	        return false;
	    }

		list($year, $month, $day, ) = split('[/.-]', $value);
		if(checkdate($month, $day, $year)) {
	        return true;
		} else {
	        return false;
		}

	}


	/**
	 * 全角 -> 半角
	 * @param object $text
	 * @param object $mode [optional] "as"..全角英数字とスペースを半角へ変換
	 * @param object $enc [optional]
	 * @return
	 */
	public static function convTo($text, $mode = "as", $enc = "UTF-8") {
		return mb_convert_kana($text, $mode, $enc);
	}
	/**
	 * ひらがな/半角カタカナ -> 全角カタカナ ＆ 全角英数字とスペースを半角変換
	 * 半角カタカナ -> 全角カタカナ ＆ 全角英数字とスペースを半角変換
	 * @param object $text
	 * @param object $mode [optional]
	 * @param object $enc [optional]
	 * @return
	 * 
	 * mod 121118 a.ide 「ひらがな」は変換しない
	 * 
	 */
//	public static function convToKata($text, $mode = "CKas", $enc = "UTF-8") {
	public static function convToKata($text, $mode = "Kas", $enc = "UTF-8") {
		return mb_convert_kana($text, $mode, $enc);
	}

/*
 * 変換モード($mode)の値。複数繋げることができる。
a 全角英数字を半角英数字に変換する
A 半角英数字を全角英数字に変換する
c 全角カタカナを全角ひらがなに変換する
C 全角ひらがなを全角カタカナに変換する
k 全角カタカナを半角カタカナに変換する
K 半角カタカナを全角カタカナに変換する
h 全角ひらがなを半角カタカナに変換する
H 半角カタカナを全角ひらがなに変換する
n 全角数字を半角数字に変換する
N 半角数字を全角数字に変換する
r 全角英文字を半角英文字に変換する
R 半角英文字を全角英文字に変換する
n 全角数字を半角数字に変換する
N 半角数字を全角数字に変換する
s 全角スペースを半角スペースに変換する (U+3000 → U+0020)
S 半角スペースを全角スペースに変換する (U+0020 → U+3000)
V 濁点つきの文字を１文字に変換する (K、H と共に利用する）
 */

	/**
 	 * ひらがな/カタカナからカナ区分を取得 ※仕掛中
	 * @param object $value
	 * @return
	 */
	public static function getKanakbn_Un($value, $enc = "UTF-8") {
		mb_internal_encoding($enc);
		$str = mb_substr(mb_convert_kana(mb_substr($value, 0, 1), "CK"), 0, 1);
		try{
			$sql = "select * from KBNMS where SYSID=:sysid and KBNSBT='KANAKBN' and STR2 like :kana";
//			$sql += "%{$kana}%";
			$db = Zend_Registry::getInstance()->dbAdapter;
			$stmt = $db->prepare($sql);
			$stmt->bindParam("sysid", $sysid);
			$kana = "%{$str}%";
			$stmt->bindParam("kana", $kana);
			$stmt->execute();
			$kanakbn = 11;
			while($row = $stmt->fetch()) {
				extract($row);
				$kanakbn = $KBNCD;
				break;
			}
			$stmt->closeCursor();
			$db->closeConnection();
			return $kanakbn;
		}catch (Exception $ex){
			//エラー処理
			$this->setException("Functions::getKanakbn: ", $ex, $stmt, $db);
			$stmt->closeCursor();
			$db->closeConnection();
			return null;
		}
	}

 	/**
 	 * ひらがな/カタカナからカナ区分を取得
 	 * @param object $value
 	 * @return
 	 */
	public static function getKanakbn($value, $enc = "UTF-8") {
		mb_internal_encoding($enc);
		$str = mb_substr(mb_convert_kana(mb_substr($value, 0, 1), "CK"), 0, 1);
		$replace_of = array(
			'ア','イ','ウ','エ','オ','ヴ',
			'カ','キ','ク','ケ','コ',
			'ガ','ギ','グ','ゲ','ゴ',
			'サ','シ','ス','セ','ソ',
			'ザ','ジ','ズ','ゼ','ゾ',
			'タ','チ','ツ','テ','ト',
			'ダ','ヂ','ヅ','デ','ド',
			'ナ','ニ','ヌ','ネ','ノ',
			'ハ','ヒ','フ','ヘ','ホ',
			'バ','ビ','ブ','ベ','ボ',
			'パ','ピ','プ','ペ','ポ',
			'マ','ミ','ム','メ','モ',
			'ヤ','ユ','ヨ',
			'ラ','リ','ル','レ','ロ',
			'ワ','ヲ','ン');
		$replace_by = array(
			1, 1, 1, 1, 1,1,
			2, 2, 2, 2, 2,
			2, 2, 2, 2, 2,
			3, 3, 3, 3, 3,
			3, 3, 3, 3, 3,
			4, 4, 4, 4, 4,
			4, 4, 4, 4, 4,
			5, 5, 5, 5, 5,
			6, 6, 6, 6, 6,
			6, 6, 6, 6, 6,
			6, 6, 6, 6, 6,
			7, 7, 7, 7, 7,
			8, 8, 8,
			9, 9, 9, 9, 9,
			10, 10, 10,
		);
		$gyo = in_array($str, $replace_of) ? str_replace($replace_of, $replace_by, $str) : 11;
		return $gyo;
	}

}
?>
