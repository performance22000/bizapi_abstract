<?php
/**
 * delete document from ZendFW
 * 14/5/1 a.ide
 */

//if(!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE != 'true') exit();

/////
// add140501 a.ide
//

// include_once the error handler
/*
include_once("{$core_path}error.class.inc.php");
$e = new errorHandler;
 */
/*
if(!$modx->hasPermission('delete_document'))
{
	$e->setError(3);
	$e->dumpError();
}
 */
$modx = $this->modx;


// check the document doesn't have any children
//$id=intval($_GET['id']);

// check permissions on the document
if($id==$modx->config['site_start'])
{
	$warning = "Document is 'Site start' and cannot be deleted!";
}
elseif($id==$modx->config['error_page'])
{
	$warning = "Document is 'Error page' and cannot be deleted!";
}
elseif($id==$modx->config['site_unavailable_page'])
{
	$warning = "Document is used as the 'Site unavailable page' and cannot be deleted!";
}
//elseif(!check_group_perm($id))
//{
//	$warning = $_lang['access_permissions'];
//}
else
{
	$linked = check_linked($id);
}

if(isset($linked) && $linked!==false)  $warning = 'Linked by ' . 'ID:' . join(', ID:', $linked);

if(isset($warning))
{
	include "header.inc.php";
	?><div class="sectionHeader">Warning</div>
	<div class="sectionBody">
	<p><?php $modx->webAlert($warning,'javascript:history.back();'); ?></p>
	<?php
	include("footer.inc.php");
	exit;
}


$children = array();
getChildren($id);

// invoke OnBeforeDocFormDelete event
$params['id']       = $id;
$params['children'] = $children;
//a.ide $modx->invokeEvent("OnBeforeDocFormDelete",$params);

$tbl_site_content = $modx->getFullTableName('site_content');
$field = array();
$field['deleted']   = '1';
//a.ide
//$field['deletedby'] = $modx->getLoginUserID();
$field['deletedby'] = $modx->getLoginUserID("mgr");
if (!$field['deletedby']) {
	$field['deletedby'] = 0;
}
//

$field['deletedon'] = time();
if(0 < count($children))
{
	$docs_to_delete   = implode(' ,', $children);
	$rs = $modx->db->update($field,$tbl_site_content,"id IN({$docs_to_delete})");
	if(!$rs)
	{
		echo "Something went wrong while trying to set the document's children to deleted status...";
		exit;
	}
}

//ok, 'delete' the document.
$rs = $modx->db->update($field,$tbl_site_content,"id='{$id}'");
if(!$rs)
{
	echo "Something went wrong while trying to set the document to deleted status...";
	exit;
}
else
{
	// invoke OnDocFormDelete event
	$params['id']       = $id;
	$params['children'] = $children; //array()
//a.ide 	$modx->invokeEvent("OnDocFormDelete",$params);

	// empty cache
	$modx->clearCache();
/* a.ide 遷移しない
	$pid = $modx->db->getValue($modx->db->select('parent',$tbl_site_content,"id='{$id}'"));
	$page = (isset($_GET['page'])) ? "&page={$_GET['page']}" : '';
	if($pid!=='0') $header="Location: index.php?r=1&a=3&id={$pid}&tab=0{$page}";
	else           $header="Location: index.php?a=2&r=1";
	header($header);
 */
}



function getChildren($parent)
{
	global $modx,$children;

	$tbl_site_content = $modx->getFullTableName('site_content');

	$rs = $modx->db->select('id',$tbl_site_content,"parent='{$parent}' AND deleted='0'");
	if(0 < $modx->db->getRecordCount($rs))
	{
		// the document has children documents, we'll need to delete those too
		while($row=$modx->db->getRow($rs))
		{
			if($row['id']==$modx->config['site_start'])
			{
				echo "The document you are trying to delete is a folder containing document {$row['id']}. This document is registered as the 'Site start' document, and cannot be deleted. Please assign another document as your 'Site start' document and try again.";
				exit;
			}
			if($row['id']==$modx->config['site_unavailable_page'])
			{
				echo "The document you are trying to delete is a folder containing document {$row['id']}. This document is registered as the 'Site unavailable page' document, and cannot be deleted. Please assign another document as your 'Site unavailable page' document and try again.";
				exit;
			}
			$children[] = $row['id'];
			getChildren($row['id']);
		}
	}
}

function check_group_perm($id)
{
	global $modx;
	include_once './processors/user_documents_permissions.class.php';
	$udperms = new udperms();
	$udperms->user = $modx->getLoginUserID();
	$udperms->document = $id;
	$udperms->role = $_SESSION['mgrRole'];
	return $udperms->checkPermissions();
}

function check_linked($id)
{
	global $modx;
	
	$tbl_site_content = $modx->getFullTableName('site_content');
	
	$rs = $modx->db->select('id',$tbl_site_content,"content LIKE '%[~{$id}~]%' AND deleted='0'");
	if(0 < $modx->db->getRecordCount($rs))
	{
		while($row = $modx->db->getRow($rs))
		{
			$result[] = $row['id'];
		}
		return $result;
	}
	return false;
}

