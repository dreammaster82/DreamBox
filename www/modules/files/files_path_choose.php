<?php

		if($_REQUEST[path])
		{
			if($_REQUEST[path] == "/") $_REQUEST[path] = "";
			$path = $_SESSION[files_path] = $_REQUEST[path];
		}
		elseif($_SESSION[files_path] && !$_REQUEST[goback])
		{
			$path = $_SESSION[files_path];
		}


		$path = str_replace(".","",$path);
		$dir = $real_filesroot . "/";
		if($path) $dir .= $path . "/";
		//$dir = str_replace("//","/",$dir);
		$wwwpath = "/" . $filesroot . "/";
		if($path) $wwwpath .= $path . "/";
//echo $dir;
		
		if(is_dir($dir))
		{
			$this_dir = opendir($dir);
			while($file = readdir($this_dir))
			{
				if( $file != "." && $file != ".." && $file[0] != "." ) $list[] = $file;
			}
			closedir($this_dir);
		}

		$curdirArray = explode("/", $path);
		$cnt = count($curdirArray);
		$curdirLink = " / <a href='?action=files&goback=1&window=1'>".$filesroot."</a> / ";
		if($_REQUEST[path]) $parentLink = "?action=files&goback=1&window=1";
		$curdirText = "";
		$i = 0;
		foreach($curdirArray as $cur)
		{
			if($i) $curdirText .= "/" . $cur; else $curdirText .= $cur;
			if($cur) $curdirLink .= "<a href='?action=files&path={$curdirText}&window=1'>{$cur}</a> / ";
			if($i == ($cnt-2)) 
			{
				if(!$curdirText) $parentLink = "?action=files&goback=1&window=1"; else $parentLink = "?action=files&path={$curdirText}&goback=1&window=1";
			}
			$i++;
		}
		
		$k=0; $class= ($k%2)? 'x' : 'y';

$OUT[content] .= "
<script>
function OpenerFilePathInsert(path, type, params)
{
	display = '[download]';
	if (path) display += path;
	if (type) display += '|' + type;
	if (params) display += '|' + params;
	display += '[/download]';
//alert(display );
	window.opener.AddText(display);
	self.close(); 
}
</script>	

	<table border=\"0\" width='100%' cellpadding=2 cellspacing=0>
	<tr><td align='center' class=z>менеджер файлов</td></tr>
	<tr height='22' class=x>
		<td><b>$curdirLink</b></td>
	</tr>
	</table>

	<table border='0' width='100%' cellpadding=2 cellspacing=0>
	<tr class=z>
		<td class=z width='16'></td>
		<td class=z>Имя файла</td>
		<td class=z>Комментарий</td>
		<td class=z>Размер</td>
		<td class=z>Дата</td>
		<td class=z>Построчно</td>
		<td class=z>Загрузить</td>
	</tr>";

if($parentLink) 
{
	$OUT[content] .= "
	<tr class=\"$class\">
		<td valign='middle'><a href='$parentLink'><img src='/admin/images/folder.gif' width='16' height='15' border='0'></a></td>
		<td valign='middle'><a href='$parentLink'><b>..</b></a></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
	</tr>";
}

if($list)
{
	foreach($list as $cur)
	{
		if(!$cur) continue;
		$k++; $class= ($k%2)? 'x' : 'y';
		if(is_dir($dir.$cur))	
		{
			$wwwpath = "/" . $filesroot . "/";
			//if($curdir) $wwwpath .= $curdir . "/";
			$res = db::queryOne("select * from files_children where file_name='$wwwpath'");
			$comments = $res[comments];

			$wwwpath .= $cur . "/";

			$wwwpath = "/" . $filesroot . "/";
			//if($curdir) $wwwpath .= $curdir . "/";
			$wwwpath .= $cur . "/";

			$path_str = $path ? $path . "/" . $cur : $cur;
			$href = "?action=files&path={$path_str}&window=1";

			$OUT[content] .= "
			<tr class=\"$class\">
				<td nowrap valign='middle'><a href='$href'><img src='/admin/images/folder.gif' width='16' height='15' border='0'></a></td>
				<td valign='middle'><b><a href='$href'>$cur</a></b></td>
				<td>$comments</td>
				<td></td>
				<td></td>
				<td><a href='#' title='вставить строки' onclick=\"OpenerFilePathInsert('$cur', 'folder', '')\">вставить</a></td>
				<td></td>
			</tr>";
		}
	}

	foreach($list as $cur)
	{
		if(!$cur) continue;
		$k++; $class= ($k%2)? 'x' : 'y';
		if(is_file($dir.$cur))
		{
			$nameext = explode(".", $cur);
			$ext = $nameext[1];
			$filesize = filesize($dir.$cur);
			$filedate = date('d.m.y', filemtime($dir.$cur));
			$size = @getimagesize($dir.$cur);
//			if($size[mime]) $info = "$size[mime] $size[0] x $size[1]"; else 
			{	
				$wwwpath = "/" . $filesroot . "/";
				if($path) $wwwpath .= $path . "/";
				$SQL = "select * from files_children where file_name='{$wwwpath}{$cur}'";
				//$wwwpath .= "/";
		
				$res = db::queryOne($SQL);
				$comments = ($res[name]) ? $res[name] : "";

				$wwwpath .= $cur;
//echo $wwwpath;
			}
			
			$OUT[content] .= "
			<tr class=\"$class\">
				<td valign='middle'><a href='#' title='вставить имя файла' onclick=\"OpenerFilePathInsert('$wwwpath', 'file', 'name')\"><img src='images/file.gif' width='16' height='15' border='0'></a></td>
				<td valign='middle'><a href='#' title='вставить имя файла' onclick=\"OpenerFilePathInsert('$wwwpath', 'file', 'name')\"><b>$cur</b></a></td>
				<td><a href='#' title='вставить комментарий к файлу' onclick=\"OpenerFilePathInsert('$wwwpath', 'file', 'comments')\">$comments</td>
				<td><a href='#' title='вставить размер файла' onclick=\"OpenerFilePathInsert('$wwwpath', 'file', 'file_size')\">$filesize</td>
				<td><a href='#' title='вставить дату файла' onclick=\"OpenerFilePathInsert('$wwwpath', 'file', 'file_date')\">$filedate</td>
				<td><a href='#' title='вставить строку' onclick=\"OpenerFilePathInsert('$wwwpath', 'file', '')\">вставить</a></td>
				<td><a href='#' title='вставить ссылку для загрузки' onclick=\"OpenerFilePathInsert('$wwwpath', 'file', 'load')\">вставить</a></td>
			</tr>";	
	
		}
	}
}

$OUT[content] .= "
	</table>";

?>