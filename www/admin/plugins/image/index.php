<?php
<<<<<<< HEAD

include('../../../include/config.php');	# конфигурация БД и т.д.
include('../../../include/util.php');		# утилиты 
include('../../../include/forall.php');
=======
include realpath($_SERVER['DOCUMENT_ROOT']).'/include/setup.php';
include_once CLIENT_PATH.'/include/auth.php';
>>>>>>> 41a0a7e... image plugin

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
<<<<<<< HEAD
		global $OUT,$imageCFG;
=======
>>>>>>> 41a0a7e... image plugin
		$src_img = '/images/null.gif';
		$width = 1;
		$height = 1;
		if(is_file('../../..'.$_REQUEST['file'])){
		    $src_img = $src = $_REQUEST['file'];
		    list($width, $height) = getimagesize('../../..'.$_REQUEST['file']);
		}
<<<<<<< HEAD
		include("include/image.htm");
=======
		ob_start();
		include("include/image.htm");
		return ob_get_clean();
>>>>>>> 41a0a7e... image plugin
	}

	function _getFilesPathChooseForm()
	{
		global $USER,$OUT,$imageCFG;

		include("include/files_path_choose.php");
<<<<<<< HEAD
		include'../../../data/gallery_window.html';
	}
}

class parents
{
	function process()
	{
		global $USER,$imageCFG;
		
		if(!$USER)
=======
		return $ret;
	}
}

class Image{
	private $config, $Auth;
			
	function __construct($Core){
		global $imageCFG;
		
		$this->Core = $Core;
		$this->config = $imageCFG;
		$this->Auth = $this->Core->getClass('Auth');
	}
	
	function process()
	{
		if(!$this->Auth->user)
>>>>>>> 41a0a7e... image plugin
		{
			header("location: /admin/"); 
			exit();
		}

		$action = $_REQUEST['action'];

<<<<<<< HEAD
		if($USER['login'] && $action) error_log(date('d.m.Y H:i')."	{$USER['login']}	{$action} {$_REQUEST['what']}	inserImage operations\n", 3, $imageCFG['log_file_path']);

		if($action && method_exists($this,$action)) $this->$action(); 
=======
		if($this->Auth->user['login'] && $action) error_log(date('d.m.Y H:i')."	{$this->Auth->user['login']}	{$action} {$_REQUEST['what']}	inserImage operations\n", 3, $this->config['log_file_path']);

		if($action && method_exists($this,$action)) return $this->$action(); 
>>>>>>> 41a0a7e... image plugin
	}		

	function files()
	{
<<<<<<< HEAD
		global $USER,$OUT;

		if(!$USER) return;
		
		$cur = new children();
		$OUT['content'] .= $cur->_getFilesPathChooseForm();
=======
		if(!$this->Auth->user) return;
		
		$cur = new children();
		return $cur->_getFilesPathChooseForm();
>>>>>>> 41a0a7e... image plugin
	}

	function form()
	{
<<<<<<< HEAD
		global $USER,$OUT;

		if(!$USER) return;

		$cur = new children();
		$OUT['content'] .= $cur->_getImageInsertForm();
	}
}

$cat = new parents();
$cat->process();
=======
		if(!$this->Auth->user) return;

		$cur = new children();
		return $cur->_getImageInsertForm();
	}
}
$Core = new Core();
$Core->getClass('Auth')->process();
$cat = new Image($Core);
$cat->Db = $Core->getClass('Db');
$out['content'] = $cat->process();
include ADMIN_PATH.'/data/gallery_window.html';
>>>>>>> 41a0a7e... image plugin
?>