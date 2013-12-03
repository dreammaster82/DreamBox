<?php
namespace content{
	class Content extends \CContent{
		protected $config = array(
					'header' => 'Содержание страниц',
					'table' => 'content',
					'pref' => 'co_'
				);

		public $ret = array();
		
		function __construct($m) {
			parent::__construct(__CLASS__, $m);
		}

		function parseRequest($request) {
			$ret = array();
			if(strpos($request, '/') !== false){
				$this->setError('badRequest');
			}
			if(strpos($request, '.html') !== false){
				$ret['alias'] = str_replace('.html', '', $request);
			} else {
				$this->setError('badRequest');
			}
			return $ret;
		}

		function getItem($data, $id){
			static $item = array();
			if((int)$id){
				if($item['id'] == $id){
					return $item;
				}
				$item =  parent::getItem($data, $id);
			} else {
				if($item['alias'] == $id){
					return $item;
				}
				$item =  parent::getItem($data, array('alias' => $id));
			}
			if((!(int)$id &&!$item['active']) && $item['id'] != 1){
				$item = array();
			}
			return $item;
		}
		
		function getTopMenu(){
			if(!$ret = $this->Util->memcacheGet(__FUNCTION__)){
				$r = $this->getItems(array('id', 'parent_id', 'alias', 'name'), array('active' => 1, 'on_top' => 1), array('priority'));
				$arr = array();
				foreach ($r as $v){
					$arr[$v['parent_id']][$v['id']] = $v;
					$arr[$v['parent_id']][$v['id']]['real_alias'] = '/content/'.$v['alias'].'.html';
				}
				foreach($arr[0] as $k => $v){
					if($arr[$k]){
						$arr[0][$k]['children'] = $arr[$k];
					}
				}
				$ret = $arr[0];
				$this->Util->memcacheSet(__FUNCTION__, $ret, $this->class);
			}
			return $ret;
		}
		
		function getPath($item = array()){
			$ret = array();
			if($item){
				$ret[] = array('link' => '', 'name' => $item['name']);
			}
			return $ret;
		}
	}
}
?>