<?php
include realpath($_SERVER['DOCUMENT_ROOT']).'/include/core.php';
ob_start();
$Core = new Core(Core::CONNECTED | Core::UTIL);
$ok = false;
$data = '';
if (!empty($_POST)){
    /* Filter all POST data in one step. */
    $_POST = array_map('stripcslashes', array_map('trim', $_POST));
	$err = array();
	if($_COOKIE['code'] != $_POST['code']){
		$err[] = 'Вы робот!';
	}
	if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
		$err[] = 'Введите корректный email!';
	}
	if(!$err){
		ob_start();?>
Здравствуйте!
<br />
<br />
<p>
	На Вашем сайте оставлен запрос обратной связи от: <strong><?=$_POST['name']?>'</strong>
	<br />
	на e-mail: <strong><?=$_POST['email']?></strong>
<?	if($_POST['comment']){?>
	<br />
	комментарий: <?=$_POST['comment']?>
<?	}?>
</p>
<br /><b>Дата размещения заявки:</b> <?=date('l jS \of F Y h:i:s')?>
<?php		$mail = $Core->getClass('Util')->mail($CONFIG['email']['from_email'], $CONFIG['email']['from_text'], $CONFIG['email']['from_email'], 'Запрос обратной связи', ob_get_clean());
		if ($mail){
			$data = 'Успешно отправлено';
		} else {
			$data = 'Ошибка отправления';
		}
		$ok = true;
	}
}
$errors = ob_get_clean();
echo json_encode(array('ok' => $ok, 'data' => $data, 'errors' => $errors.implode("\n", $err)));
?>