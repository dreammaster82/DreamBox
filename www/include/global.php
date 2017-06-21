<?php
if(!$it = $Core->getClass('Memcache')->get('getContentItems')){
	if(is_object($C = $Core->getClass(['Content', 'content']))){
		$it = $C->getContentItems();
	}
}
if($it){
	$arr = [];
	$arr1 = [];
	foreach ($it as $k => $v){
		$arr[$v[1]][] = [$v[2], $v[3]];
		if($v[4]){
			$arr1[$v[0]] = [$v[2], $v[3]];
		}
	}
	unset($items);
	if($arr1){
		foreach ($arr1 as $k => $v){
			if($arr[$k]){
				$arr1[$k][2] = $arr[$k];
			}
		}
		unset($arr);
		$out['nav'] = $arr1;
	}
}
?>