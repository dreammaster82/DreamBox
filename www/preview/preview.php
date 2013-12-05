<?php
define ('VERSION', '2.8.10');
if( file_exists(dirname(__FILE__) . '/previewtype.php'))	require_once('previewtype.php');
if(! defined('FILE_CACHE_DIRECTORY') ) 		define ('FILE_CACHE_DIRECTORY', './cache');				// Directory where images are cached. Left blank it will use the system temporary directory (which is better for security)
if(! defined('MAX_FILE_SIZE') )				define ('MAX_FILE_SIZE', 10485760);						// 10 Megs is 10485760. This is the max internal or external file size that we'll process.  
//Browser caching
if(! defined('BROWSER_CACHE_MAX_AGE') ) 	define ('BROWSER_CACHE_MAX_AGE', 864000);				// Time to cache in the browser
if(! defined('BROWSER_CACHE_DISABLE') ) 	define ('BROWSER_CACHE_DISABLE', false);				// Use for testing if you want to disable all browser caching
//Image fetching and caching
if(! defined('FILE_CACHE_ENABLED') ) 		define ('FILE_CACHE_ENABLED', TRUE);					// Should we store resized/modified images on disk to speed things up?
if(! defined('FILE_CACHE_TIME_BETWEEN_CLEANS'))	define ('FILE_CACHE_TIME_BETWEEN_CLEANS', 86400);	// How often the cache is cleaned 
//Image size and defaults
if(! defined('MAX_WIDTH') ) 			define ('MAX_WIDTH', 1500);									// Maximum image width
if(! defined('MAX_HEIGHT') ) 			define ('MAX_HEIGHT', 1500);								// Maximum image height
if(! defined('NOT_FOUND_IMAGE') )		define ('NOT_FOUND_IMAGE', '');								// Image to serve if any 404 occurs 
if(! defined('ERROR_IMAGE') )			define ('ERROR_IMAGE', '');									// Image to serve if an error occurs instead of showing error message 
if(! defined('PNG_IS_TRANSPARENT') ) 	define ('PNG_IS_TRANSPARENT', FALSE);  //42 Define if a png image should have a transparent background color. Use False value if you want to display a custom coloured canvas_colour 
if(! defined('DEFAULT_Q') )				define ('DEFAULT_Q', 75);									// Default image quality. Allows overrid in timthumb-config.php
if(! defined('DEFAULT_ZC') )			define ('DEFAULT_ZC', 1);									// Default zoom/crop setting. Allows overrid in timthumb-config.php
if(! defined('DEFAULT_F') )				define ('DEFAULT_F', '');									// Default image filters. Allows overrid in timthumb-config.php
if(! defined('DEFAULT_S') )				define ('DEFAULT_S', 0);									// Default sharpen value. Allows overrid in timthumb-config.php
if(! defined('DEFAULT_CC') )			define ('DEFAULT_CC', 'ffffff');							// Default canvas colour. Allows overrid in timthumb-config.php
if(!defined('DEFAULT_TRIM')) define('DEFAULT_TRIM', 0);

if($_GET['src']){
    timthumb::start();
}

