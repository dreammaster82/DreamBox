<?php
define ('VERSION', '0.1');
//Image size and defaults
if(! defined('PNG_IS_TRANSPARENT') ) 	define ('PNG_IS_TRANSPARENT', FALSE);  //42 Define if a png image should have a transparent background color. Use False value if you want to display a custom coloured canvas_colour 
if(! defined('DEFAULT_Q') )				define ('DEFAULT_Q', 75);									// Default image quality. Allows overrid in timthumb-config.php
if(! defined('DEFAULT_ZC') )			define ('DEFAULT_ZC', 1);									// Default zoom/crop setting. Allows overrid in timthumb-config.php
if(! defined('DEFAULT_F') )				define ('DEFAULT_F', '');									// Default image filters. Allows overrid in timthumb-config.php
if(! defined('DEFAULT_S') )				define ('DEFAULT_S', 0);									// Default sharpen value. Allows overrid in timthumb-config.php
if(! defined('DEFAULT_CC') )			define ('DEFAULT_CC', '#ffffff');							// Default canvas colour. Allows overrid in timthumb-config.php
if(!defined('DEFAULT_TRIM')) define('DEFAULT_TRIM', 0);

if($_GET['src']){
    ImagePreview::start();
}


class ImagePreview{
	
	private $errors = [],
			$is404 = false,
			$fTypes = array(
				'image/gif'=>'gif',	
				'image/jpeg'=>'jpg',
				'image/pjpeg'=>'jpg',
				'image/png'=>'png',
				'image/x-png'=>'png'
			);

	protected $PreviewTypes = [],
			$startTime = 0,
			$docRoot = '',
			$saveDirectory = '',
			$src = '',
			$type = '',
			$localImage = '',
			$cachefile = '';

	public static function start(){
		$C = new ImagePreview();
		$C->handleErrors();
		$C->run();
		$C->handleErrors();
		if($_GET['notview']){
			return true;
		}
		exit(0);
	}
	
	public function __construct(){
		global $PreviewTypes;
		
		$this->PreviewTypes = $PreviewTypes;
		$this->startTime = microtime(true);
		date_default_timezone_set('UTC');
		$this->docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
		$this->src = $this->param('src');
		$this->type = $this->param('type');
		$this->PreviewType = $PreviewTypes[$this->type];
		if(!empty($this->PreviewType)){
			if(!is_dir('./type_'.$this->type)){
				mkdir('./type_'.$this->type, 0775, true);
			}
			$this->saveDirectory = realpath('./type_'.$this->type);
		} 
		if(!$this->saveDirectory){
			$this->saveDirectory = sys_get_temp_dir();
		}
		if(strlen($this->src) <= 4){
			$this->error("No image specified");
			return false;
		}
		$myHost = preg_replace('/^www\./i', '', $_SERVER['HTTP_HOST']);
		if(preg_match('/https?:\/\/(?:www\.)?' . $myHost . '(?:$|\/)/i', $this->src)){
			$this->src = preg_replace('/https?:\/\/(?:www\.)?' . $myHost . '/i', '', $this->src);
		}
		if(preg_match('/^https?:\/\/[^\/]+/i', $this->src)){
			$this->error('Is external image');
			return false;
		}
		$this->localImage = $this->getLocalImagePath($this->src);
		if(!$this->localImage){
		    $this->error('Could not find the internal image you specified.');
			$this->set404(true);
		    return false;
		}
		
		$pathArr = explode('/', $this->src);
		$file = array_pop($pathArr);
		$pathname = $this->saveDirectory.'/'.$path = implode('/', $pathArr);
		if(!is_dir($pathname)){
			mkdir($pathname, 0775, true);
		}
		$this->cachefile = $pathname.'/'.$file; 
	}
	
	protected function error($err){
		$this->errors[] = $err;
		return false;
	}
	
