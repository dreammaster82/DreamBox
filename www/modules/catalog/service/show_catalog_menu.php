<?php
include'../../../include/setup.php';
$ret = array('ok' => false);
ob_start();
if(is_array($_REQUEST['id'])){
	$Core = new Core(Core::UTIL | Core::CONNECTED);
	foreach ($_REQUEST['id'] as $v){
		$ret['data'][$v] = $Core->getClass(array('CatalogViewer', 'catalog'))->showCatalogMenu($v, 1, true);
	}
	$ret['ok'] = true;
}
$ret['errors'] = ob_get_clean();
echo json_encode($ret);
?>
