<?php
include'../../../include/setup.php';
ob_start();
$Core = new Core(Core::UTIL | Core::CONNECTED);
$str = '';
$query = urldecode($_REQUEST['q']);
if ($query){
	if(!$prod = $Core->getClass('Util')->memcacheGet('cacheForFind')){
		if($C = $Core->getClass(array(Core::CLASS_NAME => 'Product', Core::MODULE => 'catalog'))){
			$prod = $C->cacheForFind();
		}
	}
	if($prod){
		mb_regex_encoding('UTF-8');
		$qArr = mb_split('[^А-яA-z0-9\-]', $query);
		foreach($qArr as $k => $v){
			if(!$v){
				unset($qArr[$k]);
			}
		}
		$fArr = array();
		foreach ($prod as $k => $v){
			$match = 0;
			foreach ($qArr as $v1){
				if (mb_stripos($v['name'], $v1, 0, 'UTF-8') !== false) {
					$match++; 
				}
			}
			if ($match){
				/*
				 * id @@@ name @@@ p_alias @@@ c_alias @@@ img_src @@@ images_path
				*/		
				$fArr[] = array('match' => $match, 'str' => $v['id'] .'@@@'.$v['name'].'@@@'.$v['alias'].'@@@'.$v['c_alias'].'@@@'.$v['img_src'].'@@@/files/product@@@'.$v['price']);
			}
		}
		usort($fArr, 'sort_prod');
		$str = '';
		foreach ($fArr as $v){
			$str .= $v['str']."\n";
		}
	}
}
$errors = ob_get_clean();
echo json_encode(array('str' => $str, 'errors' => $errors));

function sort_prod($a, $b){
    if($a['match'] < $b['match']){
        return 1;
    } elseif($a['match'] > $b['match']){
        return -1;
    } else {
        return 0;
    }
}
?>