<?php

include('../../../include/config.php');	# конфигурация БД и т.д.
include('../../../include/util.php');		# утилиты 
include('../../../include/forall.php');

$imageCFG = array(
	'template'				=> '../../../data/admin.html',
	'window_template'		=> '../../../data/window.html',
	//'admin'					=> 'firmsnews_admin',
	'thumbnail_width'		=> 120,
	'filesroot'				=> 'files/',
	'real_filesroot'		=> '../../../files/',
	'log_file_path'			=>	'../../../data/log/log.log',
);

//$admin = $imageCFG[admin];
$USER[is_admin] = $USER ? 1:0;

class children
{
	function _getImageInsertForm()
	{
		global $OUT,$imageCFG;
		$src_img = '/images/null.gif';
		$width = 1;
		$height = 1;
		if(is_file('../../..'.$_REQUEST['file'])){
		    $src_img = $src = $_REQUEST['file'];
		    list($width, $height) = getimagesize('../../..'.$_REQUEST['file']);
		}
		include("include/image.htm");
	}

	function _getFilesPathChooseForm()
	{
		global $USER,$OUT,$imageCFG;

		include("include/files_path_choose.php");
		include'../../../data/gallery_window.html';
	}
}

class parents
{
	function process()
	{
		global $USER,$imageCFG;
		
		if(!$USER)
		{
			header("location: /admin/"); 
			exit();
		}

		$action = $_REQUEST['action'];

		if($USER['login'] && $action) error_log(date('d.m.Y H:i')."	{$USER['login']}	{$action} {$_REQUEST['what']}	inserImage operations\n", 3, $imageCFG['log_file_path']);

		if($action && method_exists($this,$action)) $this->$action(); 
	}		

	function files()
	{
		global $USER,$OUT;

		if(!$USER) return;
		
		$cur = new children();
		$OUT['content'] .= $cur->_getFilesPathChooseForm();
	}

	function form()
	{
		global $USER,$OUT;

		if(!$USER) return;

		$cur = new children();
		$OUT['content'] .= $cur->_getImageInsertForm();
	}
}

$cat = new parents();
$cat->process();
?>