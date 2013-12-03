<?php
namespace catalog{
	class CatalogViewer extends Catalog implements \CContentViewer{

		function __construct($m) {
			parent::__construct($m);
		}
		
		function basket(){
			$ret = '';
			if($C = $this->Core->getClass(array('BasketViewer', 'catalog'))){
				$ret = $C->show();
				foreach ($C->ret as $k => $v){
					if($k != 'js_before' && $k != 'js_after'){
						$this->ret[$k] = $v;
					}
				}
				$this->ret['js_before'] .= $C->ret['js_before'];
				$this->ret['js_after'] .= $C->ret['js_after'];
			}
			return $ret;
		}
		
		function order(){
			$ret = '';
			if($C = $this->Core->getClass(array('BasketViewer', 'catalog'))){
				$ret = $C->order();
				foreach ($C->ret as $k => $v){
					if($k != 'js_before' && $k != 'js_after'){
						$this->ret[$k] = $v;
					}
				}
				$this->ret['js_before'] .= $C->ret['js_before'];
				$this->ret['js_after'] .= $C->ret['js_after'];
			}
			return $ret;
		}

		function showItems($items, $alias = ''){
			$out['items'] = $items;
			$out['alias'] = $alias;
			ob_start();
			include $this->path.'/data/show_items.html';
			return ob_get_clean();
		}

		function showItem($item){
			return false;
		}

		function showCatalogMenu($id = 0, $lvl = 0, $show = false){
			$out = array();
			$out['catalog_array'] = $this->getCatalogArray();
			if($out['catalog_array'][$id]['children']){
				$out['id'] = $id;
				$out['lvl'] = $lvl;
				$out['show'] = $show;
				$out['size_items'] = sizeof($out['catalog_array'][$id]['children']);
				$out['catalog_from_level'] = $this->catLvl;
				ob_start();
				$i = 0;
				include $this->path.'/data/show_catalog_menu.html';
				return ob_get_clean();
			} else {
				return false;
			}
		}

		function showItemsCatalogMenu($arr){
			$ret = array();
			$catArr = $this->getCatalogArray();
			foreach ($arr as $k => $v){
				if((int)$k && $catArr[(int)$k]['children']){
					$ret[(int)$k] = $this->showCatalogMenu((int)$k, (int)$v + 1, true);
				}
			}
			return $ret;
		}

		function show(){
			$out = array();
			$out['item'] = $this->getItem(array('id', 'name', 'title', 'keywords', 'description', 'alias', 'img_src', 'co_id', 'fpc_id'), $_REQUEST['alias']);
			if($_REQUEST['alias'] && !$out['item']['id']) $this->setError('Bad Request');
			if($this->errors){
				return $this->error();
			}
			$prod = array();
			$ret = '';
			if($out['item']['id']){
				$out['item']['alias'] = $this->getAlias($out['item']['id']);
				$_SESSION['catalog_alias'] = $out['item']['alias'];
			}
			$P = $this->Core->getClass(array(\Core::CLASS_NAME => 'ProductViewer', \Core::MODULE => 'catalog'));
			if(is_object($P) && $P->getId()){
				$ret = $P->show();
				foreach ($P->ret as $k => $v){
					if($k != 'js_before' && $k != 'js_after'){
						$this->ret[$k] = $v;
					}
				}
			} else {
				$out['path'] = $this->getPath($out['item']['id']);
				$out['title'] = $out['item']['name'];
				$this->ret['title'] = $out['item']['title'];
				$this->ret['description'] = $out['item']['description'];
				$this->ret['keywords'] = $out['item']['keywords'];
				if(is_object($P)){
					$out['cond'] = array($this->config['pref'].'id' => $out['item']['id'], 'active' => 1);
					if($_REQUEST['find']){
						if(!$out['item']['id']){
							$out['item'] = array('id' => 0, 'parent_id' => 0, 'content_id' => 0, 'name' => 'Поиск', 'alias' => '/', 'title' => 'Поиск', 'description' => 'Поиск', 'keywords' => 'Поиск');
						}
						$out['cond']['find'] = $_REQUEST['find'];
					}
					$out['count_products'] = $P->getCountItems($out['cond']);
					if($out['count_products']){
						$tData = array('id', $this->config['pref'].'id', 'name', 'img_src', 'alias', 'price', 'sale', 'articul', 'ca_alias');
						$order = array();
						if($_REQUEST['sort'] == 'name'){
							$order[] = 'name';
						}
						$order[] = 'price';
						$out['products'] = $P->getItems($tData, $out['cond'], $order, $this->config['items_on_page']);
					}
				}
				$this->ret['js_before'] .= '<script defer src="/modules/catalog/js/catalog_before.js"></script>';
				$this->ret['js_after'] .= '<script defer src="/modules/catalog/js/catalog_after.js"></script>';
				ob_start();
				include $this->path.'/data/show.html';
				$ret = ob_get_clean();
			}
			if(is_object($P)){
				$this->ret['js_before'] .= $P->ret['js_before'];
				$this->ret['js_after'] .= $P->ret['js_after'];
			}
			return $ret;
		}

