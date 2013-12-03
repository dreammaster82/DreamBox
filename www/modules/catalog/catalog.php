<?php
namespace catalog{
	class Catalog extends \CContent{
		protected $config = array(
			'table' => 'catalogs',
			'pref' => 'ca_',
			'link_table' => 'catalog_link',
			'link_pref' => 'cl_',
			'items_on_page' => 12,
			'pages_in_line' => 3,
			'reqId' => 'id'
		);

		public $ret = array();

		function __construct($m) {
			parent::__construct(__CLASS__, $m);
		}

		function parseRequest($request) {
			$ret = array();
			if($request){
				$arr = explode('/', $request);
				if(strpos($arr[sizeof($arr) - 1], '.html')){
					array_pop($arr);
					$ret['alias'] = implode('/', $arr);
				} else {
					if(!$arr[sizeof($arr) - 1]){
						array_pop($arr);
					}
					$request = implode('/', $arr);
					if(in_array($request, array('order', 'basket', 'spec'))){
						$ret['action'] = $request;
					} else {
						$ret['alias'] = $request;
					}
				}
			}
			return $ret;
		}

		function getItem($data, $cond){
			static $item = array();
			if((int)$cond && $item['id'] == $cond){
				$ret = array();
				foreach ($data as $v){
					if(isset($item[$v])){
						$ret[$v] = $v;
					}
				}
				$ret['id'] = $item['id'];
				if($ret){
					return $ret;
				}
			}
			if($cond){
				$item = $this->getCurItem($data, $cond);
				if($item['co_id']){
					$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Content', \Core::MODULE => 'content'));
					if(is_object($C)){
						$item = array_merge($item, $C->getItem(array('content', 'name' => 'co_name'), $item['co_id']));
					}
				}
			} else {
				$arr = array('id' => 0, 'parent_id' => 0, 'co_id' => 0, 'name' => 'Каталог', 'alias' => '/', 'title' => 'Каталог', 'description' => 'Каталог', 'keywords' => 'Каталог');
				$item = array();
				foreach ($data as $v){
					if(isset($arr[$v])){
						$item[$v] = $arr[$v];
					}
				}
			}
			return $item;
		}

		function getCurItem($data, $cond){
			$item = array();
			$b = true;
			foreach ($data as $k => $v){
				if($k === 'id' || $v === 'id'){
					$b = false;
				}
			}
			if($b){
				$data[] = 'id';
			}
			if((int)$cond){
				$item = parent::getItem($data, $cond);
			} else {
				if(is_string($cond)){
					$arr = explode('/', $cond);
					$alias = array_pop($arr);
					$items = parent::getItems($data, array('active' => 1, 'alias' => $alias));
					if(sizeof($items) > 1){
						$par = $this->getCurItem(array('id', 'parent_id'), implode('/', $arr));
						if($par){
							$carArr = $this->getCatalogArray();
							foreach ($items as $v){
								if($catArr[$v['id']]['parent_id'] == $par['id']){
									$item = $v;
									break;
								}
							}
						}
					} else {
						$item = $items[0];
					}
				} else {
					$item = parent::getItem($data, $cond);
				}
			}
			if(!$item){
				$this->setError('Not Item');
			} else {
				return $item;
			}
		}

		function ajaxProcess(){
			include_once MODULES_PATH.'/product.php';
			include_once MODULES_PATH.'/basket.php';
			if($_REQUEST['href']){
				$hrArr = array();
				if(strpos($_REQUEST['href'], '/basket/') !== false){
					$_REQUEST['action'] = 'basket';
				} else {
					$_REQUEST['href'] = str_replace('/catalog/', '', $_REQUEST['href']);
					$_REQUEST['alias'] = preg_replace('/\/([0-9]+)_([A-z0-9_-])/', '', $_REQUEST['href']);
					$str = str_replace($_REQUEST['alias'], '', $_REQUEST['href']);
					if($str){
						$arr = explode(str_replace('/', '', trim($str)));
						if((float)$arr[0]){
							$_REQUEST['prodId'] = (float)$arr[0];
						}
					}
				}
			}
			$action = $_REQUEST['action'];
			if(!$action || !method_exists($this,$action)){
				$action = 'show';
			}
			return $this->$action();
		}

		function getCatalogArray(){
			static $catArr = array();
			if(!$catArr){
				if(!$catArr = $this->Util->memcacheGet(__FUNCTION__)){
					$catArr = $this->getItems(array('id', 'parent_id', 'name', 'alias', 'img_src'), array('active' => 1), array('priority'), 'id');
					if($catArr){
						foreach ($catArr as $k => $v){
							$catArr[$v['parent_id']]['children'][$k] = &$catArr[$k];
						}
						$this->Util->memcacheSet(__FUNCTION__, $catArr, $this->class);
					}
				}
			}
			return $catArr;
		}

		function getAlias($id){
			static $alArr = array();
			if(!$alArr[$id]){
				$arr = array();
				$ca = $this->getCatalogArray();
				$arr[] = $ca[$id]['alias'];
				$pId = $ca[$id]['parent_id'];
				while($pId){
					$arr[] = $ca[$pId]['alias'];
					$pId = $ca[$pId]['parent_id'];
				}
				$alArr[$id] = '/'.implode('/', array_reverse($arr));
			}
			return $alArr[$id];
		}

		function getItempropCat($id){
			$ret = '';
			$ca = $this->getCatalogArray();
			$par = array($ca[$id]['name']);
			$pId = $ca[$id]['parent_id'];
			while($pId){
				$par[] = $ca[$pId]['name'];
				$pId = $ca[$pId]['parent_id'];
			}
			if($par){
				$par = array_reverse($par);
				foreach ($par as $v){
					$ret .= $v.' > ';
				}
				$ret = substr($ret, 0, -3);
			} 
			return $ret;
		}

		function getPath($id){
			$ret = array();
			if($id){
				$ca = $this->getCatalogArray();
				$ret[] = array('name' => $ca[$id]['name']);
				$pId = $ca[$id]['parent_id'];
				while($pId){
					$ret[] = array('link' => '/catalog'.$this->getAlias($pId).'/', 'name' => $ca[$pId]['name']);
					$pId = $ca[$pId]['parent_id'];
				}
				$ret[] = array('link' => '/catalog/', 'name' => 'Каталог продукции');
				$ret = array_reverse($ret);
			} else {
				$ret[] = array('name' => 'Каталог продукции');
			}
			return $ret;
		}

		function getLinkedItems($id){
			$r = $this->getItems(array($this->config['pref'].'id_l' => 'linked'), array($this->config['pref'].'id' => $id), false,
					false, $this->config['link_table'], $this->config['link_pref']);
			$ret = array();
			foreach ($r as $v){
				$ret[] = $v['linked'];
			}
			return $ret;
		}
	}
}
?>
