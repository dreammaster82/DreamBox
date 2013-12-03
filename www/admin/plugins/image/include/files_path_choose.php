<?php
    $mime_types = array(
	'image/gif'=>'gif',	
	'image/jpeg'=>'jpg',
	'image/pjpeg'=>'jpg',
	'image/png'=>'png'
    );
<<<<<<< HEAD
	$OUT['content'] .= '<script>
=======
	$ret = '<script>
>>>>>>> 41a0a7e... image plugin
			function windowExit()
			{
				alert(\'Сюда так нельзя!\');
				window.location = \'/admin/\';
				window.close();
			}

			if (!window.opener) windowExit();
			</script>';

	if(!$USER)
	{
<<<<<<< HEAD
		$OUT['content'] .= '<script>windowExit();</script>';
=======
		$ret .= '<script>windowExit();</script>';
>>>>>>> 41a0a7e... image plugin
	}
	$path = '';
	$patharr = array();
	if($_REQUEST['path']){
	    $path = $_REQUEST['path'];
	    $patharr = explode('/', $path);
	    $arr = array();
	    foreach ($patharr as $value){
		if($value){
		    $arr[] = $value;
		}
	    }
	    $patharr = $arr;
	} elseif(is_file('../../../'.$_REQUEST['file'])){
	    $path = str_replace($imageCFG['filesroot'], '', $_REQUEST['file']);
	    $patharr = explode('/', $path);
	    $file = array_pop($patharr);
	    $path = str_replace($file, '', $path);
	    $arr = array();
	    foreach ($patharr as $value){
		if($value){
		    $arr[] = $value;
		}
	    }
	    $patharr = $arr;
	}
	if($path && substr($path, -1, 1) != '/'){
	    $path .= '/';
	}
	$curdirLink = ' / <a href="?action=files">'.substr($imageCFG['filesroot'], 0, -1).'</a> / ';
	$rootHref = '?action=files';
	$curHref = $path ? $rootHref.'&path='.$path : $rootHref;
	$dir = $imageCFG['real_filesroot'].$path;
	if(is_dir($dir))
	{
		$this_dir = opendir($dir);

		while($file = readdir($this_dir))
		{
			if( $file != "." && $file != ".." && $file[0] != "." ) 
			{
				$list[] = $file;
			}
		}
		closedir($this_dir);
	}
	$curPath = '';
	$parPath = '';
	$cnt = sizeof($patharr) - 1;
	foreach ($patharr as $key => $value){
	    $curPath .= $value.'/';
	    $curdirLink .= '<a href="'.$rootHref.'&path='.$curPath.'">'.$value.'</a> / ';
	    if($key < $cnt){
		$parPath .= $value.'/';
	    }
	}
	if($parPath){
	    $parentLink = $rootHref.'&path='.$parPath;
	} elseif($patharr){
	    $parentLink = $rootHref;
	}
	

<<<<<<< HEAD
	$OUT['content'] .= '<script>
=======
	$ret .= '<script>
>>>>>>> 41a0a7e... image plugin
				function OpenerFilePathInsert(path, width, height)
				{
					window.opener.setImage(path, width, height);
					self.close(); 
				}
				</script>	

				<table border="0" width="100%" cellpadding="2" cellspacing="0">
				<tr>
					<td align="center" class=z>менеджер файлов</td>
				</tr>
				</table>';

	if($USER)
	{
<<<<<<< HEAD
		$OUT['content'] .= '<table border="0" width="100%" cellpadding="2" cellspacing="0" style="margin-top:1px;">
=======
		$ret .= '<table border="0" width="100%" cellpadding="2" cellspacing="0" style="margin-top:1px;">
>>>>>>> 41a0a7e... image plugin
				<tr height="22" class=x>
					<td><b>'.$curdirLink.'</b></td>
				</tr>
				</table>';
	}

<<<<<<< HEAD
	$OUT['content'] .= '<table border="0" width="100%" cellpadding="4" cellspacing="0" style="margin-top:1px;">
=======
	$ret .= '<table border="0" width="100%" cellpadding="4" cellspacing="0" style="margin-top:1px;">
>>>>>>> 41a0a7e... image plugin
				<tr class=z>
					<td class=z width="16"></td>
					<td class=z width="100%">имя файла</td>
					<td align="center" class=z nowrap>инфо</td>
					<td align="center" class=z nowrap>просмотр</td>
					<td align="center" class=z nowrap>вставить</td>
				</tr>';
    $k=0;
    $class= ($k%2)? 'x' : 'y';
		if($parentLink) 
		{
<<<<<<< HEAD
			$OUT['content'] .= '<tr class="'.$class.'">
=======
			$ret .= '<tr class="'.$class.'">
>>>>>>> 41a0a7e... image plugin
					<td><a href="'.$parentLink.'"><img src="/admin/images/folder.gif" width="16" height="15" border="0"></a></td>
					<td><a href="'.$parentLink.'"><b>..</b></a></td>
					<td colspan="3"></td>
				</tr>';
		}
	if($list){
		foreach($list as $cur)
		{
			if(!$cur) continue;
			$k++; $class= ($k%2)? 'x' : 'y';
			if(is_dir($dir.$cur))	
			{
				$path_str = $path ? $path.$cur.'/' : $cur.'/';
				$href = $rootHref.'&path='.$path_str;

<<<<<<< HEAD
				$OUT['content'] .= '<tr class="'.$class.'">
=======
				$ret .= '<tr class="'.$class.'">
>>>>>>> 41a0a7e... image plugin
					<td nowrap valign="middle"><a href="'.$href.'"><img src="/admin/images/folder.gif" width="16" height="15" border="0"></a></td>
					<td valign="middle"><a href="'.$href.'"><b>'.$cur.'</b></a></td>
					<td></td>
					<td></td>
					<td></td>
				</tr>';
			}
		}
		$fi = finfo_open(FILEINFO_MIME_TYPE);
		foreach($list as $cur)
		{
			if(!$cur) continue;
			$k++; $class= ($k%2)? 'x' : 'y';
			if(is_file($dir.$cur))
			{	
				if(!$mime_types[finfo_file($fi, $dir.$cur)]){
				    continue;
				}
				$filesize = filesize($dir.$cur);
				$filedate = date('d.m.y', filemtime($dir.$cur));

				$size = getimagesize($dir.$cur); 

<<<<<<< HEAD
				$OUT[content] .= '<tr class="'.$class.'">
=======
				$ret .= '<tr class="'.$class.'">
>>>>>>> 41a0a7e... image plugin
					<td valign="middle"><img src="/admin/images/file.gif" width="16" height="15" border="0"></td>
					<td valign="middle"><a href="'.$imageCFG['real_filesroot'].$path.$cur.'" title="просмотр" target="_blank"><b>'.$cur.'</b></a></td>
					<td>'.$filesize.' kB <br> '.$filedate.' <br> '.$size[0].' x '.$size[1].' <br>'.$size['mime'].'<td><img src="/preview/preview.php?src=/'.$imageCFG['filesroot'].$path.$cur.'&zc=2&w='.$imageCFG['thumbnail_width'].'" border="0"></td>
					<td align="right"><input type="button" value=">>" style="width: 20px" class="buttons" onclick="OpenerFilePathInsert(\'/'.$imageCFG['filesroot'].$path.$cur.'\', '.$size[0].', '.$size[1].');"/></td>
				</tr>';	
		
			}
		}
	} else {
<<<<<<< HEAD
		$OUT['content'] .= '<tr class="x">
=======
		$ret .= '<tr class="x">
>>>>>>> 41a0a7e... image plugin
					<td colspan="5" align="center"><b>Категория пуста!</b></td>
				</tr>';
		return;
	}
<<<<<<< HEAD
	$OUT[content] .= '</table>';
=======
	$ret .= '</table>';
>>>>>>> 41a0a7e... image plugin
?>