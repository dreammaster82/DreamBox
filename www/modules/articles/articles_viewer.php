<?php
namespace articles{
	use core;
	class ArticlesViewer extends Articles implements core\CContentViewer{

		function __construct($m) {
			parent::__construct($m);
		}

		function show(){
			if($this->errors){
				return $this->error();
			}
			$out = array();
			$this->ret['not_catalog_menu'] = true;
			$item = $this->getItem(array('id', 'name', 'content', 'img_src', 'posted'), $this->id);
			$this->ret['style'] .= '<link rel="stylesheet" href="/modules/articles/css/style.css" />';
			if($item){
				return $this->showItem($item);
			} else {
				$this->ret['title'] = '';
				$this->ret['description'] = '';
				$this->ret['keywords'] = '';
				$out['cnt'] = $this->getCountItems(array('active' => 1));
				if($out['cnt']){
					$out['items'] = $this->getItems(array('id', 'name', 'annotation', 'posted', 'img_src'), array('active' => 1), false, false, false, false, $this->config['items_on_page']);
				}
				ob_start();
				include $this->path.'/data/show.html';
				return ob_get_clean();
			}
		}

		function showItems($out){
			ob_start();
			include $this->path.'/data/show_items.html';
			return ob_get_clean();
		}

		function showItem($out) {
			$this->ret['title'] = $out['name'];
			$this->ret['description'] = '';
			$this->ret['keywords'] = $out['name'];
			$out['posted'] = reset(explode(' ', $out['posted']));
			$out['data_array'] = explode('-', $out['posted']);
			$this->ret['js_before'] .= '<script defer src="/scripts/photo_viewer_light.js"></script>';
			$this->ret['js_after'] .= '<script>$(document).ready(function(){$(\'section.content\').WMphoto();});</script>';
			$this->ret['style'] .= '<link rel="stylesheet" type="text/css" href="/css/photo_viewer_light_round/photo_viewer_light_round.css" />';
			$out['other'] = $this->getItems(array('id', 'name', 'alias'), array('active' => 1, array(array('name' => 'id', 'cond' => '!=', 'data' => $out['id']))), array('posted DESC'), false, false, false, 5);
			ob_start();
			include $this->path.'/data/show_item.html';
			return ob_get_clean();
		}
	}
}
?>