<?php
//---Картинка и код для формы обратной связи
include_once CLIENT_PATH.'/service/dimages/Session_Crypt.php';
$secret = substr(uniqid(rand()), 1, 4);
$crypt = new session_crypt();
$string = iconv('windows-1251', 'utf-8', $crypt->encode($secret));
$secFile = '/service/dimages/get_image_crypted.php?key='.$string;
$out['js'] .= '<script>window.secretImage = "'.$secFile.'"; window.secretCrypted = "'.$secret.'";</script>';

$out['basket'] = 'Корзина пуста';
if($_SESSION['basket']){
	$sum = 0;
	foreach ($_SESSION['basket'] as $k => $v){
		$sum += $v['price'] * $v['count'];
	}
	if($sum){
		$out['basket'] = '('.sizeof($_SESSION['basket']).') '.number_format($sum, 0, '', ' ').' руб.';
	}
}

$out['catalog_menu'] = $Core->getClass(array('CatalogViewer', 'catalog'))->showCatalogMenu();
$out['example'] = $Core->getClass('Util')->memcacheGet('example');
if(!$out['example']){
	$out['example'] = 'нитяные шторы';
}
?>