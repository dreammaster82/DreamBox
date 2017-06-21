<?php

	function replaceAnchors($DATA,$desc)
	{
		if(!$DATA[anchors]) return $desc;

		$URL = parseRealURL($DATA[url]);
		foreach($DATA[anchors] as $key=>$a)
		{
			if($a)
			{
				$anchor = array();
				$newval = "";
				foreach($a as $curr)
				{
					$temp = explode('=',$curr);
					$newkey = $temp[0];
					for($i=1;$i<count($temp);$i++) 
					{
						$newval .= $temp[$i];
						if($i < (count($temp) - 1)) $newval .= "=";
					}
					$anchor[$newkey] = $newval;
				}

				$have_java = strpos($anchor[href],"javascript");

				if(!$anchor[name] && !$anchor[id] && !$anchor[name] && $anchor[href][0] <> "#" && !strpos($anchor[href],"@") && ($have_java === false))
				{
					$realhref = getRealURL($URL, $anchor[href]);
					$attrs = "a href=\"".$realhref."\" target=\"_blank\"";
				} 
				elseif($anchor[name]) 
				{
					$attrs = "a name=\"".$anchor[name]."\"";
				} 
				elseif($anchor[id]) 
				{
					$attrs = "a id=\"".$anchor[id]."\"";
				} 
				else $attrs = "a href=\"".$anchor[href]."\"";

				if($have_java === false)
				{
					$desc = str_replace("a[$key]",$attrs,$desc);
				}
				else $desc = str_replace("a[$key]","a",$desc);

			}
		}
		return $desc;
	}

?>