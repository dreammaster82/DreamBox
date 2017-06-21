<?php
include_once'Session_Crypt.php';
$crypt = new session_crypt();
$secret = $crypt->decode(iconv('UTF-8', 'Windows-1251//IGNORE', $_REQUEST['key']));
if (!$secret) $secret = '0000';
$filename = $secret.'.png';
getImageCode($secret, $filename);

function getImageCode($secret, $filename){
	$font = 'Scada-Regular.ttf';
	$shFont = 'Scada-Regular.ttf';
	$imgPath = 'fon_code.png';
	$x0 = 5;							// отступ слева
	$xMin = 10; $xMax = 25;			// диапазон смещений по Х
	$y0 = 15;							// отступ сверху
	$yMin = 0; $yMax = 15;			// диапазон смещений по Y
	$sizeMin = 12; $sizeMax = 18;		// диапазон размеров шрифта
	$angleMin = -10; $angleMax = 10;	// диапазон угла наклона
	$rMin = 1; $rMax = 50;			// диапазон цвета red
	$img = imagecreatefrompng($imgPath);
	$len = strlen($secret);
	for($i=0; $i < $len; ++$i){
		$x = $x0 + (rand($xMin, $xMax) * $i);
		$y = $y0 + rand($yMin, $yMax);
		$size = rand($sizeMin, $sizeMax);
		$angle = rand($angleMin, $angleMax);
		$color = imagecolorallocate($img, 0xff, 0xff, 0xff); // ЧБ вариант
		//---Shadow---//
		//imageTTFtext($img, $size, $angle, $x, $y-1, imagecolorallocate($img, 0x00, 0x96, 0x61), $shFont, $secret[$i]);
		imageTTFtext($img, $size, $angle, $x, $y, $color, $font, $secret[$i]);
		
	}
	header('Content-Type: image/png');
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')){
		header('Content-Disposition: inline; filename='.$filename);
	} else {
		header('Content-Disposition: attachment; filename='.$filename);
	}
	imagepng($img);
	imagedestroy($img);	
}
?>