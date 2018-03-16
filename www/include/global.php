<?php
if(!$it = $Core->getClass('Memcache')->get('getContentItems')){
	if(is_object($C = $Core->getClass(['Content', 'content']))){
		$it = $C->getContentItems();
	}
}
$out['nav'] = [];
if($it){
    $cur = (int)$_REQUEST['req'];
    $arr = [];
    $arr1 = [];
    foreach ($it as $k => $v){
        if($v[3]){
            if(!$v[1]) $arr1[$v[0]] = [$Core->getClass('Util')->getAlias($v[0], 'content'), $v[2], $v[0], $cur == $v[0]];
            else {
                $arr[$v[1]][$v[0]] = [$Core->getClass('Util')->getAlias($v[1], 'content') . '/' . $Core->getClass('Util')->getAlias($v[0], 'content'), $v[2], $v[0], $cur == $v[0]];
                if($cur == $v[0]) $cur = (int)$v[1];
            }
        }
    }
    unset($it);
    if($arr1){
        foreach ($arr1 as $k => $v){
            if($arr[$k]){
                if($cur == $k) $arr1[$k][3] = true;
                $arr1[$k][4] = $arr[$k];
            }
        }
        unset($arr);
        $out['nav'] = $arr1;
    }
}

$bodyClass = [];
if((int)$_REQUEST['page']) array_push($bodyClass, 'paged');
array_push($bodyClass, $out['active'] == 'index' ? 'main-page' : $_REQUEST['module']);
$out['body-class'] = join(' ', $bodyClass);
?>