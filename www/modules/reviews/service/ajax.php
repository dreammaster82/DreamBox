<?php
include realpath($_SERVER['DOCUMENT_ROOT']).'/include/core.php';
ob_start();
$Core = new Core(Core::CONNECTED | Core::UTIL);
$ret = array('ok' => false, 'data' => '', 'errors' => '');
$action = trim($_REQUEST['action']);
$err = array();
if($action && $_COOKIE['code'] == $_REQUEST['code']){
	$_REQUEST = array_map(function($a){return htmlentities(trim($a), ENT_COMPAT | ENT_HTML5, 'UTF-8');}, $_REQUEST);
	$_REQUEST['email'] = html_entity_decode($_REQUEST['email'], ENT_COMPAT | ENT_HTML5, 'UTF-8');
	switch($action){
		case 'send_review':
			if(!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)){
				$err[] = 'Введите корректный email.';
			} else {
				$C = $Core->getClass(array('Reviews', 'reviews'));
				$_REQUEST['raiting'] = $_REQUEST['range'];
				if($C->appendReview($_REQUEST)){
					$ret['data'] = 'Отзыв успешно добавлен. Будет показан после модерации.';
					$ret['ok'] = true;
				} else {
					$ret['data'] = 'Ошибка добавления отзыва.';
				}
			}
			break;
	}
}
$ret['errors'] = ob_get_clean().implode("\n", $err);
echo json_encode($ret);