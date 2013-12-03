<?php
namespace catalog{
	class Filters extends \CContent{

		const IS_FILTER = 1, IS_BASE = 2, IS_ALTERNAT = 4;

		protected $config = array(
			'admin' => 'catalog',
			'parametr_groups' => 'filter_parametr_groups',
			'parametr_groups_pref' => 'fpg_',
			'parametr_items' => 'filter_parametr_items',
			'parametr_items_pref' => 'fpi_',
			'parametr_categories' => 'filter_parametr_categories',
			'parametr_categories_pref' => 'fpc_',
			'prod_parametr_links' => 'filter_prod_parametr_links',
			'prod_parametr_links_pref' => 'fppl_',
			'product_pref' => 'pr_',
			'catalog_pref' => 'ca_',
			'header' => 'Редактирование фильтров',
			'files_path' => '/files/filters',
			'reqId' => 'id'
		);

		function __construct($m = '') {
			parent::__construct(__CLASS__, $m);
		}

		function parseRequest($request) {
			return array();
		}

		function getParams($cond, $par = 0){
			$ret = array();
			$arr = array();
			$idCond = '';
			if(is_array($cond)){
				$arr = array_values($cond);
				$idCond = ' IN('.substr(str_repeat('?,', sizeof($cond)), 0, -1).')';
			} elseif((int)$cond){
				$arr[] = $cond;
				$idCond = '=?';
			}
			$addCond = array();
			if($par & self::IS_FILTER){
				$addCond[] = $this->config['prod_parametr_links_pref'].'is_filter=?';
				$arr[] = 1;
			}
			if($par & self::IS_BASE){
				$addCond[] = $this->config['parametr_groups_pref'].'is_base=?';
				$arr[] = 1;
			}
			if($par & self::IS_ALTERNAT){
				$addCond[] = $this->config['parametr_groups_pref'].'is_alternat=?';
				$arr[] = 1;
			}
			$r = $this->Db->query('SELECT
				'.$this->config['prod_parametr_links_pref'].$this->config['product_pref'].'id AS prod_id,
				'.$this->config['prod_parametr_links_pref'].$this->config['parametr_groups_pref'].'id AS group_id,
				'.$this->config['prod_parametr_links_pref'].$this->config['parametr_items_pref'].'id AS item_id,
				'.$this->config['parametr_items_pref'].'name AS item_name,
				'.$this->config['parametr_items_pref'].'img_src AS img_src,
				'.$this->config['parametr_groups_pref'].'name AS group_name
				FROM '.$this->config['prod_parametr_links'].'
				INNER JOIN '.$this->config['parametr_items'].' ON ('.$this->config['prod_parametr_links_pref'].$this->config['parametr_items_pref'].'id='.$this->config['parametr_items_pref'].'id)
				INNER JOIN '.$this->config['parametr_groups'].' ON ('.$this->config['prod_parametr_links_pref'].$this->config['parametr_groups_pref'].'id='.$this->config['parametr_groups_pref'].'id)
				WHERE '.$this->config['prod_parametr_links_pref'].$this->config['parametr_items_pref'].'id!=0 AND ('.$this->config['prod_parametr_links_pref'].$this->config['product_pref'].'id'.$idCond.')'.($addCond ? ' AND '.implode(' AND ', $addCond) : '').'
				ORDER BY '.$this->config['parametr_groups_pref'].'priority, '.$this->config['parametr_items_pref'].'priority', $arr);
			if(is_array($cond)){
				foreach ($r as $v){
					$ret[$v['prod_id']][$v['group_id']]['name'] = $v['group_name'];
					$ret[$v['prod_id']][$v['group_id']]['items'][$v['item_id']][] = $v['item_name'];
					if($v['img_src']){
						$ret[$v['prod_id']][$v['group_id']]['items'][$v['item_id']][] = $v['img_src'];
					}
				}
			} elseif((int)$cond){
				foreach ($r as $v){
					$ret[$v['group_id']]['name'] = $v['group_name'];
					$ret[$v['group_id']]['items'][$v['item_id']][] = $v['item_name'];
					if($v['img_src']){
						$ret[$v['group_id']]['items'][$v['item_id']][] = $v['img_src'];
					}
				}
			}
			return $ret;
		}
		
		function getParametrItems($data = array(), $cond = array(), $order = array(), $getBy = '', $limit = false) {
			return parent::getItems($data, $cond, $order, $getBy, $this->config['prod_parametr_links'], $this->config['prod_parametr_links_pref'], $limit);
		}
	}
}
?>
