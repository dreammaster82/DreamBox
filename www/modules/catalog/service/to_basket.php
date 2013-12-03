<?php
include'../../../include/setup.php';
$ret = array('ok' => false);
ob_start();
if((float)$_REQUEST['id']){
	$Core = new Core(Core::UTIL | Core::CONNECTED);
	$ret['ok'] = $Core->getClass(array('Basket', 'catalog'))->toBasket((float)$_REQUEST['id']);
	$ret['html'] = 'В корзине пусто';
	if($_SESSION['basket']){
		$sum = 0;
		foreach ($_SESSION['basket'] as $k => $v){
			$sum += $v['price'] * $v['count'];
		}
		if($sum){
			$ret['html'] = '<u></u> ('.sizeof($_SESSION['basket']).') '.number_format($sum, 0, '', ' ').' руб.';
		}
	}
}
$ret['errors'] = ob_get_clean();
echo json_encode($ret);
?>
