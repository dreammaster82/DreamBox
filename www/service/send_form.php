<?php
include realpath($_SERVER['DOCUMENT_ROOT']).'/include/setup.php';
include_once CLIENT_PATH.'/service/dimages/Session_Crypt.php';
ob_start();
$Core = new Core(self::CONNECTED | self::UTIL);
$ok = false;
$data = '';
if (!empty($_POST)){
    /* Filter all POST data in one step. */
    $_POST = array_map('stripcslashes', array_map('trim', $_POST));
	/* Get initial secret key. */
	$crypt = new session_crypt();
	$initSecr = iconv("utf-8", 'windows-1251', $_POST["secret_crypted"]);
	$encr = $crypt->decode($initSecr);
	$err = array();
	if (empty($secret)){
		$err[] = 'Вы не указали код защиты';
	} elseif ($secret != $encr){
		$err[] = 'Укажите правильный код защиты';
	}
	if(!$err){
		$bodyMsg = 'Здравствуйте! <br /><br /> <p>На Вашем сайте оставлен запрос звонка от: <strong>'.$_POST['name'].'</strong><br /><br /> на номер: <strong>'.$_POST['tel'].'</strong></p><br /><b>Дата размещения заявки:</b>'.date('l jS \of F Y h:i:s');
		$mail = $Core->getClass('Util')->mail($CONFIG['email']['from_email'], $CONFIG['email']['from_text'], 'Запрос звонка', $CONFIG['email']['from_email'], $bodyMsg);
		if ($mail){
			$data = 'Отправлено';
		} else {
			$data = 'Ошибка отправления';
		}
		$ok = true;
	}
}
$errors = ob_get_clean();
echo json_encode(array('ok' => $ok, 'data' => $data, 'errors' => $errors));