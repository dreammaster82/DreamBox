<?php
if($_SERVER['REQUEST_URI']){
    preg_match('/type_([a-z0-9_]+)\/(.*)/i', $_SERVER['REQUEST_URI'], $request_arr);
    if(isset($request_arr[1])){
        $_GET['type'] = $request_arr[1];
        if(isset($request_arr[2])){
            $_GET['src'] = $request_arr[2];
			if( file_exists(dirname(__FILE__) . '/previewtype.php'))	require_once('previewtype.php');
			if($PreviewTypes[$_GET['type']]['type']){
				include './imagePreview.php';
			} else {
				include_once './preview.php';
			}
        }
    }
    
}
?>
