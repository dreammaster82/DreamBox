<?php
include realpath($_SERVER['DOCUMENT_ROOT']).'/include/setup.php';
include_once ADMIN_PATH.'/include/admin_functions.php';
include_once ADMIN_PATH.'/include/ccontent_admin.php';
$out = array();
$Core = new Core(Core::UTIL | Core::CONNECTED);
$Core->getClass('Auth')->process();
$out['index'] = false;
if($_REQUEST['module']){
	if($_REQUEST['admin']){
		$out['content'] = $Core->process(array(Core::CLASS_NAME => ucfirst($_REQUEST['module']), Core::MODULE => 'admin', Core::ADMIN => true));
	} else {
		$out['content'] = $Core->process(array(Core::ADMIN => true));
	}
} else {
	include ADMIN_PATH.'/include/admin.php';
	$out['content'] = $Core->process(array(Core::CLASS_NAME => 'Admin', Core::MODULE => 'admin', Core::ADMIN => true));
}
	$out['js'] = $Core->ret['js_before'].$Core->ret['js_after'];
	unset($Core->ret['js_before']);
	unset($Core->ret['js_after']);
	if($Core->ret){
		foreach ($Core->ret as $k => $v){
			$out[$k] = $v;
			unset($Core->ret[$k]);
		}
	}
	
	
$out['template'] = $out['template'] ? $out['template'] : 'admin';

if($_REQUEST['window']){
	$out['template'] = 'gallery_window';
}
include ADMIN_PATH.'/data/'.$out['template'].'.html';
?>