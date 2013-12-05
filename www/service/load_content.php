<?php
ob_start();
include '../include/setup.php';
include_once CLIENT_PATH.'/include/ccontent.php';
include_once CLIENT_PATH.'/include/ccontent_viewer.php';
setSession();
Db::connect();
$data = array(
	'ok' => false,
	'html' => '',
	'error' => ''
);
if($_REQUEST['module']){
	$MODULE = $_REQUEST['module'];
	if(is_file(MODULES_PATH.'/'.$MODULE.'/'.$MODULE.'.php')){
		$class = ucfirst($MODULE);
		if(!class_exists($class)){
			include_once MODULES_PATH.'/'.$MODULE.'/'.$MODULE.'.php';
		}
		if(!(int)$_REQUEST['no_view']){
			$class .= 'Viewer';
			if(!class_exists($class)){
				include_once MODULES_PATH.'/'.$MODULE.'/'.$MODULE.'_viewer.php';
			}
		}
		if(method_exists($C, 'ajaxProcess')){
			$ret = $C->ajaxProcess();
			$data['html'] = $C->ret['before'].$ret.$C->ret['after'].'<script>setTitle("'.$C->ret['title'].'","'.$C->ret['description'].'","'.$C->ret['keywords'].'");initContent();</script>';
			$data['ok'] = true;
		} else {
			echo 'Невозможно запустить выполнение';
		}
	} else {
		echo 'Ошибка запроса';
	}
} else {
	echo 'Ошибка запроса';
}
$data['error'] = ob_get_clean();
echo json_encode($data);
?>
