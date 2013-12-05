<?php
ob_start();
include_once './Session_Crypt.php';
$ses_cr = new session_crypt();
$secret = substr(uniqid(rand()), 1, 4);
$secret_str = iconv('Windows-1251', 'UTF-8//IGNORE', $ses_cr->encode($secret));
$errors = ob_get_clean();
echo json_encode(array('key' => $secret_str, 'errors' => $errors));
?>