		function showProducts($items, $cnt, $cond, $cur){
			if(!$P = $this->Core->getClass(array(\Core::CLASS_NAME => 'ProductViewer', \Core::MODULE => 'catalog'))){
				return false;
			}
			$out = array();
			$out['items'] = $items;
			unset($items);
			$out['href'] = '/catalog'.($cur['alias'] ? $cur['alias'] : '').'/';
			$arr = array();
			/*---Load Filters---*/
			if($cur['fpc_id'] && $F = $this->Core->getClass(array(\Core::CLASS_NAME => 'Filters', \Core::MODULE => 'catalog'))){
				$arr['filters'] = 'fl=';
				$out['filters'][] = array('name' => 'catId', 'value' => $cur['id']);
				if($P->serv['filters']['groups']){
					foreach ($P->serv['filters']['groups'] as $k => $v){
						$arr['filters'] .= 'f'.$k.'_'.implode('_', $v);
						foreach ($v as $k1 => $v1){
							$out['filters'][] = array('name' => 'filters['.$k.']['.$k1.']', 'value' => $v1);
						}
					}

				}
				list($pMin, $pMax) = $P->getMinMaxPrices($cond);
				$out['filters'][] = array('name' => 'min_price', 'value' => (int)$pMin);
				$out['filters'][] = array('name' => 'max_price', 'value' => ceil($pMax));
				if($P->serv['filters']['price']){
					$arr['filters'] .= 'p';
				}
				if($P->serv['filters']['price'][0]){
					$out['filters'][] = array('name' => 'price_from', 'value' => $P->serv['filters']['price'][0]);
					$out['filters'][] = array('name' => 'min', 'value' => 1);
					$arr['filters'] .= $P->serv['filters']['price'][0];
				} else {
					$out['filters'][] = array('name' => 'price_from', 'value' => (int)$pMin);
				}
				if($P->serv['filters']['price'][1]){
					$out['filters'][] = array('name' => 'price_to', 'value' => $P->serv['filters']['price'][1]);
					$out['filters'][] = array('name' => 'max', 'value' => 1);
					$arr['filters'] .= '_'.$P->serv['filters']['price'][1];
				} else {
					$out['filters'][] = array('name' => 'price_to', 'value' => ceil($pMax));
				}
				if($_REQUEST['sort']){
					$out['filters'][] = array('name' => 'sort', 'value' => $_REQUEST['sort']);
				}
				
				$this->ret['js_before'] .= '
					<script defer src="/modules/catalog/js/ui.core.widget.mouse.slider.js"></script>
					<script defer src="/modules/catalog/js/filters.js"></script>';
				$this->ret['js_after'] .= '<script>var filterBlock = "#filter_form";</script>';
			} elseif($P->serv['filters']) {
				$arr['filters'] = 'fl=';
				if($P->serv['filters']['groups']){
					foreach ($P->serv['filters']['groups'] as $k => $v){
						$arr['filters'] .= 'f'.$k.'_'.implode('_', $v);
					}
				}
				if($P->serv['filters']['price']){
					$arr['filters'] .= 'p';
				}
				if($P->serv['filters']['price'][0]){
					$arr['filters'] .= $P->serv['filters']['price'][0];
				}
				if($P->serv['filters']['price'][1]){
					$arr['filters'] .= '_'.$P->serv['filters']['price'][1];
				}
			}
			if($arr['filters']){
				$out['filters_array'] = $arr['filters'];
			}
			/*------*/
			if($_REQUEST['find']){
				$arr['find'] .= 'find='.$_REQUEST['find'];
			}
			if($arr){
				$out['add_href'] = '&'.implode('&', $arr);
			}
			if($_REQUEST['sort'] == 'name'){
				$out['sort']['name'] = 'active';
				$arr['sort'] = 'sort=name';
			} else {
				$out['sort']['price'] = 'active';
			}
			if($arr){
				$href .= '?'.implode('&', $arr);
			}
			if($out['items']){
				if($S = $this->Core->getClass('Scroll')){
					$S->cnt = $cnt;
					$S->limit = $this->config['items_on_page'];
					$S->pageInLine = $this->config['pages_in_line'];
					$out['pages'] = $S->showPages($href);
				}
			}
			ob_start();
			include $this->path.'/data/show_products.html';
			return ob_get_clean();
		}
		
		function spec(){
			$this->ret['title'] = 'Спецпредложения';
			$this->ret['description'] = 'Спецпредложения';
			$this->ret['keywords'] = 'Спецпредложения';
			$out['path'] = [['link' => '/catalog/', 'name' => 'Каталог продукции'], ['name' => 'Спецпредложения']];
			if($P = $this->Core->getClass(array(\Core::CLASS_NAME => 'ProductViewer', \Core::MODULE => 'catalog'))){
				$out['cond'] = array('active' => 1, 'spec' => 1);
				$out['count_products'] = $P->getCountItems($out['cond']);
				if($out['count_products']){
					$tData = array('id', $this->config['pref'].'id', 'name', 'img_src', 'alias', 'price', 'sale', 'articul', 'ca_alias');
					$order = array();
					if($_REQUEST['sort'] == 'name'){
						$order[] = 'name';
					}
					$order[] = 'price';
					$out['items'] = $P->getItems($tData, $out['cond'], $order, $this->config['items_on_page']);
					$arr = array();
					$href = '';
					if($_REQUEST['sort'] == 'name'){
						$out['sort']['name'] = 'active';
						$arr['sort'] = 'sort=name';
					} else {
						$out['sort']['price'] = 'active';
					}
					if($arr){
						$href .= '?'.implode('&', $arr);
					}
					if($S = $this->Core->getClass('Scroll')){
						$S->cnt = $cnt;
						$S->limit = $this->config['items_on_page'];
						$S->pageInLine = $this->config['pages_in_line'];
						$out['pages'] = $S->showPages($href);
					}
				}
				$this->ret['js_before'] .= $P->ret['js_before'];
				$this->ret['js_after'] .= $P->ret['js_after'];
				$this->ret['js_before'] .= '<script defer src="/modules/catalog/js/catalog_before.js"></script>';
				$this->ret['js_after'] .= '<script defer src="/modules/catalog/js/catalog_after.js"></script>';
			}
			ob_start();
			include $this->path.'/data/show_spec.html';
			$ret = ob_get_clean();
			return $ret;
		}
	}
}
?>