class timthumb {
	protected $src = "";
	protected $is404 = false;
	protected $docRoot = "";
	protected $lastURLError = false;
	protected $localImage = "";
	protected $localImageMTime = 0;
	protected $url = false;
	protected $myHost = "";
	protected $isURL = false;
	public $cachefile = '';
	protected $errors = array();
	protected $toDeletes = array();
	protected $cacheDirectory = '';
	protected $startTime = 0;
	protected $lastBenchTime = 0;
	protected $cropTop = false;
	protected $salt = "";
	protected $fileCacheVersion = 1; //Generally if timthumb.php is modifed (upgraded) then the salt changes and all cache files are recreated. This is a backup mechanism to force regen.
	protected $filePrependSecurityBlock = "<?php die('Execution denied!'); //"; //Designed to have three letter mime type, space, question mark and greater than symbol appended. 6 bytes total.
	protected static $curlDataWritten = 0;
	protected static $curlFH = false;
	public static function start(){
		$tim = new timthumb();
		$tim->handleErrors();
		if($tim->tryBrowserCache()){
			exit(0);
		}
		$tim->handleErrors();
		if(FILE_CACHE_ENABLED && $tim->tryServerCache()){
			exit(0);
		}
		$tim->handleErrors();
		$tim->run();
		$tim->handleErrors();
                if($_GET['notview']){
                    return true;
                }
		exit(0);
	}
	public function __construct(){
		global $ALLOWED_SITES, $PreviewTypes;
		$this->startTime = microtime(true);
		date_default_timezone_set('UTC');
		$this->debug(1, "Starting new request from " . $this->getIP() . " to " . $_SERVER['REQUEST_URI']);
		$this->calcDocRoot();
		//On windows systems I'm assuming fileinode returns an empty string or a number that doesn't change. Check this.
		$this->salt = @filemtime(__FILE__) . '-' . @fileinode(__FILE__);
		$this->debug(3, "Salt is: " . $this->salt);
                if($_GET['cache_dir']){
                    $this->cacheDirectory = $_GET['cache_dir'];
                } else {
                    if(FILE_CACHE_DIRECTORY){
			if(! is_dir(FILE_CACHE_DIRECTORY)){
				mkdir(FILE_CACHE_DIRECTORY, 0777, true);
				if(! is_dir(FILE_CACHE_DIRECTORY)){
					$this->error("Could not create the file cache directory.");
					return false;
				}
			}
			$this->cacheDirectory = FILE_CACHE_DIRECTORY;
			if (!touch($this->cacheDirectory . '/index.html')) {
				$this->error("Could note create the index.html file.");
			}
                    } else {
			$this->cacheDirectory = sys_get_temp_dir();
                    }
                }
		$this->myHost = preg_replace('/^www\./i', '', $_SERVER['HTTP_HOST']);
		$this->src = $this->param('src');
		$this->url = parse_url($this->src);
                $this->type = $this->param('type');
                $this->PreviewType = $PreviewTypes[$this->type];
                if(!empty($this->PreviewType)){
                    if(!is_dir('./type_'.$this->type)){
                        mkdir('./type_'.$this->type, 0777, true);
                    }
                    $this->cacheDirectory = './type_'.$this->type;
                }
		if(strlen($this->src) <= 4){
			$this->error("No image specified");
			return false;
		} 
		if(preg_match('/https?:\/\/(?:www\.)?' . $this->myHost . '(?:$|\/)/i', $this->src)){
			$this->src = preg_replace('/https?:\/\/(?:www\.)?' . $this->myHost . '/i', '', $this->src);
		}
		if(preg_match('/^https?:\/\/[^\/]+/i', $this->src)){
			$this->error('Is external image');
			return false;
		}
		$this->localImage = $this->getLocalImagePath($this->src);
		if(! $this->localImage){
		    $this->debug(1, "Could not find the local image: {$this->localImage}");
		    $this->error("Could not find the internal image you specified.");
		    $this->set404();
		    return false;
		}
		$this->debug(1, "Local image path is {$this->localImage}");
		$this->localImageMTime = @filemtime($this->localImage);
		//We include the mtime of the local file in case in changes on disk.
		$path_arr = explode('/', $this->src);
		$last = sizeof($path_arr) - 1;
		foreach($path_arr as $key => $value){
		    if(!empty($value) && $key != $last){
			$this->cacheDirectory .= '/'.$value;
			if(!is_dir($this->cacheDirectory)){
			    mkdir($this->cacheDirectory, 0777);
			}
		    }
		} 
		$this->cachefile = $this->cacheDirectory . '/' . $path_arr[$last]; 
		$this->debug(2, "Cache file is: " . $this->cachefile);
		return true;
	}
	public function __destruct(){
		foreach($this->toDeletes as $del){
			$this->debug(2, "Deleting temp file $del");
			@unlink($del);
		}
	}
	public function run(){
	    $this->debug(3, "Got request for internal image. Starting serveInternalImage()");
	    $this->serveInternalImage();
	    return true;
	}
	protected function handleErrors(){
		if($this->haveErrors()){ 
			if(NOT_FOUND_IMAGE && $this->is404()){
				if($this->serveImg(NOT_FOUND_IMAGE)){
					exit(0);
				} else {
					$this->error("Additionally, the 404 image that is configured could not be found or there was an error serving it.");
				}
			}
			if(ERROR_IMAGE){
				if($this->serveImg(ERROR_IMAGE)){
					exit(0);
				} else {
					$this->error("Additionally, the error image that is configured could not be found or there was an error serving it.");
				}
			}
			$this->serveErrors(); 
			exit(0); 
		}
		return false;
	}
	protected function tryBrowserCache(){
		if(BROWSER_CACHE_DISABLE){ $this->debug(3, "Browser caching is disabled"); return false; }
		if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) ){
			$this->debug(3, "Got a conditional get");
			$mtime = false;
			//We've already checked if the real file exists in the constructor
			if(! is_file($this->cachefile)){
				//If we don't have something cached, regenerate the cached image.
				return false;
			}
			if($this->localImageMTime){
				$mtime = $this->localImageMTime;
				$this->debug(3, "Local real file's modification time is $mtime");
			} else if(is_file($this->cachefile)){ //If it's not a local request then use the mtime of the cached file to determine the 304
				$mtime = @filemtime($this->cachefile);
				$this->debug(3, "Cached file's modification time is $mtime");
			}
			if(! $mtime){ return false; }

			$iftime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
			$this->debug(3, "The conditional get's if-modified-since unixtime is $iftime");
			if($iftime < 1){
				$this->debug(3, "Got an invalid conditional get modified since time. Returning false.");
				return false;
			}
			if($iftime < $mtime){ //Real file or cache file has been modified since last request, so force refetch.
				$this->debug(3, "File has been modified since last fetch.");
				return false;
			} else { //Otherwise serve a 304
				$this->debug(3, "File has not been modified since last get, so serving a 304.");
				header ($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
				$this->debug(1, "Returning 304 not modified");
				return true;
			}
		}
		return false;
	}
	protected function tryServerCache(){
		$this->debug(3, "Trying server cache");
		if(file_exists($this->cachefile)){
			$this->debug(3, "Cachefile {$this->cachefile} exists");
			if($this->isURL){
				$this->debug(3, "This is an external request, so checking if the cachefile is empty which means the request failed previously.");
				if(filesize($this->cachefile) < 1){
					$this->debug(3, "Found an empty cachefile indicating a failed earlier request. Checking how old it is.");
					//Fetching error occured previously
					if(time() - @filemtime($this->cachefile) > WAIT_BETWEEN_FETCH_ERRORS){
						$this->debug(3, "File is older than " . WAIT_BETWEEN_FETCH_ERRORS . " seconds. Deleting and returning false so app can try and load file.");
						@unlink($this->cachefile);
						return false; //to indicate we didn't serve from cache and app should try and load
					} else {
						$this->debug(3, "Empty cachefile is still fresh so returning message saying we had an error fetching this image from remote host.");
						$this->set404();
						$this->error("An error occured fetching image.");
						return false; 
					}
				}
			} else {
				$this->debug(3, "Trying to serve cachefile {$this->cachefile}");
			}
			if($this->serveCacheFile()){
				$this->debug(3, "Succesfully served cachefile {$this->cachefile}");
				return true;
			} else {
				$this->debug(3, "Failed to serve cachefile {$this->cachefile} - Deleting it from cache.");
				//Image serving failed. We can't retry at this point, but lets remove it from cache so the next request recreates it
				@unlink($this->cachefile);
				return true;
			}
		}
	}
	protected function error($err){
		$this->debug(3, "Adding error message: $err");
		$this->errors[] = $err;
		return false;

	}
	protected function haveErrors(){
		if(sizeof($this->errors) > 0){
			return true;
		}
		return false;
	}
	protected function serveErrors(){
		header ($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
		$html = '<ul>';
		foreach($this->errors as $err){
			$html .= '<li>' . htmlentities($err) . '</li>';
		}
		$html .= '</ul>';
		echo $html;
		echo '<br />Query String : ' . htmlentities ($_SERVER['QUERY_STRING']);
		echo '<br />version : ' . VERSION . '</pre>';
	}
	protected function serveInternalImage(){
		$this->debug(3, "Local image path is $this->localImage");
		if(! $this->localImage){
			$this->sanityFail("localImage not set after verifying it earlier in the code.");
			return false;
		}
		$fileSize = filesize($this->localImage);
		if($fileSize > MAX_FILE_SIZE){
			$this->error("The file you specified is greater than the maximum allowed file size.");
			return false;
		}
		if($fileSize <= 0){
			$this->error("The file you specified is <= 0 bytes.");
			return false;
		}
		$this->debug(3, "Calling processImageAndWriteToCache() for local image.");
		if($this->processImageAndWriteToCache($this->localImage)){
			$this->serveCacheFile();
			return true;
		} else { 
			return false;
		}
	}
	protected function processImageAndWriteToCache($localImage){
		$sData = getimagesize($localImage);
		$origType = $sData[2];
		$mimeType = $sData['mime'];

		$this->debug(3, "Mime type of image is $mimeType");
		if(! preg_match('/^image\/(?:gif|jpg|jpeg|png)$/i', $mimeType)){
			return $this->error("The image being resized is not a valid gif, jpg or png.");
		}

		if (!function_exists ('imagecreatetruecolor')) {
		    return $this->error('GD Library Error: imagecreatetruecolor does not exist - please contact your webhost and ask them to install the GD library');
		}

		if (function_exists ('imagefilter') && defined ('IMG_FILTER_NEGATE')) {
			$imageFilters = array (
				1 => array (IMG_FILTER_NEGATE, 0),
				2 => array (IMG_FILTER_GRAYSCALE, 0),
				3 => array (IMG_FILTER_BRIGHTNESS, 1),
				4 => array (IMG_FILTER_CONTRAST, 1),
				5 => array (IMG_FILTER_COLORIZE, 4),
				6 => array (IMG_FILTER_EDGEDETECT, 0),
				7 => array (IMG_FILTER_EMBOSS, 0),
				8 => array (IMG_FILTER_GAUSSIAN_BLUR, 0),
				9 => array (IMG_FILTER_SELECTIVE_BLUR, 0),
				10 => array (IMG_FILTER_MEAN_REMOVAL, 0),
				11 => array (IMG_FILTER_SMOOTH, 0),
			);
		}

		// get standard input properties
		$new_width =  isset($this->PreviewType[width]) ? $this->PreviewType[width] : (int) abs ($this->param('w', 0));
		$new_height = isset($this->PreviewType[height]) ? $this->PreviewType[height] : (int) abs ($this->param('h', 0));
		$zoom_crop = isset($this->PreviewType[zoom_crop]) ? $this->PreviewType[zoom_crop] : (int) $this->param('zc', DEFAULT_ZC);
		$quality = isset($this->PreviewType[quality]) ? $this->PreviewType[quality] : (int) abs ($this->param('q', DEFAULT_Q));
		$align = $this->cropTop ? 't' : $this->param('a', 'c');
		$filters = $this->param('f', '');
		$sharpen = (bool) $this->param('s', 0);
		$canvas_color = $this->param('cc', DEFAULT_CC);
		$trim = isset($this->PreviewType['trim']) ? $this->PreviewType['trim'] : (int) $this->param('tr', DEFAULT_TRIM);

		// set default width and height if neither are set already
		if ($new_width == 0 && $new_height == 0) {
		    $new_width = 100;
		    $new_height = 100;
		}

		// ensure size limits can not be abused
		$new_width = min ($new_width, MAX_WIDTH);
		$new_height = min ($new_height, MAX_HEIGHT);

		// set memory limit to be able to have enough space to resize larger images
		$this->setMemoryLimit();
		if (strlen ($canvas_color) < 6) {
			$canvas_color = 'ffffff';
		}
		// open the existing image
		$image = $this->openImage ($mimeType, $localImage);
		if($trim){
		    $this->imagetrim($image, $canvas_color);
		}
		if ($image === false) {
			return $this->error('Unable to open image.');
		}

		// Get original width and height
		$width = imagesx ($image);
		$height = imagesy ($image);
		$origin_x = 0;
		$origin_y = 0;

		// generate new w/h if not provided
		if ($new_width && !$new_height) {
			$new_height = floor ($height * ($new_width / $width));
		} else if ($new_height && !$new_width) {
			$new_width = floor ($width * ($new_height / $height));
		}
		// scale down and add borders
		if ($zoom_crop == 3 || $zoom_crop == 5) {
		    $zoom = false;
		    if($zoom_crop == 5){
			if($width > $new_width || $height > $new_height){
			    $zoom = true;
			} else {
			    $new_width = $width;
			    $new_height = $height;
			}
			
		    }
		    if($zoom_crop == 3 || $zoom){
			$final_height = $height * ($new_width / $width);

			if ($final_height > $new_height) {
				$new_width = $width * ($new_height / $height);
			} else {
				$new_height = $final_height;
			}
		    }
		}

		// create a new true color image
		$canvas = imagecreatetruecolor ($new_width, $new_height);
		imagealphablending ($canvas, false);

		$canvas_color_R = hexdec (substr ($canvas_color, 0, 2));
		$canvas_color_G = hexdec (substr ($canvas_color, 2, 2));
		$canvas_color_B = hexdec (substr ($canvas_color, 2, 2));

		// Create a new transparent color for image
		$color = imagecolorallocatealpha ($canvas, $canvas_color_R, $canvas_color_G, $canvas_color_B, 127);

		// Completely fill the background of the new image with allocated color.
		imagefill ($canvas, 0, 0, $color);

		// scale down and add borders
		if ($zoom_crop == 2) {

			$final_height = $height * ($new_width / $width);

			if ($final_height > $new_height) {

				$origin_x = $new_width / 2;
				$new_width = $width * ($new_height / $height);
				$origin_x = round ($origin_x - ($new_width / 2));

			} else {

				$origin_y = $new_height / 2;
				$new_height = $final_height;
				$origin_y = round ($origin_y - ($new_height / 2));

			}

		}
                

		// Restore transparency blending
		imagesavealpha ($canvas, true);

		if ($zoom_crop > 0) {

                    if($zoom_crop == 4){
                            imagecopyresampled($canvas, $image, $new_width/2-$width/2, $new_height/2-$height/2, 0, 0, $width, $height, $width, $height);
                        } else {
                            $src_x = $src_y = 0;
                            $src_w = $width;
                            $src_h = $height;

                            $cmp_x = $width / $new_width;
                            $cmp_y = $height / $new_height;

                            // calculate x or y coordinate and width or height of source
                            if ($cmp_x > $cmp_y) {

				$src_w = round ($width / $cmp_x * $cmp_y);
				$src_x = round (($width - ($width / $cmp_x * $cmp_y)) / 2);

                            } else if ($cmp_y > $cmp_x) {

				$src_h = round ($height / $cmp_y * $cmp_x);
				$src_y = round (($height - ($height / $cmp_y * $cmp_x)) / 2);

                            }

                            // positional cropping!
                            if ($align) {
				if (strpos ($align, 't') !== false) {
					$src_y = 0;
				}
				if (strpos ($align, 'b') !== false) {
					$src_y = $height - $src_h;
				}
				if (strpos ($align, 'l') !== false) {
					$src_x = 0;
				}
				if (strpos ($align, 'r') !== false) {
					$src_x = $width - $src_w;
				}
                            }
                            imagecopyresampled ($canvas, $image, $origin_x, $origin_y, $src_x, $src_y, $new_width, $new_height, $src_w, $src_h);
                        }

		} else {

			// copy and resize part of an image with resampling
			imagecopyresampled ($canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

		}

		if ($filters != '' && function_exists ('imagefilter') && defined ('IMG_FILTER_NEGATE')) {
			// apply filters to image
			$filterList = explode ('|', $filters);
			foreach ($filterList as $fl) {

				$filterSettings = explode (',', $fl);
				if (isset ($imageFilters[$filterSettings[0]])) {

					for ($i = 0; $i < 4; $i ++) {
						if (!isset ($filterSettings[$i])) {
							$filterSettings[$i] = null;
						} else {
							$filterSettings[$i] = (int) $filterSettings[$i];
						}
					}

					switch ($imageFilters[$filterSettings[0]][1]) {

						case 1:

							imagefilter ($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1]);
							break;

						case 2:

							imagefilter ($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1], $filterSettings[2]);
							break;

						case 3:

							imagefilter ($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1], $filterSettings[2], $filterSettings[3]);
							break;

						case 4:

							imagefilter ($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1], $filterSettings[2], $filterSettings[3], $filterSettings[4]);
							break;

						default:

							imagefilter ($canvas, $imageFilters[$filterSettings[0]][0]);
							break;

					}
				}
			}
		}

		// sharpen image
		if ($sharpen && function_exists ('imageconvolution')) {

			$sharpenMatrix = array (
					array (-1,-1,-1),
					array (-1,16,-1),
					array (-1,-1,-1),
					);

			$divisor = 8;
			$offset = 0;

			imageconvolution ($canvas, $sharpenMatrix, $divisor, $offset);

		}
		//Straight from Wordpress core code. Reduces filesize by up to 70% for PNG's
		if ( (IMAGETYPE_PNG == $origType || IMAGETYPE_GIF == $origType) && function_exists('imageistruecolor') && !imageistruecolor( $image ) && imagecolortransparent( $image ) > 0 ){
			imagetruecolortopalette( $canvas, false, imagecolorstotal( $image ) );
		}

		$imgType = '';
		if(preg_match('/^image\/(?:jpg|jpeg)$/i', $mimeType)){ 
			$imgType = 'jpg';
			imagejpeg($canvas, $this->cachefile, $quality); 
		} else if(preg_match('/^image\/png$/i', $mimeType)){ 
			$imgType = 'png';
			imagepng($canvas, $this->cachefile, floor($quality * 0.09));
		} else if(preg_match('/^image\/gif$/i', $mimeType)){
			$imgType = 'gif';
			imagegif($canvas, $this->cachefile);
		} else {
			return $this->sanityFail("Could not match mime type after verifying it previously.");
		}
		imagedestroy($canvas);
		imagedestroy($image);
		return true;
	}
	// Trims an image then optionally adds padding around it.
	// $im  = Image link resource
	// $bg  = The background color to trim from the image
	// $pad = Amount of padding to add to the trimmed image
	//  
	protected function imagetrim(&$im, $bg, $pad=null){
	// Calculate padding for each side.
	    $ibg = hexdec($bg);
	    $ibg = imagecolorclosest($im, ($ibg >> 16) & 0xFF, ($ibg >> 8) & 0xFF, $ibg & 0xFF);
	if (isset($pad)){
	    $pp = explode(' ', $pad);
	    if (isset($pp[3])){
		$p = array((int) $pp[0], (int) $pp[1], (int) $pp[2], (int) $pp[3]);
	    }else if (isset($pp[2])){
		$p = array((int) $pp[0], (int) $pp[1], (int) $pp[2], (int) $pp[1]);
	    }else if (isset($pp[1])){
		$p = array((int) $pp[0], (int) $pp[1], (int) $pp[0], (int) $pp[1]);
	    }else{
		$p = array_fill(0, 4, (int) $pp[0]);
	    }
	}else{
	    $p = array_fill(0, 4, 0);
	}
	// Get the image width and height.
	$imw = imagesx($im);
	$imh = imagesy($im);
	// Set the X variables.
	$xmin = $imw;
	$xmax = 0;
	// Start scanning for the edges.
	for ($iy=0; $iy<$imh; $iy++){
	    $first = true;
	    for ($ix=0; $ix<$imw; $ix++){
		$ndx = imagecolorat($im, $ix, $iy);
		if ($ndx != $ibg){
		    if ($xmin > $ix){ $xmin = $ix; }
		    if ($xmax < $ix){ $xmax = $ix; }
		    if (!isset($ymin)){ $ymin = $iy; }
		    $ymax = $iy;
		    if ($first){ $ix = $xmax; $first = false; }
		}
	    }
	}
	// The new width and height of the image. (not including padding)
	$imw = 1+$xmax-$xmin; // Image width in pixels
	$imh = 1+$ymax-$ymin; // Image height in pixels
	// Make another image to place the trimmed version in.
	$im2 = imagecreatetruecolor($imw+$p[1]+$p[3], $imh+$p[0]+$p[2]);
	// Make the background of the new image the same as the background of the old one.
	$bg2 = imagecolorallocate($im2, ($ibg >> 16) & 0xFF, ($ibg >> 8) & 0xFF, $ibg & 0xFF);
	imagefill($im2, 0, 0, $bg2);
	// Copy it over to the new image.
	imagecopy($im2, $im, $p[3], $p[0], $xmin, $ymin, $imw, $imh);
	imagedestroy($im);
	// To finish up, we replace the old image which is referenced.
	$im = $im2;
	}
	protected function calcDocRoot(){
		$docRoot = @$_SERVER['DOCUMENT_ROOT'];
		if(!isset($docRoot)){ 
			$this->debug(3, "DOCUMENT_ROOT is not set. This is probably windows. Starting search 1.");
			if(isset($_SERVER['SCRIPT_FILENAME'])){
				$docRoot = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF'])));
				$this->debug(3, "Generated docRoot using SCRIPT_FILENAME and PHP_SELF as: $docRoot");
			} 
		}
		if(!isset($docRoot)){ 
			$this->debug(3, "DOCUMENT_ROOT still is not set. Starting search 2.");
			if(isset($_SERVER['PATH_TRANSLATED'])){
				$docRoot = str_replace( '\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0-strlen($_SERVER['PHP_SELF'])));
				$this->debug(3, "Generated docRoot using PATH_TRANSLATED and PHP_SELF as: $docRoot");
			} 
		}
		if($docRoot && $_SERVER['DOCUMENT_ROOT'] != '/'){ $docRoot = preg_replace('/\/$/', '', $docRoot); }
		$this->debug(3, "Doc root is: " . $docRoot);
		$this->docRoot = $docRoot;

	}
	protected function getLocalImagePath($src){
		$src = preg_replace('/^\//', '', $src); //strip off the leading '/'
                
		$realDocRoot = realpath($this->docRoot);
		if(! $this->docRoot){
			$this->debug(3, "We have no document root set, so as a last resort, lets check if the image is in the current dir and serve that.");
			//We don't support serving images outside the current dir if we don't have a doc root for security reasons.
			$file = preg_replace('/^.*?([^\/\\\\]+)$/', '$1', $src); //strip off any path info and just leave the filename.
			if(is_file($file)){
				return realpath($file);
			}
			return $this->error("Could not find your website document root and the file specified doesn't exist in timthumbs directory. We don't support serving files outside timthumb's directory without a document root for security reasons.");
		} //Do not go past this point without docRoot set

		//Try src under docRoot
		if(file_exists ($this->docRoot . '/' . $src)) {
			$this->debug(3, "Found file as " . $this->docRoot . '/' . $src);
			$real = realpath($this->docRoot . '/' . $src);
			if(stripos($real, $realDocRoot) === 0){
				return $real;
			} else {
				$this->debug(1, "Security block: The file specified occurs outside the document root.");
				//allow search to continue
			}
		}
		//Check absolute paths and then verify the real path is under doc root
		$absolute = realpath('/' . $src);
		if($absolute && file_exists($absolute)){ //realpath does file_exists check, so can probably skip the exists check here
			$this->debug(3, "Found absolute path: $absolute");
			if(! $this->docRoot){ $this->sanityFail("docRoot not set when checking absolute path."); }
			if(stripos($absolute, $realDocRoot) === 0){
				return $absolute;
			} else {
				$this->debug(1, "Security block: The file specified occurs outside the document root.");
				//and continue search
			}
		}
		
		$base = $this->docRoot;
		
		// account for Windows directory structure
		if (strstr($_SERVER['SCRIPT_FILENAME'],':')) {
			$sub_directories = explode('\\', str_replace($this->docRoot, '', $_SERVER['SCRIPT_FILENAME']));
		} else {
			$sub_directories = explode('/', str_replace($this->docRoot, '', $_SERVER['SCRIPT_FILENAME']));
		}
		foreach ($sub_directories as $sub){
			$base .= $sub . '/';
			$this->debug(3, "Trying file as: " . $base . $src);
			if(file_exists($base . $src)){
				$this->debug(3, "Found file as: " . $base . $src);
				$real = realpath($base . $src);
				if(stripos($real, $realDocRoot) === 0){ 
					return $real;
				} else {
					$this->debug(1, "Security block: The file specified occurs outside the document root.");
					//And continue search
				}
			}
		}
		
		$img = $this->docRoot . '/' . $src;                
		if (file_exists($img)) {
			return $img;
		}
                
                
		return false;
	}
	protected function serveCacheFile(){
            
		$this->debug(3, "Serving {$this->cachefile}");
		if(! is_file($this->cachefile)){
			$this->error("serveCacheFile called in timthumb but we couldn't find the cached file.");
			return false;
		}
                if($_GET['notview']){
                    return true;;
                }
                $fdata = getimagesize($this->cachefile);
		$imageDataSize = filesize($this->cachefile);
		$this->sendImageHeaders($fdata['mime'], $imageDataSize);
		if($bytesSent > 0){
			return true;
		}
		$content = file_get_contents ($this->cachefile);
		if ($content != FALSE) {
			//$content = substr($content, strlen($this->filePrependSecurityBlock) + 6);
			echo $content;
			$this->debug(3, "Served using file_get_contents and echo");
			return true;
		} else {
			$this->error("Cache file could not be loaded.");
			return false;
		}
	}
	protected function sendImageHeaders($mimeType, $dataSize){
		if(! preg_match('/^image\//i', $mimeType)){
			$mimeType = 'image/' . $mimeType;
		}
		if(strtolower($mimeType) == 'image/jpg'){
			$mimeType = 'image/jpeg';
		}
		$gmdate_expires = gmdate ('D, d M Y H:i:s', strtotime ('now +10 days')) . ' GMT';
		$gmdate_modified = gmdate ('D, d M Y H:i:s') . ' GMT';
		// send content headers then display image
		header ('Content-Type: ' . $mimeType);
		header ('Accept-Ranges: none'); //Changed this because we don't accept range requests
		header ('Last-Modified: ' . $gmdate_modified);
		header ('Content-Length: ' . $dataSize);
		if(BROWSER_CACHE_DISABLE){
			$this->debug(3, "Browser cache is disabled so setting non-caching headers.");
			header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
			header("Pragma: no-cache");
			header('Expires: ' . gmdate ('D, d M Y H:i:s', time()));
		} else {
			$this->debug(3, "Browser caching is enabled");
			header('Cache-Control: max-age=' . BROWSER_CACHE_MAX_AGE . ', must-revalidate');
			header('Expires: ' . $gmdate_expires);
		}
		return true;
	}
	protected function param($property, $default = ''){
		if (isset ($_GET[$property])) {
			return $_GET[$property];
		} else {
			return $default;
		}
	}
	protected function openImage($mimeType, $src){
		switch ($mimeType) {
			case 'image/jpg': //This isn't a valid mime type so we should probably remove it
			case 'image/jpeg':
				$image = imagecreatefromjpeg ($src);
				break;

			case 'image/png':
				$image = imagecreatefrompng ($src);
				break;

			case 'image/gif':
				$image = imagecreatefromgif ($src);
				break;
		}

		return $image;
	}
	protected function getIP(){
		$rem = @$_SERVER["REMOTE_ADDR"];
		$ff = @$_SERVER["HTTP_X_FORWARDED_FOR"];
		$ci = @$_SERVER["HTTP_CLIENT_IP"];
		if(preg_match('/^(?:192\.168|172\.16|10\.|127\.)/', $rem)){ 
			if($ff){ return $ff; }
			if($ci){ return $ci; }
			return $rem;
		} else {
			if($rem){ return $rem; }
			if($ff){ return $ff; }
			if($ci){ return $ci; }
			return "UNKNOWN";
		}
	}
	protected function debug($level, $msg){
		if(DEBUG_ON && $level <= DEBUG_LEVEL){
			$execTime = sprintf('%.6f', microtime(true) - $this->startTime);
			$tick = sprintf('%.6f', 0);
			if($this->lastBenchTime > 0){
				$tick = sprintf('%.6f', microtime(true) - $this->lastBenchTime);
			}
			$this->lastBenchTime = microtime(true);
			error_log("TimThumb Debug line " . __LINE__ . " [$execTime : $tick]: $msg");
		}
	}
	protected function sanityFail($msg){
		return $this->error("There is a problem in the timthumb code. Message: Please report this error at <a href='http://code.google.com/p/timthumb/issues/list'>timthumb's bug tracking page</a>: $msg");
	}
	protected function setMemoryLimit(){
		$inimem = ini_get('memory_limit');
		$inibytes = timthumb::returnBytes($inimem);
		$ourbytes = timthumb::returnBytes(MEMORY_LIMIT);
		if($inibytes < $ourbytes){
			ini_set ('memory_limit', MEMORY_LIMIT);
			$this->debug(3, "Increased memory from $inimem to " . MEMORY_LIMIT);
		} else {
			$this->debug(3, "Not adjusting memory size because the current setting is " . $inimem . " and our size of " . MEMORY_LIMIT . " is smaller.");
		}
	}
	protected static function returnBytes($size_str){
		switch (substr ($size_str, -1))
		{
			case 'M': case 'm': return (int)$size_str * 1048576;
			case 'K': case 'k': return (int)$size_str * 1024;
			case 'G': case 'g': return (int)$size_str * 1073741824;
			default: return $size_str;
		}
	}
	protected function serveImg($file){
		$s = getimagesize($file);
		if(! ($s && $s['mime'])){
			return false;
		}
		header ('Content-Type: ' . $s['mime']);
		header ('Content-Length: ' . filesize($file) );
		header ('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header ("Pragma: no-cache");
		$bytes = @readfile($file);
		if($bytes > 0){
			return true;
		}
		$content = @file_get_contents ($file);
		if ($content != FALSE){
			echo $content;
			return true;
		}
		return false;

	}
	protected function set404(){
		$this->is404 = true;
	}
	protected function is404(){
		return $this->is404;
	}
}