<?php
include realpath($_SERVER['DOCUMENT_ROOT']).'/include/setup.php';
include_once CLIENT_PATH.'/admin/include/admin_functions.php';
include_once ADMIN_PATH.'/include/ccontent_admin.php';
include_once CLIENT_PATH.'/admin/plugins/hiperLink2/hiperLink2.php';
$Core = new Core(Core::CONNECTED | Core::UTIL);
$C = $Core->getClass(array(Core::CLASS_NAME => 'HiperLink2', Core::MODULE => 'admin'));
$OUT['content'] .= $C->process();
$OUT['description'] = $OUT['keywords'] = $OUT['title'] = $C->ret['title'];
$OUT['warning'] = $C->ret['warning'];
$OUT['script'] = $C->ret['script'];

if($_REQUEST['window']){
	include ADMIN_PATH.'/data/gallery_window.html';
} else {
    include ADMIN_PATH.'/data/admin.html';
}

?>
