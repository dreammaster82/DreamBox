<?php
ob_start();
if ($_REQUEST['is_json']){
	header('Content-Type: text/plain; charset=utf-8');
} else {
	header('Content-Type: text/html; charset=utf-8');
}
include realpath($_SERVER['DOCUMENT_ROOT']).'/include/setup.php';		# утилиты

class getFilters extends Util{
    private $config = array(
		'catalog_table' => 'catalogs',
		'product_table' => 'product',
		'product_other' => 'product_other',
		'product_pref' => 'pr_',
		'product_other_pref' => 'po_',
		'catalog_pref' => 'ca_',
		'parametr_groups' => 'filter_parametr_groups',
		'parametr_groups_pref' => 'fpg_',
		'parametr_items' => 'filter_parametr_items',
		'parametr_items_pref' => 'fpi_',
		'parametr_categories' => 'filter_parametr_categories',
		'parametr_categories_pref' => 'fpc_',
		'prod_parametr_links' => 'filter_prod_parametr_links',
		'prod_parametr_links_pref' => 'fppl_',
		'show_slider' => true,
		'slider_after' => 0
    );
    private $current, $filters = array(), $pGroups = array(), $pItems = array(), $pCount = 0, $fGroupId = array(), $fItemId = array(), $fProdId = array(), $fPrProdId = array(), $fName = array();
    public $pMinc = 10000000, $pMaxc = 0, $return = '', $json = array(), $all = '', $sf = array(), $prFrom, $prTo, $groups = array();
	
	function __construct() {
		if((int)$_REQUEST['catId']){
			if(is_array($_REQUEST['filters'])){
				foreach ($_REQUEST['filters'] as $key => $value){
					foreach ($value as $k => $v){
						$this->filters[(int)$key][(int)$k] = (int)$k;
					}
				}
			}
		}
		$this->prFrom = (int)$_REQUEST['min_price'];
		$this->prTo = ceil($_REQUEST['max_price']);
	}
	
