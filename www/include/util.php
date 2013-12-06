<?php
namespace core{
	class Util{
		function sortArray() {
			$argv = func_get_args();
			$arguments = $argv[1];
			$array = $argv[0];
			$code = '';
			for ($c = 0; $c < count($arguments); $c += 2) {
				if (in_array($arguments[$c + 1], array("ASC", "DESC"))) {
					$code .= 'if ($a["'.$arguments[$c].'"] != $b["'.$arguments[$c].'"]) {';
					if ($arguments[$c + 1] == "ASC") {
						$code .= 'return ($a["'.$arguments[$c].'"] < $b["'.$arguments[$c].'"] ? -1 : 1); }';
					}
					else {
						$code .= 'return ($a["'.$arguments[$c].'"] < $b["'.$arguments[$c].'"] ? 1 : -1); }';
					}
				}
			}
			$code .= 'return 0;';
			$compare = create_function('$a, $b', $code);
			usort($array, $compare);
			return $array;
		}

		function subString($string, $length=1, $adding_string='...', $encode = 'UTF-8'){
			if(mb_strlen($string, $encode) <= $length){
				return $string;
			} else {
				$string = htmlspecialchars_decode($string);
				$string = mb_substr($string, 0, $length, $encode).$adding_string;
				return htmlspecialchars($string);
			}
		}

		function checkText($text, $length)
		{
			$text = substr($text, 0, $length);
			return preg_replace("/[^\w\x7F-\xFF\s]/", " ", $text);
		}

		function error($errors){
			$out = array();
			Header("HTTP/1.0 404 Not Found");
			if(is_array($errors)){
				foreach ($errors as $k => $v){
					$out['errors'] .= $k.': '.(is_array($v) ? implode(', ', $v) : $v).'; ';
				}
			} elseif(is_string($errors)) {
				$out['errors'] = $errors;
			}
			ob_start();
			include CLIENT_PATH.'/data/error.html';
			return ob_get_clean();
		}

		function mail($from, $from_text, $emailto, $subj, $body, $type="text/html", $file=''){
			if (!$from || !$emailto) return;

			$subj = base64_encode($subj);
			$subj = '=?UTF-8?B?' . $subj .'?=';

			$from_text = base64_encode($from_text);
			$from_text = '=?UTF-8?B?' . $from_text . '?=';

			$head = "";
			$head .= "MIME-Version: 1.0\r\n";
			$head .= "Content-Type: {$type}; charset=utf-8\r\n"; 
			//$head .= "Return-Path: {$CONFIG[email_alert]}\r\n";
			$head .= "Reply-To: $from\r\n";
			$head .= "From: $from_text <{$from}>\r\n";
			$head .= "X-Mailer: PHP mailer\r\n";
			$head .= "X-Sender: $from\r\n";

			$head .= "Content-Transfer-Encoding: 8bit\n";
			mail($emailto, $subj, $body, $head, '-f '.$this->Core->globalConfig['email']['email_system']);
		}

		function getmicrotime(){
			list($usec, $sec) = explode(' ', microtime());
			return ((float)$usec + (float)$sec);
		}

		function getrealpath($path){
			if(realpath($path)){
				return str_replace('\\', '/', realpath($path));
			} 
			if(realpath(CLIENT_PATH.$path)){
				return str_replace('\\', '/', realpath(CLIENT_PATH.$path));
			}
			$real = '';
			$real_arr = array();
			if(stripos($path, '../') !== false){
				$real = str_replace('\\', '/', realpath(''));
			} else {
				$real = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
				if(stripos($path, $real) !== false){
					$real = '';
				}
			}
			$add = '';
			if($real){
				if(stripos($real, '/') === 0){
					$add = '/';
				}
				$real_arr = explode('/', $real);
			} else {
				if(stripos($path, '/') === 0){
					$add = '/';
				}
			}
			$path_arr = explode('/', $path);
			foreach ($path_arr as $value){
				if($value == '..'){
					array_pop($real_arr);
				} elseif($value != '') {
					$real_arr[] = $value;
				}
			}
			return $add.implode('/', $real_arr);
		}

		function untag($string, $encode){
			$tags = array();
			preg_match_all('/(<.*?>)/i', $string, $tags);
			if($tags){
				$pos = 0;
				foreach ($tags[0] as $key => $value){
					$pos = mb_stripos($string, $value, 0, $encode);
					$len = mb_strlen($value, $encode);
					$string = mb_substr($string, 0, $pos, $encode).mb_substr($string, $pos + $len, mb_strlen($string, $encode), $encode);
					if(stripos($value, '</') !== false){
						$type = 1;
					} elseif(stripos($value, '/>')){
						$type = -1;
					} else {
						$type = 0;
					}
					$tags[0][$key] = array($value, $pos, $type);
				}
			}
			return array('string' => $string, 'tags' => $tags);
		}

		function tag($data, $encode){
			if($data['tags']){
				$string = $data['string'];
				$length = mb_strlen($string, $encode);
				$pos = 0;
				$prev = 0;
				foreach ($data['tags'][0] as $key => $value){
					$l1 = mb_strlen($string, $encode);
					if($value[1] < $length){                       
						$pos = $value[1] + $prev;
						$string = mb_substr($string, 0, $pos, $encode).$value[0].mb_substr($string, $pos, $l1, $encode);
						$prev += mb_strlen($value[0], $encode);
					} else {
						if($value[2] == 1){
							$string .= $value[0];
						}
						break;
					}
				}
			}
			return $string;
		}

		function checkString($string, $encode, $length, $adding_string = '...'){
			$data = $this->untag($string, $encode);
			$string = subString($data['string'], $encode, $length, $adding_string);
			$string = $this->tag(array('string' => $string, 'tags' => $data['tags']), $encode);
			return $string;
		}

		function getImagesArray($path){
			$path = $this->getrealpath($path);
			$files = array();
			if(file_exists($path)){
				try{
					$dir = new \RecursiveDirectoryIterator($path, \FilesystemIterator::NEW_CURRENT_AND_KEY | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
					foreach ($dir as $v){
						if($v->isFile()){
							$name = $v->getFilename();
							$t = explode('_', $name);
							if(is_numeric($t[0])){
								$files[$t[0]] = $name;
							}
							ksort($files, SORT_NUMERIC);
						}
					}
				} catch(\UnexpectedValueException $e){
					echo $e->getMessage();
				}
			}
			return $files;
		}

		function memcacheSet($ind, $data, $class = 'all', $expire = 0){
			if($m = $this->Core->getClass('MemcacheCore')){
				$m->set($ind, $data, $class, $expire);
			}
		}

		function memcacheGet($ind){
			if($m = $this->Core->getClass('MemcacheCore')){
				$m->get($ind);
			}
		}

		function memcacheDelete($ind, $class = 'all'){
			if($m = $this->Core->getClass('MemcacheCore')){
				$m->delete($ind, $class);
			}
		}

		function memcacheFlush($class = 'all'){
			if($m = $this->Core->getClass('MemcacheCore')){
				$m->flush($class);
			}
		}
	}
}
?>