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
	 * db->escapeの逆変換 ※現状は改行コードのみ
	 * @param object $string ..$_POST項目は全てmodx->db->escapeしてるので、それを戻す。
	 */
	public static function dbUnescape($string) {
		$ESCs = array( "\\r" => "\r", "\\n" => "\n" );
		foreach ($ESCs as $from => $to) {
			$string = str_replace("$from", $to, $string);
		}
		return $string;
	}
	
	
	//※保留
	private function html2txt($document){ 
		$search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript 
			'@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags 
			'@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly 
			'@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA 
		); 
		$text = preg_replace($search, '', $document); 
 		return $text; 
	} 
}
?>
