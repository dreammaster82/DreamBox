<?php
	function HTML_convert($text){
		global $DATA;
		
		if(!$text){
			return false;
		}
		
		$attributs = array('border','width','height','cellspacing','cellpadding','colspan','rowspan','nowrap','align','valign','href','target','src','alt','hspace','vspace','name');
		//$tags = array('table','tr','td','th','tboby','p','b','strong','br','ul','li','img','a','u','i','center','div','span','h1','h2','h3','address','/table','/tr','/td','/th','/tboby','/p','/b','/strong','/ul','/li','/a','/u','/i','/center','/div','/span','/h1','/h2','/h3','/address');
		$tags = array('table','tr','td','th','tboby','p','b','strong','br','ul','li','img','a','u','i','center','div','h1','h2','h3','address','/table','/tr','/td','/th','/tboby','/p','/b','/strong','/ul','/li','/a','/u','/i','/center','/div','/h1','/h2','/h3','/address');
		$newline_tags = array('table','tr','td','th','tbody','/table','/tr','/th','/tbody','br','p','/p','ul','/ul','li','h1','h2','h3');
		$tab_tags = array('td');

		$replace = array(
			'strong' =>'b',
			'STRONG' =>'b',
			'\t' =>'',
			'\r' =>'',
			'\n' =>'',
			'/>' =>'>',
			'  ' =>' ',
			);
		
		foreach($replace as $old => $new){
			$text = str_replace($old, $new, $text);
		}

		$text = stripslashes($text);
		$text = str_replace("\n", '', $text);
		$text = str_replace("\r", '', $text);
		$text = preg_replace('/( ){2,}/m', '', $text);
		//$text = str_replace(" ", '', $text);

		$res = preg_split('((>)|(<))', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		for ($i = 0; $i < count($res); $i++){
			$res[$i] = stripslashes($res[$i]);
			if($res[$i] == "<"){
				$res[$i+1] = str_replace("\"", "", $res[$i+1]);
				$res[$i+1] = str_replace("'", "", $res[$i+1]);
				$res[$i+1] = str_replace("`", "", $res[$i+1]);
				$i += 2;
			}
		}

		$k=0; $count = count($res); $img_cnt = 1; $a_cnt = 1;
		for ($i = 0; $i < $count; $i++)
		{
			$res[$i] = stripslashes($res[$i]);
			if($res[$i] == "<")
			{
				$tag = preg_split("/ /", $res[$i+1], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

				for($j = 0; $j < count($tag); $j++)
				{
					
					$attr = explode('=', $tag[$j]);

					if($attr)
					{
						$attr[0] = strtolower($attr[0]);

						if($j == 0)
						{
							if(($attr[0] == "img")||($attr[0] == "a"))
							{
								if($attr[0] == "img")
								{
									$ta = array();
									$tattr = '';
									foreach ($tag as $tk => $tv){
										if($tk){
											$arr = explode('=', $tv);
											if(in_array($arr[0], $attributs)){
												$tattr = $arr[0];
												unset($arr[0]);
												$tv = implode('=', $arr);
											} else {
												$tv = ' '.$tv;
											}
											$ta[$tattr] .= $tv;
										}
									}
									$DATA['images'][$img_cnt] = $ta;
									$spec_tag_id = $img_cnt;
									$img_cnt++;
								}
								if($attr[0] == "a")
								{
									$DATA['anchors'][$a_cnt] = $tag;
									$spec_tag_id = "";
									if(count($tag))
									{
										foreach($tag as $tagattr)
										{
											$ttmp = explode("=", $tagattr);
											if(count($ttmp)){ if($ttmp[0] == "href"){ $spec_tag_id = " href='". $ttmp[1] ."'"; } }
										}
									}
									//$spec_tag_id = $tag[1];
									$a_cnt++;
								}
								unset($tag);
								if($attr[0] == "img"){ $tag[0] = $attr[0]."[$spec_tag_id]"; }
								if($attr[0] == "a"){ $tag[0] = $attr[0] ." ". $spec_tag_id ." target='_blank' rel='nofollow'"; }
								break;
							}
							if(!in_array($attr[0],$tags)) $tag[$j] = ""; else $tag[$j] = $attr[0];
						} else
						{
							if($attr[0] == "cellspacing") $attr[1] = '1';
							if($attr[0] == "cellpadding") $attr[1] = '3';
#							if($attr[0] == "width") $attr[1] = '100%';
							if($attr[0] == "border") $attr[1] = '0';

							if(!in_array($attr[0],$attributs))
								$tag[$j] = "";
							elseif(isset($attr[1]))
								$tag[$j] =  $attr[0]."=\"".$attr[1]."\"";
						}
					}
				}

				if(is_array($tag)) $res[$i+1] = implode(" ", $tag); else $res[$i+1] = $tag;

				$tag = preg_split("/ /", $res[$i+1], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

				$new_line = ""; $tab = "";
				if($tag[0])
				{
					$lasttag = $tag[0];
					if(in_array($tag[0],$tab_tags)) $tab = "\t";
					if(in_array($tag[0],$newline_tags)) $new_line = "\n";
				}

				$res[$i] = $tab . $res[$i];
				if($new_line && $i) $res[$i] = $new_line . $res[$i];
				$res[$i+1] = implode(" ", $tag);

				$i += 2;
				continue;
			}

		}

		$res = implode("", $res);

		$replace = array(
			'<>' =>'',
			'<a ' =>'<noindex><a ',
			'</a>' =>'</a></noindex>',
			);
		foreach($replace as $old => $new){
			$res = str_replace($old, $new, $res);
		}
			
		return $res;
	}
	
	function parseUrlFromData($data){
		$url = array();
		if(is_array($data)){
			foreach ($data as $k => $v){
				foreach ($v as $k1 => $v1){
					foreach ($v1 as $k2 => $v2){
						preg_match('/http:\/\/(.*?)\//', $v2, $arr);
						if($arr[0]){
							$url[$arr[0]] = $arr[0];
						}
					}
				}
			}
		}
		if($url){
			$data['url'] = $url;
		}
		return $data;
	}
?>