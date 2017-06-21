<?php
function getPreview($id, $th, $cnf){
	if(!is_numeric($id)){
		return false;
	}
	$preview = $th->getItem(array('id', 'name', 'note', 'img_small_src', 'img_big_src', 'img_small_x', 'img_small_y', 'img_big_x', 'img_big_y'), $id);
	$imgSrc = $cnf['preview_root'].$preview['img_small_src'];
	$imgSrc2 = $cnf['preview_root'].$preview['img_big_src'];
	$href = $imgSrc2;
	$width = $preview['img_small_x'];
	$height = $preview['img_small_y'];
	$alt = str_replace('"','',$preview['name']);
	$note = ($preview['note']) ? $preview['note'] : '';
	$link .= ($preview['img_big_x']) ? '
		<div class="photo_gallery">
			<span class="one_image">
				<a href="'.$href.'" title="'.$note.'"  jqLink="'.$imgSrc2.'">
					<img src="'.$imgSrc.'" style="width:'.$width.'px;height:'.$height.'px" alt="'.$alt.'" />'.
			($alt ? '<span>'.$alt.'</span>' : '')
				.'</a>
			</span>
		</div>' : 
		'<img src="'.$imgSrc.'" style="width:'.$width.'px;height:'.$height.'px" alt="'.$alt.'" />';
	return $link;
}

?>