	protected function getLocalImagePath($src){
		$src = preg_replace('/^\//', '', $src); //strip off the leading '/'
		$ret = '';
		//Try src under docRoot
		if(file_exists($this->docRoot.'/'.$src)) {
			$ret = $this->docRoot.'/'.$src;
		} else {
			//Check absolute paths and then verify the real path is under doc root
			$absolute = realpath('/'.$src);
			if($absolute && file_exists($absolute)){ //realpath does file_exists check, so can probably skip the exists check here
				$ret = $absolute;
			}
		}
		return $ret;
	}
	
	protected function handleErrors(){
		if($this->errors){ 
			if(NOT_FOUND_IMAGE && $this->get404()){
				$this->error("Additionally, the 404 image that is configured could not be found or there was an error serving it.");
			}
			if(ERROR_IMAGE){
				$this->error("Additionally, the error image that is configured could not be found or there was an error serving it.");
			}
			header ($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
			$ret = '<ul>';
			foreach($this->errors as $err){
				$ret .= '<li>' . htmlentities($err) . '</li>';
			}
			$ret .= '</ul>';
			echo $ret;
			echo '<br />Query String : ' . htmlentities ($_SERVER['QUERY_STRING']);
			echo '<br />version : ' . VERSION . '</pre>';
			exit(0); 
		}
		return false;
	}
	
	protected function param($property, $default = ''){
		if (isset ($_GET[$property])) {
			return $_GET[$property];
		} else {
			return $default;
		}
	}
	
	function run(){
		$params = isset($this->PreviewType) ? $this->PreviewType : [];
		if($this->cropTop && !$params['align']){
			$params['align'] = 't';
		}
		if($this->process($this->localImage, $this->cachefile, $params)){
			$this->serveCacheFile();
		}
	}
	
	protected function serveCacheFile(){
		if(!is_file($this->cachefile)){
			$this->error("serveCacheFile called in timthumb but we couldn't find the cached file.");
			return false;
		}
		if($_GET['notview']){
			return true;
		}
		$fdata = getimagesize($this->cachefile);
		$imageDataSize = filesize($this->cachefile);
		$this->sendImageHeaders($fdata['mime'], $imageDataSize);
		if(readfile($this->cachefile) === false){
			$this->error("Cache file could not be loaded.");
			return false;
		}
		return true;
	}
	
	protected function sendImageHeaders($mimeType, $dataSize){
		if(!preg_match('/^image\//i', $mimeType)){
			$mimeType = 'image/'.$mimeType;
		}
		if(strtolower($mimeType) == 'image/jpg'){
			$mimeType = 'image/jpeg';
		}
		$gmdate_expires = gmdate ('D, d M Y H:i:s', strtotime ('now +10 days')).' GMT';
		$gmdate_modified = gmdate ('D, d M Y H:i:s').' GMT';
		// send content headers then display image
		header ('Content-Type: '.$mimeType);
		header ('Accept-Ranges: none'); //Changed this because we don't accept range requests
		header ('Last-Modified: '.$gmdate_modified);
		header ('Content-Length: '.$dataSize);
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header("Pragma: no-cache");
		header('Expires: '.gmdate ('D, d M Y H:i:s', time()));
		return true;
	}
	
	function process($from, $to, array $params){
		$sData = getimagesize($from);
		$origType = $sData[2];
		$mimeType = $sData['mime'];
		if(!in_array($mimeType, array_keys($this->fTypes))){
			return $this->error('The image being resized is not a valid gif, jpg or png.');
		}
		
		// get standard input properties
		$width =  $params['width'] ? $params['width'] : (int)abs($this->param('w', 0));
		$height = $params['height'] ? $params['height'] : (int)abs($this->param('h', 0));
		$zc =  isset($params['zoom_crop']) ? $params['zoom_crop'] : (int)$this->param('zc', DEFAULT_ZC);
		$quality = $params['quality'] ? $params['quality'] : (int)abs($this->param('q', DEFAULT_Q));
		$align = $params['align'] ? $params['align'] : $this->param('a', 'c');
		$filters = $params['filters'] ? $params['filters'] : $this->param('f', '');
		$sharpen = $params['sharpen'] ? $params['sharpen'] : (bool)$this->param('s', 0);
		$color = $params['color'] ? $params['color'] : $this->param('cc', DEFAULT_CC);
		if (strlen($color) < 6) {
			$color = '#ffffff';
		}
		$trim = $params['trim'] ? $params['trim'] : (int)$this->param('tr', DEFAULT_TRIM);

		$mw = NewMagickWand();
		MagickReadImage($mw, $from);
		// Get original width and height
		$imWidth = MagickGetImageWidth($mw);
		$imHeight = MagickGetImageHeight($mw);
		
		$orX = 0;
		$orY = 0;

		// generate new w/h if not provided
		if ($width && !$height) {
			$height = floor ($imHeight * ($width / $imWidth));
		} else if ($height && !$width) {
			$width = floor ($imWidth * ($height / $imHeight));
		}
		if(in_array($zc, array(2, 3, 5))){
			if ($zc == 3 || $zc == 5) {
				$zoom = false;
				if($zc == 5){
					if($imWidth > $width || $imHeight > $height){
						$zoom = true;
					} else {
						$width = $imWidth;
						$height = $imHeight;
					}
				}
				if($zc == 3 || $zoom){
					$fHeight = $imHeight * ($width / $imWidth);
					if ($fHeight > $height) {
						$width = $imWidth * ($height / $imHeight);
					} else {
						$height = $fHeight;
					}
					$mw = MagickTransformImage($mw, NULL, $width.'x'.$height);
				}
			}

			if ($zc == 2) {
				$nmw = NewMagickWand();
				MagickNewImage($nmw, $width, $height);
				MagickSetImageFormat($nmw, $mimeType);
				$fHeight = $imHeight * ($width / $imWidth);
				if ($fHeight > $height) {
					$orX = $width / 2;
					$width = $imWidth * ($height / $imHeight);
					$orX = round ($orX - ($width / 2));
				} else {
					$orY = $height / 2;
					$height = $fHeight;
					$orY = round ($orY - ($height / 2));
				}
			}
			$mw = MagickTransformImage($mw, NULL, $width.'x'.$height);
			
			if($orX || $orY){
				MagickCompositeImage($nmw, $mw, MW_BlendCompositeOp, $orX, $orY);
				$mw = $nmw;
			}
		} else {
			if($zc == 4){
				$nwd = NewDrawingWand();
				$pw = NewPixelWand();
				PixelSetColor($pw, $color);
				PixelSetOpacity($pw, 1);
				DrawSetFillColor($nwd, $pw);
				$nmw = NewMagickWand();
				MagickNewImage($nmw, $width, $height, $color);
				MagickSetImageFormat($nmw, 'png');
				
				$mw = $nmw;
				//MagickCompositeImage($mw, $nwd, MW_BlendCompositeOp, 0, 0);
			} elseif($zc == 1) {
				$imWidth = $width;
				$imHeight = $height;
				if($width > $height){
					$imHeight = $width;
				} else {
					$imWidth = $height;
				}
				MagickResizeImage($mw, $imWidth, $imHeight, MW_QuadraticFilter, 1.0);
				MagickCropImage($mw, $width, $height, abs($width - $imWidth) / 2, abs($height - $imHeight) / 2);
			} else {
				MagickResizeImage($mw, $width, $height, MW_QuadraticFilter, 1.0);
			}
		}
		MagickSharpenImage($mw, 1, 4);
		MagickWriteImage($mw, $to);
		ClearMagickWand($mw); //Удаляем и выгружаем полученное изображение из памяти
		DestroyMagickWand($mw);
		return true;
	}
	
	function set404(bool $set){
		$this->is404 = $set;
	}
	
	function get404(){
		return $this->is404;
	}
}

function print_r1($obj){
	echo"<pre>";
	print_r($obj);
	echo"</pre>";
}
?>