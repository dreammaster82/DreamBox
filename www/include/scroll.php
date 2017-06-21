<?php
namespace core{
	class Scroll{
		public $limit = 12, $cnt = 20, $pagesInLine = 3, $page, $ret = [];

		function __construct() {
			$this->page = (int)$_REQUEST['page'];
		}

		function showPages($lnk = ''){
			if(!$this->cnt){
				return false;
			}
			$data = array();
			$out['iteration'] = ceil($this->cnt / $this->limit);
			if($out['iteration'] == 1){
				return false;
			}
			$first = floor($this->page / $this->pagesInLine) *  $this->pagesInLine;
			if($out['iteration'] <= $this->pagesInLine || $out['iteration'] <= ($first + $this->pagesInLine)){
				$out['end'] = $out['iteration'];
			} else {
				$out['end'] = $first + $this->pagesInLine;
			}
			$out['next'] = $first;
			if(($this->page + 1) > $this->pagesInLine) {
				$out['cur_page'] = $out['next'] - 1;
			} else {
				$out['page'] = $out['next'];
				$out['cur_page'] = 0;
			}
			if(!$this->page){
				$out['no_active'] = ' no_active';
			}
			if($lnk){
				if(strpos($lnk, '?') === false){
					$out['href'] = $lnk.'?page=';
				} else {
					$out['href'] = $lnk.'&page=';
				}
			} else {
				$out['href'] = './?page=';
			}
			if($this->cnt > $this->limit){
				$this->ret['js_after'] = '<script src="/js/scroll.js"></script>';
				ob_start();
				include CLIENT_PATH.'/data/show_pages.html';
				return ob_get_clean();
			} else {
				return '';
			}
		}
	}
}
?>