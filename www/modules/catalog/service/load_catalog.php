<?php
ob_start();
include'../../../include/setup.php';
$ret = array('ok' => false, 'html' => '');
$Core = new Core();
if($_REQUEST['href'] && $C = $Core->getClass(array('CatalogViewer', 'catalog'))){
	$url = parse_url($_REQUEST['href']);
	$query = array();
	parse_str($url['query'], $query);
	$_REQUEST = array_merge($_REQUEST, $C->parseRequest(str_replace('/catalog', '', $url['path'])), $query);
	$item = $C->getItem(array('id'), $_REQUEST['alias']);
	if(!(int)$_REQUEST['showAll'] && ($_REQUEST['sort'] || (int)$_REQUEST['page']) && $P = $Core->getClass(array('ProductViewer', 'catalog'))){
		$cond = array('active' => 1);
		if($item['id']){
			$cond['ca_id'] = $item['id'];
		}
		$tData = array('id', 'ca_id', 'name', 'img_src', 'alias', 'price', 'sale', 'articul', 'ca_alias');
		$order = array();
		if($_REQUEST['sort'] == 'name'){
			$order[] = 'name';
		}
		$order[] = 'price';
		$items = $P->getItems($tData, $cond, $order, 12);
		if($items){
			$_REQUEST['ajax'] = 1;
			$ret['html'] = $P->showItems($items);
		}
		$ret['ok'] = true;
	} else {
		$item = $C->getItem(array('id', 'title', 'alias'), $_REQUEST['alias']);
		if($item['id']){
			$ret['title'] = $item['title'];
			if($P = $C->Core->getClass(array(\Core::CLASS_NAME => 'ProductViewer', \Core::MODULE => 'catalog'))){
				$out['cond'] = array('ca_id' => $item['id'], 'active' => 1);
				if($_REQUEST['find']){
					$out['cond']['find'] = $_REQUEST['find'];
				}
				$out['count_products'] = $P->getCountItems($out['cond']);
				if($out['count_products']){
					$tData = array('id', 'ca_id', 'name', 'img_src', 'alias', 'price', 'sale', 'articul', 'ca_alias');
					$order = array();
					if($_REQUEST['sort'] == 'name'){
						$order[] = 'name';
					}
					$order[] = 'price';
					$out['products'] = $P->getItems($tData, $out['cond'], $order, 12);
					$ret['html'] = $C->showProducts($out['products'], $out['count_products'], $out['cond'], $item);
				}
			}
			$ret['ok'] = true;
		}
	}
} else {
	echo 'Ошибка запроса';
}
$ret['errors'] = ob_get_clean();
echo json_encode($ret);
?>
