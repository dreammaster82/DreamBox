<?php
namespace reviews{
	use core;
	class ReviewsViewer extends Reviews implements core\CContentViewer{

		function __construct($m) {
			parent::__construct($m);
		}

		function show(){
			if($this->errors){
				return $this->error();
			}
			$this->ret['title'] = '';
			$this->ret['description'] = '';
			$this->ret['keywords'] = '';
			$out['cnt'] = $this->getCountItems(array('active' => 1));
			if($out['cnt']){
				$out['items'] = $this->getItems(array('id', 'name', 'text', 'ask', 'posted', 'type', 'raiting'), array('active' => 1), false, false, false, false, $this->config['items_on_page']);
			}
			$this->ret['style'] .= '<link rel="stylesheet" href="/modules/reviews/css/style.css" />';
			$this->ret['style'] .= '<link rel="stylesheet" href="/modules/reviews/css/range.css" />';
			$this->ret['js_before'] .= '<script src="/modules/reviews/js/script.js" defer></script>';
			ob_start();
			include $this->path.'/data/show.html';
			return ob_get_clean();
		}

		function showItems($out){
			ob_start();
			include $this->path.'/data/show_items.html';
			return ob_get_clean();
		}

		function showItem($out) {
			return '';
		}
	}
}
?>