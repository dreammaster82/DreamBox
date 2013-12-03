<?php

$open = "[SlideShow][";
$close = "][/SlideShow]";
$open_length = strlen($open);
$close_length = strlen($close);

$prevCFG = array(
	'slide_children'		=> 'gallery_children',
	'slide_root'			=> '/files/gallery',
	'slide_win_x_offset'	=> 29,
	'slide_win_y_offset'	=> 29,
	'slide_win_x_min'		=> 150,
	'slide_win_y_min'		=> 200,
	);

while($slide_pos=strpos($content, $open, $slide_pos+1))
{
	$slide_end = strpos($content, $close, $slide_pos+1);
	$slide_id = substr($content, $slide_pos+$open_length, ($slide_end-$slide_pos-$close_length+1));

	$content = substr($content, 0, $slide_pos) . _getSlide($slide_id, $this->current, $href) . substr($content, $slide_end+$close_length);
}

function _getSlide($slide_id, $current, $href)
{
	global $prevCFG;

	if(!is_numeric($slide_id)) return;

	$link = '<div id="photo_gallery4" class="photo_gallery slide_show">';

	$SQL = 'SELECT id, name, img_small_src, img_big_src, img_small_x, img_small_y, img_big_x, img_big_y FROM '.$prevCFG['slide_children'].' WHERE parent_id='.$slide_id.' AND active=1 ORDER BY priority';
	$slide_img = Db::query($SQL);
	$max_height = $prevCFG['slide_win_y_min'];
	foreach($slide_img as $key=>$slide)
	{
		$win_height = $slide['img_small_y'] + $prevCFG['slide_win_y_offset'];
		$max_height = ($max_height > $win_height) ? $max_height : $win_height;
	}
	foreach($slide_img as $key=>$slide)
	{
		$img_src = $prevCFG['slide_root'].$slide['img1_src'];
		$img_href = $prevCFG['slide_root'].$slide['img2_src'];
		$width = $slide['img_small_x'];
		$height = $slide['img_small_y'];

		$win_width = $slide['img_small_x'] + $prevCFG['slide_win_x_offset'];
		$win_height = $slide['img_small_y'] + $prevCFG['slide_win_y_offset'];
		$win_width = ($win_width < $prevCFG['slide_win_x_min']) ? $prevCFG['slide_win_x_min'] : $win_width;
		$win_height = ($win_height < $prevCFG['slide_win_y_min']) ? $prevCFG['slide_win_y_min'] : $win_height;
		$link .= '<div style="width:'.$win_width.'px;height:'.$max_height.'px;"><a href="'.$img_href.'" target="details" rel="prettyPhoto[photos]" title="'.$slide['name'].'" jqLink="'.$img_href.'"><img src="'.$img_src.'" width="'.$width.'" height="'.$height.'" border="0" alt="" /></a></div>';
	}
	$link .= '</div>';
	return $link;
}

?>