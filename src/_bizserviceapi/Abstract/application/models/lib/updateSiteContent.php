<?php
/**
 * modx library
 * table: PREFIX_site_content
 */
class UpdateSiteContent {
	private $tablename = "site_content";
	private $db = null;
	private $now = 0;

	public $cols = array(
		"id" => 0,
		"type" => "document",
		"contenttype" => "text/html",
		"pagetitle" => "",
		"longtitle" => "",
		"description" => "",
		"alias" => null,
		"link_attributes" => "",
		"published" => 0,
		"pub_date" => 0,
		"unpub_date" => 0,
		"parent" => 0,
		"isfolder" => 0,
		"introtext" => null,
		"content" => null,
		"richtext" => 1,
		"template" => 0,
		"menuindex" => 0,
		"searchable" => 0,
		"caceable" => 0,
		"createdby" => 0,
		"createdon" => 0,
		"editedby" => 0,
		"editedon" => 0,
		"deleted" => 0,
		"deletedon" => 0,
		"deletedby" => 0,
		"publishedon" => 0,
		"publishedby" => 0,
		"menutitle" => "",
		"donthit" => 0,
		"haskeywords" => 0,
		"hasmetatags" => 0,
		"privateweb" => 0,
		"privatemgr" => 0,
		"content_dispo" => 0,
		"hidemenu" => 0,
	);

	public function __construct($db, $modx = null, $now = 0) {
		$this->db = $db;
		$this->tablename = ($modx != null) ? $modx->getFullTableName($this->tablename) : $this->tablename;
		$this->now = ($now == 0) ? time() : $now;
	}
	
	public function close() {
		try{
			if ($this->db != null) {
				$this->db->closeConnection();
				$this->db = null;
			}
		}catch(exception $ex){
			throw $ex;
		}
	}

	public function select($where = "", $cols = "*") {
		$tbl = $this->tablename;
		$sql = <<<SQL
SELECT {$cols} FROM {$tbl} WHERE {$where}
SQL;
		try{
			$stmt = $this->db->prepare($sql);
			$stmt->execute();
			if ($stmt->rowCount() == 0) {
				return null;
			}
			$rec = null;
			while($row = $stmt->fetch()) {
				$rec = $row;
				break;
			}
			$stmt->closeCursor();
			return $rec;
			
		}catch(exception $ex){
			throw $ex;
		}
	}

	public function update($newrequest = array(), $where = "", $isinsert = false) {
		try{
			//検索(見つからない場合は追加orFALSE)
			$row = $this->select($where, "id");
			if ($row == null) {
				if ($isinsert) {
					return $this->insert($newrequest);
				}else{
					return false;
				}
			}
			//更新日時
			$newrequest["editedon"] = $this->now;
			//更新SQL作成
			$values = "";
			$com = "";
			foreach($newrequest as $colname => $value) {
				if ($colname == "createdon") {
					continue;
				}
				if ($colname == "content") {
					$values .= "{$com}{$colname}=:{$colname}";
					$content = $value;
				}else{
					$values .= "{$com}{$colname}='{$value}'";
				}
				$com = ",";
			}
			$tbl = $this->tablename;
			$sql = <<<SQL
UPDATE {$tbl} SET {$values} WHERE {$where}
SQL;
			//更新
			$stmt = $this->db->prepare($sql);
			if (isset($content)) {
				$stmt->bindParam(":content", $content);
			}
			$stmt->execute();
			$stmt->closeCursor();
			return $row['id'];
			
		}catch(exception $ex){
			throw $ex;
		}
	}
	
	public function insert($newrequest = array()) {
		try{
			//追加日時
			$newrequest["createdon"] = $this->now;
			if ($newrequest["published"] != 0) {
				$newrequest["publishedon"] = $this->now;
			}
			//追加SQL作成
			$names = "";
			$values = "";
			$where = "";
			$com = "";
			$and = "";
			foreach($newrequest as $colname => $value) {
				$names .= "{$com}{$colname}";
				if ($colname == "content") {
					$values .= "{$com}:{$colname}";
					$content = $value;
				}else{
					$values .= "{$com}'{$value}'";
					$where .= "{$and}{$colname}='{$value}'";
					$and = " and ";
				}
				$com = ",";
			}
			$tbl = $this->tablename;
			$sql = <<<SQL
INSERT INTO {$tbl} ({$names}) VALUES ({$values})
SQL;
			$stmt = $this->db->prepare($sql);
			if (isset($content)) {
				$stmt->bindParam(":content", $content);
			}
			$stmt->execute();
			$stmt->closeCursor();
			//追加データのidを取得
//			if ($where == "") {
//				$where = "pagetitle='{$newrequest['pagetitle']}' and parent='{$newrequest['parent']}'";
//			}
			$row = $this->select($where, "id");
			return $row['id'];

		}catch(exception $ex){
			throw $ex;
		}
	}
	
}
?>