	function getCatalogArray(){
		static $catArr = array();
		if(!$catArr){
			if(!$catArr = $this->memcacheGet(__FUNCTION__)){
				$r = $this->Db->query('SELECT
					'.$this->config['catalog_pref'].'id AS id,
					'.$this->config['catalog_pref'].'parent_id AS parent_id,
					'.$this->config['catalog_pref'].'name AS name,
					'.$this->config['catalog_pref'].'alias AS alias,
					'.$this->config['catalog_pref'].'img_src AS img_src
					FROM '.$this->config['catalog_table'].'
					WHERE '.$this->config['catalog_pref'].'active=1 ORDER BY '.$this->config['catalog_pref'].'priority');
				if($r){
					$s = sizeof($r);
					for($i = 0;$i < $s; ++$i){
						$catArr[$r[$i]['id']] = $r[$i];
					}
					foreach($catArr as $k => $v){
						$catArr[$v['parent_id']]['children'][$k] = &$catArr[$k];
					}
					$this->memcacheSet(__FUNCTION__, $catArr, $this->class);
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

    function getFilterProducts(){
		$this->pGroups = array();
		$this->pItems = array();
		foreach($this->filters as $k => $v){
			$this->pGroups[$k] = 1;
			foreach($v as $k1 => $v1){
				$this->pItems[$k1] = $k;
			}
		}
		$this->pCount = sizeof($this->pGroups);
		if(!$this->pItems){
			return;
		}
		$arr = array($this->current['id'], 1, 1);
		$fAdd = array();
		foreach($this->pItems as $k => $v){
			$fAdd[$k] .= $k;
		}
		$arr = array_merge($arr, $fAdd);
		$fAddStr = '';
		if($fAdd){
			if(sizeof($fAdd) == 1){
				$fAddStr = ' AND '.$this->config['prod_parametr_links_pref'].$this->config['parametr_items_pref'].'id=?';
			} else {
				$fAddStr = ' AND '.$this->config['prod_parametr_links_pref'].$this->config['parametr_items_pref'].'id IN('.substr(str_repeat('?,', sizeof($fAdd)), 0, -1).')';
			}
		}
		if($this->config['show_slider']){
			$r = $this->Db->query('SELECT 
				COUNT(*) AS cnt,
				'.$this->config['product_other_pref'].'price AS price
				FROM '.$this->config['product_table'].'
				INNER JOIN '.$this->config['product_other'].' ON('.$this->config['product_pref'].'id='.$this->config['product_other_pref'].$this->config['product_pref'].'id)
				INNER JOIN '.$this->config['prod_parametr_links'].' ON('.$this->config['product_pref'].'id='.$this->config['prod_parametr_links_pref'].$this->config['product_pref'].'id)
				WHERE ('.$this->config['product_pref'].$this->config['catalog_pref'].'id=? AND '.$this->config['product_pref'].'active=?)
					AND '.$this->config['prod_parametr_links_pref'].'is_filter=?'.$fAddStr.'
				GROUP BY '.$this->config['prod_parametr_links_pref'].$this->config['product_pref'].'id
				HAVING cnt='.$this->pCount,
				$arr);
			if($r){
				$s = sizeof($r);
				for($i = 0;$i < $s; ++$i){
					if($this->pMinc > $r[$i]['price']){ $this->pMinc = (int)$r[$i]['price']; }
					if($this->pMaxc < $r[$i]['price']){ $this->pMaxc = (int)$r[$i]['price']; }
				}
			}
		}
    }

    function process(){
		$this->current = $this->Db->queryOne('SELECT
			'.$this->config['catalog_pref'].'id AS id,
			'.$this->config['catalog_pref'].$this->config['parametr_categories_pref'].'id AS category_id
			FROM '.$this->config['catalog_table'].'
			INNER JOIN '.$this->config['parametr_categories'].' ON('.$this->config['catalog_pref'].$this->config['parametr_categories_pref'].'id='.$this->config['parametr_categories_pref'].'id)
			WHERE '.$this->config['catalog_pref'].'id=?', array((int)$_REQUEST['catId']));
		$this->current['alias'] = $this->getAlias($this->current['id']);
		if($this->current['id']){
			if ($this->filters){
				$this->getFilterProducts();
			} else {
				$this->pMinc = (int)$_REQUEST['min_price'];
				$this->pMaxc = ceil($_REQUEST['max_price']);
			}
			return $this->getProdFilters();
		}
		return array();
    }

    function getGroupFilters($gId){
		if(!$this->pItems) {
			return $this->fGroupId[$gId];
		}
		$temp1 = array();
		$temp2 = array();
		$temp3 = array();
		if(!$this->fGroupId[$gId]){
			return;
		}
		$count = $this->pGroups[$gId] ? $this->pCount - 1 : $this->pCount;
		// идем по массиву списка выбранных параметров Array([261] => 86)
		foreach($this->pItems as $k => $v){
			// значения без учета текущей группы 
			if($v != $gId){
				// идем по (массиву от ID значений фильтра) Array([261] => Array([42536] => 86, [42537] => 86)). Ключ массива - ID конкретного значения конкретного фильтра, в подмассиве продукт - ключ, а значение - id фильтра  
				if($this->fItemId[$k]){
					foreach($this->fItemId[$k] as $k1 => $v1){
						// идем по (массиву от ID продукт) [42536] => Array([261] => 86, [874] => 415, [2295] => 414, [2238] => 418...). Ключ массива - ID продукта, в подмассиве ключ - ID конкретного значения конкретного фильтра, значение - id фильтра  
						if($this->fProdId[$k1]){
							foreach($this->fProdId[$k1] as $k2 => $v2){
								if ($this->config['show_slider']){
									$price = $this->fPrProdId[$k1];
									if (($price >= $this->prFrom) && ($price <= $this->prTo)){
										++$temp1[$v][$k1][$k2];
									}
								} else {
									++$temp1[$v][$k1][$k2];
								}
							}
						}
					}
				}
			}
		}
		if($temp1){
			foreach($temp1 as $v){
				foreach($v as $k1 => $v1){
					++$temp2[$k1];
				}
			}
			foreach($temp2 as $k => $v){
				if($v < $count){
					unset($temp2[$k]);
				}
			}
		}
		if($temp1){
			$group = array_shift($temp1);
			foreach($group as $k => $v){
				if($temp2[$k]){
					foreach($v as $k1 => $v1){
						++$temp3[$k1];
					}
				}
			}
			foreach($this->fGroupId[$gId] as $k => $v){
				$this->fGroupId[$gId][$k] = $temp3[$k] ? $temp3[$k] : 0;
			}
		}
		return $this->fGroupId[$gId];
    }
	
	function showSlider(){
		$ret = '';
		if($this->config['show_slider']){
			 $ret .= '<div class="one_filter opened" id="one_fil_slider">
				<div class="title">
				    <span>Цена</span><i></i>
				</div>
				<div class="filter_box">
					<input type="hidden" name="min_price" value="'.(int)$_REQUEST['min_price'].'" />
					<input type="hidden" name="max_price" value="'.ceil($_REQUEST['max_price']).'" />
					<div class="price_input">
						от <input type="text" name="price_from" value="'.$this->pMinc.'" /> до <input type="text" name="price_to" value="'.$this->pMaxc.'" /> руб./м.
					</div>
					<div class="price_lider_box">
						<div id="price_slider_contener"></div>
					</div>
				</div>
			</div>';
		}
		return $ret;
	}
	
	function getCategories($id){
		static $cat = array();
		if(!$cat){
			/*
			 * $cat[0] - params for filters arrays
			 * $cat[1][0] - params array by Product Id (fProdId)
			 * $cat[1][1] - params array by Price product Id (fPrProdId)
			 * $cat[1][2] - params by Name (fName)
			 */
			if(!$cat = $this->memcacheGet(__FUNCTION__.'_'.$id)){
				/*
				 * 0 - param_item_id
				 * 1 - prod_id
				 * 2 - param_group_id
				 * 3 - price
				 * 4 - name
				 */
				$r = $this->Db->query('SELECT
					'.$this->config['prod_parametr_links_pref'].$this->config['parametr_items_pref'].'id,
					'.$this->config['prod_parametr_links_pref'].$this->config['product_pref'].'id,
					'.$this->config['prod_parametr_links_pref'].$this->config['parametr_groups_pref'].'id,
					'.$this->config['product_other_pref'].'price,
					'.$this->config['parametr_items_pref'].'name
					FROM '.$this->config['product_table'].'
					INNER JOIN '.$this->config['product_other'].' ON('.$this->config['product_pref'].'id='.$this->config['product_other_pref'].$this->config['product_pref'].'id)
					INNER JOIN '.$this->config['prod_parametr_links'].' ON('.$this->config['product_pref'].'id='.$this->config['prod_parametr_links_pref'].$this->config['product_pref'].'id)
					LEFT JOIN '.$this->config['parametr_items'].' ON('.$this->config['parametr_items_pref'].'id='.$this->config['prod_parametr_links_pref'].$this->config['parametr_items_pref'].'id)
					WHERE '.$this->config['product_pref'].'active=1 AND '.$this->config['prod_parametr_links_pref'].'is_filter=1 AND '.$this->config['prod_parametr_links_pref'].$this->config['catalog_pref'].'id=?
					ORDER BY '.$this->config['parametr_items_pref'].'priority',
					array($id), PDO::FETCH_NUM);
				$s = sizeof($r);
				for($i = 0; $i < $s; ++$i){
					$cat[0][] = $r[$i];
					$cat[1][1][$r[$i][1]] = $r[$i][3];
					if($r[$i][0]){
						$cat[1][0][$r[$i][1]][$r[$i][0]] = $r[$i][2];
						$cat[1][2][$r[$i][0]] = $r[$i][4];
					}
				}
				$this->memcacheSet(__FUNCTION__.'_'.$id, $cat, 'Product');
			}
			$this->fProdId = $cat[1][0];
			$this->fPrProdId = $cat[1][1];
			$this->fName = $cat[1][2];
		}
		return $cat[0];
	}

	function getProdFilters(){
		$sFilters = $ret = array();
		if(!$this->current['category_id']){
			return false;
		}
		if($this->config['show_slider']){
			if((int)$_REQUEST['min']){
				$this->pMinc = $this->prFrom = (int)$_REQUEST['price_from'];
			}
			if((int)$_REQUEST['max']){
				$this->pMaxc = $this->prTo = ceil($_REQUEST['price_to']);
			}	
		}
		$cat = $this->getCategories($this->current['id']);
		$s = sizeof($cat);
		for($i = 0; $i < $s; ++$i){
			if($cat[$i][0]){
				if($this->pItems[$cat[$i][0]]){
					$this->fItemId[$cat[$i][0]][$cat[$i][1]] = $cat[$i][2];
				}
				if ($this->config['show_slider']){
					if (($cat[$i][3] >= $this->prFrom) && ($cat[$i][3] <= $this->prTo)){
						++$this->fGroupId[$cat[$i][2]][$cat[$i][0]];
					} else {
						$this->fGroupId[$cat[$i][2]][$cat[$i][0]] += 0;
					}
				} else {
					++$this->fGroupId[$cat[$i][2]][$cat[$i][0]];
				}
			}
		}
		unset($cat);
		$r = $this->Db->query('SELECT
			'.$this->config['parametr_groups_pref'].'name AS name,
			IF('.$this->config['parametr_groups_pref'].'alias_of=0,'.$this->config['parametr_groups_pref'].'id,'.$this->config['parametr_groups_pref'].'alias_of) AS id
			FROM '.$this->config['parametr_groups'].'
			WHERE '.$this->config['parametr_groups_pref'].$this->config['parametr_categories_pref'].'id=? AND '.$this->config['parametr_groups_pref'].'is_filter=1
			ORDER BY '.$this->config['parametr_groups_pref'].'priority', array($this->current['category_id']));
		if($r){
			$pcntAr = array();
			$s = sizeof($r);
			for($i = 0; $i < $s; ++$i){
				$this->groups[$r[$i]['id']] = $r[$i]['name'];
				$items = $this->getGroupFilters($r[$i]['id']);
				if($items){
					foreach($items as $k => $v){
						if ($this->pItems[$k] == $r[$i]['id']){
							if ($v){
								$ret[$r[$i]['id']][$k][0] = 1;
								$ret[$r[$i]['id']][$k][1] = $v;
								$pcntAr[0][$r[$i]['id']] += $v;
								$sFilters[] = $this->fName[$k];
							} else {
								$ret[$r[$i]['id']][$k][0] = 2;
								$ret[$r[$i]['id']][$k][1] = 0;
							}
						} elseif($this->fName[$k]){
							if($v){
								$ret[$r[$i]['id']][$k][0] = 0;
								$ret[$r[$i]['id']][$k][1] = $v;
								$pcntAr[1][$r[$i]['id']] += $v;
							} else {
								$ret[$r[$i]['id']][$k][0] = 2;
								$ret[$r[$i]['id']][$k][1] = 0;
							}
						}
					}
				}
			}
		}
		if(!$this->filters){
			$pcntAr[0] = array();
			foreach ($this->fPrProdId as $k => $v){
				if ($this->config['show_slider']){
					if (($v >= $this->prFrom) && ($v <= $this->prTo)){
						++$pcntAr[0][0];
					}
				} else {
					++$pcntAr[0][0];
				}
			}
		}
		$pa = array();
		if($pcntAr[0]){
			$pa[0] = max($pcntAr[0]);
		}
		if($pcntAr[1]){
			$pa[1] = max($pcntAr[1]);
		}
		$pcnt = (int)$pa[0] ? $pa[0] : (int)$pa[1];
		$href = '/catalog'.$this->current['alias'].'/';
		$add = array();
		$filters = $this->filters;
		if($filters || ($this->prTo != ceil($_REQUEST['max_price']) || $this->prFrom != (int)$_REQUEST['min_price'])){
			$add['filters'] .= 'fl=';
			foreach ($filters as $k => $v){
				$add['filters'] .= 'f'.$k;
				foreach ($v as $v1){
					$add['filters'] .= '_'.$v1;
				}
			}
			if($this->prTo != ceil($_REQUEST['max_price']) || $this->prFrom != (int)$_REQUEST['min_price']){
				$add['filters'] .= 'p'.$this->prFrom.'_'.$this->prTo;
			}
		}
		if($add) {
			$href .= '?'.implode('&', $add);
		}
		
		$this->href = $href;
		$this->pcnt = $pcnt;
		$this->sf = $sFilters;
		return $ret;
	}
	
	function getHtml($groups, $array, $prices){
		$ret = '';
		if($groups){
			$add = array();
			$ret .= '<input type="hidden" name="sort" value="'.$_REQUEST['sort'].'" />';
			$addHref = '&'.implode('&', $add);

			$ret .= '
				<div id="selected_filter" class="one_filter" style="display: none">
					<div class="filter_box">
						<a class="clear_filtr clear" href="#">Очистить фильтры</a>
					</div>
				</div>';
			if($this->config['show_slider']){
				list($this->pMinc, $this->pMaxc) = $prices;
				if((int)$_REQUEST['min']){
					$this->prFrom = (int)$_REQUEST['price_from'];
				}
				if((int)$_REQUEST['max']){
					$this->prTo = ceil($_REQUEST['price_to']);
				}	
			}
			if(!$this->config['slider_after']){
				$ret .= $this->showSlider();
			}
			if(!$this->fName){
				$this->getCategories((int)$_REQUEST['catId']);
			}
			foreach ($array as $k => $v){
				$ret .= '<div class="one_filter opened" id="one_fil_'.$k.'">
							<div class="title">
								<span>'.$groups[$k].'</span><i></i>
							</div>
							<ul class="filter_box">';
				$addfilt = '';
				$filt = $this->filters;
				unset($filt[$k]);
				foreach ($filt as $k1 => $v1){
					$addfilt .= 'f'.$k1.'_'.implode('_', $v1);
				}
				if(((int)$_REQUEST['min'] && $this->prFrom != (int)$_REQUEST['min_price']) || ((int)$_REQUEST['max'] && $this->prTo != ceil($_REQUEST['max_price']))){
					$addfilt .= 'p'.$this->prFrom.'_'.$this->prTo;
				}
				$bool = false;
				foreach ($v as $k1 => $v1){
					$href = '/catalog'.$this->getAlias((int)$_REQUEST['catId']).'/?fl=f'.$k.'_'.$k1.$addfilt.$addHref;
					$type = '';
					if($v1[0] == 1){
						$type = ' checked';
						$bool = true;
					} elseif($v1[0] == 2){
						$type = ' disabled';
					}
					$ret .= '
								<li class="item'.$type.'" data-item="'.$k1.'">
									<input type="checkbox" name="filters['.$k.']['.$k1.']" value="1" id="filters'.$k.'_'.$k1.'"'.$type.' />
									<label for="filters'.$k.'_'.$k1.'"><span></span><a href="'.$href.'" class="'.$type.'">'.$this->fName[$k1].'</a><b>'.$v1[1].'</b></label>
								</li>';
				}
				$ret .= '
							</ul>';
				/*$ret .= '
							<a href="#" class="clear"'.(!$bool ? ' style="display:none;"' : '').' data-index="'.$k.'">Отменить фильтр</a>*/
				$ret .= '
						</div>';
				if($this->config['slider_after']){
					$ret .= $this->showSlider();
					--$this->config['slider_after'];
				}
			}
			if($this->config['slider_after']){
				$ret .= $this->showSlider();
			}
			/*$ret .= '
						<div class="filtr_bot">
							<button type="button"><span>Показать '.$pcnt.' '.(!($pcnt > 10 && $pcnt < 20) && $pcnt % 10 < 5 ? ($pcnt % 10 == 1 ? ' модель' : ' модели') : ' моделей').'</span></button>
						</div>';*/
		}
		return $ret;
	}
}

$ret = array();
$Core = new Core(Core::CONNECTED);
$F = $Core->getClass('getFilters');
$sArr = array((int)$_REQUEST['catId'], json_encode($_REQUEST['filters']), (int)$_REQUEST['price_from'], (int)$_REQUEST['price_to']);
$r = $Core->getClass('Db')->queryOne('SELECT fc_data AS data FROM filter_cache WHERE fc_ca_id=? AND fc_params=? AND fc_price_from=? AND fc_price_to=?',
		$sArr);
if($r){
	$ret = json_decode($r['data'], true);
} else {
	$ret['data'] = $F->process();
	$ret['pcnt'] = $F->pcnt;
	$ret['href'] = $F->href;
	$ret['selected_filters'] = $F->sf;
	$ret['prices'] = array($F->pMinc, $F->pMaxc);
	$ret['groups'] = $F->groups;
	$sArr[] = json_encode($ret);
	$Core->getClass('Db')->queryInsert('INSERT INTO filter_cache (fc_ca_id, fc_params, fc_price_from, fc_price_to, fc_data) VALUES (?, ?, ?, ?, ?)',
			$sArr);
}
if($_REQUEST['sort']){
	$pos = strpos($ret['href'], '?');
	if($pos === false){
		$ret['href'] .= '?';
	} elseif($pos < strlen($ret['href'])){
		$ret['href'] .= '&';
	}
	$ret['href'] .= 'sort='.htmlentities($_REQUEST['sort']);
}
if(!(int)$_REQUEST['json'] && $ret['data']){
	$ret['data'] = $F->getHtml($ret['groups'], $ret['data'], $ret['prices']);
}
unset($ret['groups']);
$ret['errors'] = ob_get_clean();
echo json_encode($ret);
?>