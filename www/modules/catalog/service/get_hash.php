<?php
$ok = false;
$hash = false;
$action = array(
	1 => 'order',
);
if($_POST['name']){
	session_name('sessn');
	session_start();
	$hash = md5($_POST['name']);
	if(isset($_POST['type'])){
		$_SESSION[$_POST['name']][$_POST['type']] = $hash;
	} else {
		$_SESSION[$_POST['name']] = $hash;
	}
	$ok = true;
}
echo json_encode(array('ok' => $ok, 'hash' => $hash, 'action' => $action[(int)$_POST['action']]));
?>
