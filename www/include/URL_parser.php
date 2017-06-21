<?php

function parseRealURL($prefix)
{
	$URL = array();
	$last = strlen($prefix) - 1;
	if($prefix[$last] == "/") $prefix .= "index.php";
	$path = parse_url($prefix);
	$URL[scheme] = $path[scheme];
	$URL[host] = $path[host];
	$URL[path] = $path[path];
	$path = pathinfo($path[path]);
	$URL[dirname] = ($path[dirname] <> "\\") ? $path[dirname] : "/".$path[basename]."/";
	return $URL;
}

function getRealURL($URL, $src)
{
	$temp = parse_url($src);

	$path = pathinfo($temp[path]);

	$temp[dirname] = $path[dirname];
	$temp[basename] = $path[basename];

	$query_str = $temp[query] ? "?".$temp[query] : "";

	if(!$temp[host])
	{
		if($temp[dirname][0] <> "/")
		{
			if($URL[dirname][0] <> "/")
			{
				$realurl = $URL[scheme]."://".$URL[host].$URL[dirname]."/".$temp[basename].$query_str;
			}
			else
			{
				$realurl = $URL[scheme]."://".$URL[host].$URL[dirname]."/".$temp[dirname]."/".$temp[basename].$query_str;
			}
		} 
		else
		{
			$realurl = $URL[scheme]."://".$URL[host].$temp[dirname]."/".$temp[basename].$query_str;
		}
	} 
	else 
	{
		if(!$URL[name])
		{
			if($temp[scheme]) $realurl = $src; 
			else $realurl = $URL[scheme]."://".$src;
		} 
		else $realurl = "";
	}

	return $realurl;
}

?>