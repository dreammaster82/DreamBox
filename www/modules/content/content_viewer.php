<?php
namespace content{
	class ContentViewer extends Content implements \CContentViewer{

		function __construct($m) {
			parent::__construct($m);
			$this->config['admin'] = 'content';
		}

		function showItems($out){
			ob_start();
			include $this->path.'/data/show_items.html';
			return ob_get_clean();
		}

		function showItem($out) {
			$this->ret['title'] = $out['title'];
			$this->ret['description'] = $out['description'];
			$this->ret['keywords'] = $out['keywords'];
			$this->ret['template'] = $out['template'];
			$out['children'] = $this->getItems(array('id', 'name', 'alias'), array('parent_id' => $out['id']), array('priority'));
			$dt = explode('-', reset(explode(' ', $out['posted'])));
			$out['date'] = $dt[2].' '.$this->months[(int)$dt[1]].' '.$dt[0];
			$this->ret['js_before'] .= '<script src="/js/photo_viewer_light.js"></script>';
			$this->ret['js_after'] .= '<script>$(document).ready(function(){$(\'section.content\').WMphoto();});</script>';
			$this->ret['style'] .= '<link rel="stylesheet" href="/css/photo_viewer_light_round/photo_viewer_light_round.css" />';
			ob_start();
			include $this->path.'/data/show_item.html';
			return ob_get_clean();
		}

		function show(){
			if($this->errors){
				return $this->Util->error($this->errors);
			}
			if($_REQUEST['alias']){
				$tData = array('id', 'name', 'content', 'img_src', 'title', 'description', 'keywords', 'toprint', 'parent', 'alias', 'template', 'is_text', 'posted', 'active');
				$item = $this->getItem($tData, $_REQUEST['alias']);
				if($item){
					return $this->showItem($item);
				}
			}
			return $this->Util->error('Not item');
		}
	}
}
?>
