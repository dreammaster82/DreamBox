<?php
namespace admin{
	class Filters extends CContent{
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
			'product_table' => 'product',
			'catalog_pref' => 'ca_',
			'cache_table' => 'filter_cache',
			'header' => 'Редактирование фильтров',
		);

		private $types = array(
			'0' => array(
				'id' => 0,
				'name' => 'укажите тип',
			),
			'1' => array(
				'id' => 1,
				'name' => 'text',
			),
			'2' => array(
				'id' => 2,
				'name' => 'select',
			),
			'3' => array(
				'id' => 3,
				'name' => 'checkbox',
			),
			'4' => array(
				'id' => 4,
				'name' => 'interval',
			),
			'5' => array(
				'id' => 5,
				'name' => 'autoselect',
			),
			'6' => array(
				'id' => 6,
				'name' => 'header',
			)
		);

		public $ret = array();

		function __construct() {
			parent::__construct(__CLASS__);
			$this->path = MODULES_PATH.'/catalog';
		}

		function adminCategories($item){
			$ret = array();
			$ret[] = array('type' => 'link', 'link' => '?cl=Filters&action=showGroups&categoryId='.$item['id'], 'text' => '<img src="/admin/images/admin_move.gif" width="16" height="16" border="0" alt="Параметры категории" />');
			$ret[] = array('type' => 'link', 'link' => '?cl=Filters&action=deleteItem&function=deleteOptionCategories&return_function=showOptionCategories&what='.$item['id'], 'text' => '<img src="/admin/images/admin_delete.gif" width="16" height="16" border="0" alt="Удалить" />');
			$ret[] = array('type' => 'link', 'link' => '?cl=Filters&action=editOptionCategories&categoryId='.$item['id'], 'text' => '<img src="/admin/images/admin_edit.gif" width="16" height="16" border="0" alt="Редактировать" />');
			return $ret;
		}

		function adminGroups($item, $ctgId = 0){
			$ret = $addArr = array();
			$add = '';
			if($ctgId){
				$addArr[] = 'categoryId='.$ctgId;
			}
			if($addArr){
				$add .= '&'.implode('&', $addArr);
			}
			if($item['type'] != 1 && $item['type'] != 6 && !$item['alias_of']){
				$ret[] = array('type' => 'link', 'link' => '?cl=Filters&action=showItems&groupId='.$item['id'].$add, 'text' => '<img src="/admin/images/admin_move.gif" width="16" height="16" border="0" alt="Элементы фильтра" />');
			}
			$ret[] = array('type' => 'link', 'link' => '?cl=Filters&action=deleteItem&function=deleteGroup&return_function=showGroups&what='.$item['id'].$add, 'text' => '<img src="/admin/images/admin_delete.gif" width="16" height="16" border="0" alt="Удалить" />');
			$ret[] = array('type' => 'link', 'link' => '?cl=Filters&action=editGroup&groupId='.$item['id'].$add, 'text' => '<img src="/admin/images/admin_edit.gif" width="16" height="16" border="0" alt="Редактировать" />');
			$ret[] = array('type' => 'link', 'link' => '?cl=Filters&action=priorityGroups&pr=0&groupId='.$item['id'].$add, 'text' => '<img src="/admin/images/admin_up.gif" width="16" height="16" border="0" alt="Увеличить приоритет" />');
			$ret[] = array('type' => 'link', 'link' => '?cl=Filters&action=priorityGroups&pr=1&groupId='.$item['id'].$add, 'text' => '<img src="/admin/images/admin_down.gif" width="16" height="16" border="0" alt="Уменьшить приоритет" />');
			return $ret;
		}

		function adminLinks($item, $grId = 0, $ctgId = 0){
			$ret = $addArr = array();
			$add = '';
			if($ctgId){
				$addArr[] = 'categoryId='.$ctgId;
			}
			if($addArr){
				$add .= '&'.implode('&', $addArr);
			}
			$ret[] = array('type' => 'link', 'link' => '?cl=Filters&action=deleteItem&function=deleteItem2&return_function=showItems&groupId='.$grId.'&what='.$item['id'].$add, 'text' => '<img src="/admin/images/admin_delete.gif" width="16" height="16" border="0" alt="Удалить" />');
			$ret[] = array('type' => 'link', 'link' => '?cl=Filters&action=editItem&groupId='.$grId.'&what='.$item['id'].$add, 'text' => '<img src="/admin/images/admin_edit.gif" width="16" height="16" border="0" alt="Редактировать" />');
			$ret[] = array('type' => 'link', 'link' => '?cl=Filters&action=priorityItem&pr=0&groupId='.$grId.'&what='.$item['id'].$add, 'text' => '<img src="/admin/images/admin_up.gif" width="16" height="16" border="0" alt="Увеличить приоритет" />');
			$ret[] = array('type' => 'link', 'link' => '?cl=Filters&action=priorityItem&pr=1&groupId='.$grId.'&what='.$item['id'].$add, 'text' => '<img src="/admin/images/admin_down.gif" width="16" height="16" border="0" alt="Уменьшить приоритет" />');
			return $ret;
		}
		
		function clearFilterCache($catId){
			$this->Db->queryExec('DELETE FROM '.$this->config['cache_table'].' WHERE fc_ca_id=?', array($catId));
		}

		function deleteGroup(){
			if(!(int)$_REQUEST['what']){
				return $this->showGroups();
			}
			$this->Db->queryExec('DELETE FROM '.$this->config['parametr_groups'].' WHERE '.$this->config['parametr_groups_pref'].'id=?', array((int)$_REQUEST['what']));
			$this->Db->queryExec('DELETE FROM '.$this->config['parametr_items'].' WHERE '.$this->config['parametr_items_pref'].$this->config['parametr_groups_pref'].'id=?', array((int)$_REQUEST['what']));
			// удаляем линки на группу параметров в таблицах каталога
			$this->Db->queryExec('DELETE FROM '.$this->config['prod_parametr_links'].' WHERE '.$this->config['prod_parametr_links_pref'].$this->config['parametr_groups_pref'].'id=?', array((int)$_REQUEST['what']));
			$this->Db->queryExec('TRUNCATE TABLE '.$this->config['cache_table']);
			$this->Util->memcacheFlush('Product');
			return $this->showGroups();			
		}

		function deleteItem(){
			if((int)$_REQUEST['what']){
				$opt = $return = array();
				$return[] = 'action='.$_REQUEST['return_function'];
				$return[] = 'cl=Filters';
				$opt[] = 'cl=Filters';
				$opt[] = 'action='.$_REQUEST['function'];
				$opt[] = 'what='.(int)$_REQUEST['what'];
				if((int)$_REQUEST['groupId']){
					$opt[] = 'groupId='.(int)$_REQUEST['groupId'];
					$return[] = 'groupId='.(int)$_REQUEST['groupId'];
				}
				if((int)$_REQUEST['categoryId']){
					$opt[] = 'categoryId='.(int)$_REQUEST['categoryId'];
					$return[] = 'categoryId='.(int)$_REQUEST['categoryId'];
				}
				$out['options'] = implode('&', $opt);
				$out['return'] = implode('&', $return);
				ob_start();
				include $this->path.'/data/admin/delete_item'.($_REQUEST['cl'] ? '_'.strtolower($_REQUEST['cl']) : '').'.html';
				$ret = ob_get_clean();
			} else {
				$ret = $this->show();
			}
			return $ret;
		}

		function deleteItem2(){
			if(!(int)$_REQUEST['groupId'] || !(int)$_REQUEST['what']){
				return $this->showGroups();
			}
			$this->Db->queryExec('DELETE FROM '.$this->config['parametr_items'].' WHERE '.$this->config['parametr_items_pref'].'id=?', array((int)$_REQUEST['what']));
			// удаляем линки на группу параметров в таблицах каталога
			$this->Db->queryExec('DELETE FROM '.$this->config['prod_parametr_links'].' WHERE '.$this->config['prod_parametr_links_pref'].$this->config['parametr_items_pref'].'id=?', array((int)$_REQUEST['what']));
			$this->Db->queryExec('TRUNCATE TABLE '.$this->config['cache_table']);
			$this->Util->memcacheFlush('Product');
			return $this->showItems();
		}

		function deleteOptionCategories(){
			$this->Db->queryExec('DELETE FROM '.$this->config['parametr_categories'].' WHERE '.$this->config['parametr_categories_pref'].'id=?', array((int)$_REQUEST['what']));
			$this->Db->queryExec('DELETE FROM '.$this->config['parametr_groups'].' WHERE '.$this->config['parametr_groups_pref'].$this->config['parametr_categories_pref'].'id=?', array((int)$_REQUEST['what']));
			// удаляем линки на группу параметров в таблицах каталога
			$this->Db->queryExec('DELETE FROM '.$this->config['prod_parametr_links'].' WHERE '.$this->config['prod_parametr_links_pref'].$this->config['parametr_categories_pref'].'id=?', array((int)$_REQUEST['what']));
			$this->Db->queryExec('TRUNCATE TABLE '.$this->config['cache_table']);
			$this->Util->memcacheFlush('Product');
			return $this->showOptionCategories();
		}

		function editGroup(){
			$out = array();
			if((int)$_REQUEST['groupId']){
				$dTable = array('id', 'name', 'type', 'is_filter', 'is_hidden', 'is_alternat', 'alias_of', 'comment', 'not_index', 'add_title', 'is_base');
				$out['item'] = $this->getItem($dTable, (int)$_REQUEST['groupId'], $this->config['parametr_groups'], $this->config['parametr_groups_pref']);
				$out['item']['name'] = htmlentities($out['item']['name'], ENT_COMPAT | ENT_HTML5, 'UTF-8');
			}
			if((int)$_REQUEST['categoryId']){
				$out['category'] = $this->getItem(array('id', 'name'), (int)$_REQUEST['categoryId'], $this->config['parametr_categories'], $this->config['parametr_categories_pref']);
			}
			if($out['category']['id']){
				$r = $this->getItems(array('id', 'name'), array($this->config['parametr_categories_pref'].'id' => 0), array('priority'), false,
					$this->config['parametr_groups'], $this->config['parametr_groups_pref']);
				$out['groups_options'] = $this->getArrayTreeOpt($r, $out['item']['alias_of']);
			}
			$out['types'] = $this->getArrayTreeOpt($this->types, $out['item']['type']);
			ob_start();
			include $this->path.'/data/admin/edit_group_filters.html';
			$out['content'] = ob_get_clean();
			$add = '';
			$catTxt = 'Глобальные параметры';
			if($out['category']['id']){
				$add .= '&categoryId='.$out['category']['id'];
				$catTxt = $out['category']['name'];
			}
			$out['menu'][] = array('title' => 'Категория', 'link' => '?cl=Filters&action=showGroups'.$add, 'text' => $catTxt);
			$out['menu'][] = array('title' => 'Параметр', 'text' => $out['item']['name']);
			return $this->show($out);
		}

		function editItem(){
			if(!(int)$_REQUEST['groupId']){
				return $this->showGroups();
			}
			$out['group'] = $this->getItem(array('id', 'name'), (int)$_REQUEST['groupId'], $this->config['parametr_groups'], $this->config['parametr_groups_pref']);
			$out['category'] = $this->getItem(array('id', 'name'), (int)$_REQUEST['categoryId'], $this->config['parametr_categories'], $this->config['parametr_categories_pref']);
			if((int)$_REQUEST['what']){
				$out['item'] = $this->getItem(array('id', 'name', 'note', 'img_src'), (int)$_REQUEST['what'], $this->config['parametr_items'], $this->config['parametr_items_pref']);
				$out['item']['name'] = htmlentities($out['item']['name'], ENT_COMPAT | ENT_HTML5, 'UTF-8');
				$out['item']['note'] = htmlentities($out['item']['note'], ENT_COMPAT | ENT_HTML5, 'UTF-8');
			}
			if(!$out['item']){
				$r = $this->getItems(array('id', 'name'), array($this->config['parametr_groups_pref'].'id' => $out['group']['id']), array('priority'),
						'', $this->config['parametr_items'], $this->config['parametr_items_pref']);
				$out['after_options'] = $this->getArrayTreeOpt($r, 0);
			}
			if($out['item']['img_src']){
				$out['path'] = $this->config['files_path'];
				$fr = finfo_open(FILEINFO_MIME_TYPE);
				$fi = finfo_file($fr, CLIENT_PATH.$out['path'].$out['item']['img_src']);
				finfo_close($fr);
				$out['image']['size'] = filesize(CLIENT_PATH.$out['path'].$out['item']['img_src']);
				$out['image']['type'] = $this->fTypes[$fi];
			}
			ob_start();
			include $this->path.'/data/admin/edit_item_filters.html';
			$out['content'] = ob_get_clean();
			$add = '';
			$catTxt = 'Глобальные параметры';
			if($out['category']['id']){
				$add .= '&categoryId='.$out['category']['id'];
				$catTxt = $out['category']['name'];
			}
			$out['menu'][] = array('title' => 'Категория', 'link' => '?cl=Filters&action=showGroups'.$add, 'text' => $catTxt);
			$out['menu'][] = array('title' => 'Параметр', 'link' => '?cl=Filters&action=showItems&groupId='.$out['group']['id'].$add, 'text' => $out['group']['name']);
			$out['menu'][] = array('title' => 'Элемент', 'text' => $out['item']['name']);
			return $this->show($out);
		}

		function editOptionCategories(){
			$out = array();
			if((int)$_REQUEST['categoryId']){
				$out['item'] = $this->getItem(array('id', 'name', 'preffix_name', 'preffix_select', 'preffix_a'), (int)$_REQUEST['categoryId'],
						$this->config['parametr_categories'], $this->config['parametr_categories_pref']);
				$out['item']['name'] = htmlentities($out['item']['name'], ENT_COMPAT | ENT_HTML5, 'UTF-8');
				$out['item']['preffix_a'] = htmlentities($out['item']['preffix_a'], ENT_COMPAT | ENT_HTML5, 'UTF-8');
			}
			ob_start();
			include $this->path.'/data/admin/edit_option_categories_filters.html';
			$out['content'] = ob_get_clean();
			return $this->show($out);
		}

		function getOptions($selId){
			$r = $this->getItems(array('id', 'name'), false, false, false, $this->config['parametr_categories'], $this->config['parametr_categories_pref']);
			return $this->getArrayTreeOpt($r, $selId);
		}

		function priorityGroups(){
			if(!(int)$_REQUEST['groupId']){
				return $this->showGroups();
			}
			$id = (int)$_REQUEST['groupId'];
			$pr = 1;
			$items = $this->getItems(array('id', 'priority'), array($this->config['parametr_categories_pref'].'id' => (int)$_REQUEST['categoryId']),
					array('priority'), false, $this->config['parametr_groups'], $this->config['parametr_groups_pref']);
			foreach($items as $k => $v){
				if($v['id'] == $id){ 
					$pos = $k;
					$pr = $v['priority'];
				}
			}
			if((int)$_REQUEST['pr']){
				$newId = $items[$pos + 1]['id'];
				$newPr = $items[$pos + 1]['priority'];
			} else {
				$newId = $items[$pos - 1]['id'];
				$newPr = $items[$pos - 1]['priority'];
			}
			if($newId){
				$this->Db->queryExec('UPDATE '.$this->config['parametr_groups'].' SET
					'.$this->config['parametr_groups_pref'].'priority=?
					WHERE '.$this->config['parametr_groups_pref'].'id=?',
					array($newPr, $id));
				$this->Db->queryExec('UPDATE '.$this->config['parametr_groups'].' SET
					'.$this->config['parametr_groups_pref'].'priority=?
					WHERE '.$this->config['parametr_groups_pref'].'id=?',
					array($pr, $newId));
			}
			$this->Db->queryExec('TRUNCATE TABLE '.$this->config['cache_table']);
			$this->Util->memcacheFlush('Product');
			return $this->showGroups();
		}

		function priorityItem(){
			if(!(int)$_REQUEST['groupId'] || !(int)$_REQUEST['what']){
				return $this->showGroups();
			}
			$id = (int)$_REQUEST['what'];
			$items = $this->getItems(array('id', 'priority'), array($this->config['parametr_groups_pref'].'id' => (int)$_REQUEST['groupId']), array('priority'),
					'', $this->config['parametr_items'], $this->config['parametr_items_pref']);
			foreach($items as $k => $v){
				if($v['id'] == $id){ 
					$pos = $k;
					$pr = $v['priority'];
				}
			}
			if((int)$_REQUEST['pr']){
				$newId = $items[$pos + 1]['id'];
				$newPr = $items[$pos + 1]['priority'];
			} else {
				$newId = $items[$pos - 1]['id'];
				$newPr = $items[$pos - 1]['priority'];
			}
			if($newId){
				$this->Db->queryExec('UPDATE '.$this->config['parametr_items'].' SET 
					'.$this->config['parametr_items_pref'].'priority=?
					WHERE '.$this->config['parametr_items_pref'].'id=?', array($newPr, $id));
				$this->Db->queryExec('UPDATE '.$this->config['parametr_items'].' SET
					'.$this->config['parametr_items_pref'].'priority=?
					WHERE '.$this->config['parametr_items_pref'].'id=?', array($pr, $newId));
			}
			$this->Db->queryExec('TRUNCATE TABLE '.$this->config['cache_table']);
			$this->Util->memcacheFlush('Product');
			return $this->showItems();
		}

		function saveGroup(){
			$item = array();
			$item['name'] = html_entity_decode(substr(stripslashes(trim($_REQUEST['name'])), 0, 64), ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$item['comment'] = mb_substr(stripslashes(trim($_REQUEST['comment'])), 0, 64, 'UTF-8');
			if(((int)$_REQUEST['categoryId'] && !(int)$_REQUEST['globalGroupId']) && (!$item['name'] || !(int)$_REQUEST['type'])){
				$this->ret['warning'] .= 'Необходимо указать название фильтра и его тип!';
				return $this->editGroup();
			}
			$item['alias_of'] = 0;
			$item['type'] = (int)$_REQUEST['type'];
			$item['is_filter'] = (int)$_REQUEST['type'] == 1 ? 0 : (int)$_REQUEST['is_filter'];
			$item['is_hidden'] = (int)$_REQUEST['is_hidden'];
			$item['is_alternat'] = (int)$_REQUEST['is_alternat'];
			$item['not_index'] = (int)$_REQUEST['not_index'];
			$item['add_title'] = (int)$_REQUEST['add_title'];
			$item['is_base'] = (int)$_REQUEST['is_base'];
			if((int)$_REQUEST['categoryId'] && (int)$_REQUEST['globalGroupId']){
				$item = array_merge($item, $this->getItem(array('id', 'name', 'type', 'is_filter', 'is_hidden', 'is_alternat', 'not_index', 'add_title', 'is_base'), (int)$_REQUEST['globalGroupId'],
						$this->config['parametr_groups'], $this->config['parametr_groups_pref']));
				$item['alias_of'] = (int)$item['id'];
			}
			$r = $this->getItem(array('id'), (int)$_REQUEST['groupId'], $this->config['parametr_groups'], $this->config['parametr_groups_pref']);
			$item['id'] = $r['id'];
			if($item['id']){
				$this->Db->queryExec('UPDATE '.$this->config['parametr_groups'].' SET
					'.$this->config['parametr_groups_pref'].'name=?,
					'.$this->config['parametr_groups_pref'].'type=?,
					'.$this->config['parametr_groups_pref'].'is_filter=?,
					'.$this->config['parametr_groups_pref'].'is_hidden=?,
					'.$this->config['parametr_groups_pref'].'is_alternat=?,
					'.$this->config['parametr_groups_pref'].'not_index=?,
					'.$this->config['parametr_groups_pref'].'add_title=?,
					'.$this->config['parametr_groups_pref'].'is_base=?,
					'.$this->config['parametr_groups_pref'].'alias_of=?,
					'.$this->config['parametr_groups_pref'].'comment=?
					WHERE '.$this->config['parametr_groups_pref'].'id=?', 
					array($item['name'], $item['type'], $item['is_filter'], $item['is_hidden'], $item['is_alternat'], $item['not_index'], $item['add_title'],
						$item['is_base'], $item['alias_of'], $item['comment'], $item['id']));
			} else {
				$r = $this->Db->queryOne('SELECT MAX('.$this->config['parametr_groups_pref'].'priority) as max
					FROM '.$this->config['parametr_groups'].'
					WHERE '.$this->config['parametr_groups_pref'].$this->config['parametr_categories_pref'].'id=?', array((int)$_REQUEST['categoryId']));
				$item['id'] = $this->Db->queryInsert('INSERT INTO '.$this->config['parametr_groups'].'
					('.$this->config['parametr_groups_pref'].$this->config['parametr_categories_pref'].'id,
					'.$this->config['parametr_groups_pref'].'name,
					'.$this->config['parametr_groups_pref'].'type,
					'.$this->config['parametr_groups_pref'].'is_filter,
					'.$this->config['parametr_groups_pref'].'is_hidden,
					'.$this->config['parametr_groups_pref'].'is_alternat,
					'.$this->config['parametr_groups_pref'].'not_index,
					'.$this->config['parametr_groups_pref'].'add_title,
					'.$this->config['parametr_groups_pref'].'is_base,
					'.$this->config['parametr_groups_pref'].'alias_of,
					'.$this->config['parametr_groups_pref'].'comment,
					'.$this->config['parametr_groups_pref'].'priority)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
					array((int)$_REQUEST['categoryId'], $item['name'], $item['type'], $item['is_filter'], $item['is_hidden'], $item['is_alternat'], $item['not_index'], $item['add_title'],
						$item['is_base'], $item['alias_of'], $item['comment'], ++$r['max']));
				if($item['type'] == 5){
					$pr = 1;
					$this->Db->queryExec('INSERT INTO '.$this->config['parametr_items'].'
						('.$this->config['parametr_items_pref'].$this->config['parametr_groups_pref'].'id,
						'.$this->config['parametr_items_pref'].'name,
						'.$this->config['parametr_items_pref'].'priority) VALUES (?,?,?), (?,?,?), (?,?,?), (?,?,?)',
						array($item['id'], '---', $pr, $item['id'], 'есть', $pr + 1, $item['id'], 'нет', $pr + 2, $item['id'], 'н.д.', $pr + 3));	
				}
			}
			if((int)$_REQUEST['categoryId']){
				$id = $item['alias_of'] ? $item['alias_of'] : $item['id'];
				$this->Db->queryExec('UPDATE '.$this->config['prod_parametr_links'].' SET
					'.$this->config['prod_parametr_links_pref'].'is_filter=?
					WHERE '.$this->config['prod_parametr_links_pref'].$this->config['parametr_groups_pref'].'id=?',
					array($item['is_filter'], $id));
			} else {
				$this->Db->queryExec('UPDATE '.$this->config['parametr_groups'].' SET
					'.$this->config['parametr_groups_pref'].'name=?,
					'.$this->config['parametr_groups_pref'].'type=?,
					'.$this->config['parametr_groups_pref'].'is_filter=?,
					'.$this->config['parametr_groups_pref'].'is_alternat=?,
					'.$this->config['parametr_groups_pref'].'is_base=?
					WHERE '.$this->config['parametr_groups_pref'].'alias_of=?', 
					array($item['name'], (int)$_REQUEST['type'], ((int)$_REQUEST['type'] == 1 ? 0 : (int)$_REQUEST['is_filter']), (int)$_REQUEST['is_alternat'], (int)$_REQUEST['is_base'], $item['id']));
			}
			if((int)$_REQUEST['sort_parametr']){
				$itName = $this->getItems(array('id', 'priority'), array($this->config['parametr_groups_pref'].'id' => $item['id']), array('name'), '',
						$this->config['parametr_items'], $this->config['parametr_items_pref']);
				$pr = 0;
				foreach($itName as $k => $v){
					$this->Db->queryExec('UPDATE '.$this->config['parametr_items'].' SET '.$this->config['parametr_items_pref'].'priority=? WHERE '.$this->config['parametr_items_pref'].'id=?',
							array(++$pr, $v['id']));
				}
			}
			$this->Db->queryExec('TRUNCATE TABLE '.$this->config['cache_table']);
			$this->Util->memcacheFlush('Product');
			if($_REQUEST['reload']){
				(int)$_REQUEST['groupId'] = $item['id'];
				return $this->editGroup();
			} else {
				return $this->showGroups();
			}
		}

		function saveItem(){
			if(!(int)$_REQUEST['groupId']){
				return $this->showGroups();
			}

			$name = html_entity_decode(substr(stripslashes(trim($_REQUEST['name'])), 0, 64), ENT_COMPAT | ENT_HTML5, 'UTF-8');
			if(!$name){
				$this->ret['warning'] = 'Необходимо указать название элемента фильтра!';
				return $this->editGlobalItem();
			}
			$note = html_entity_decode(substr(stripslashes(trim($_REQUEST['note'])), 0, 64), ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$item = $this->getItem(array('id'), (int)$_REQUEST['what'], $this->config['parametr_items'], $this->config['parametr_items_pref']);
			if($item['id']){
				$this->Db->queryExec('UPDATE '.$this->config['parametr_items'].' SET
					'.$this->config['parametr_items_pref'].'name=?,
					'.$this->config['parametr_items_pref'].'note=?
					WHERE '.$this->config['parametr_items_pref'].'id=?', array($name, $note, $item['id']));
			} else {
				$r = $this->Db->queryOne('SELECT MAX('.$this->config['parametr_items_pref'].'priority) AS max
					FROM '.$this->config['parametr_items'].'
					WHERE '.$this->config['parametr_items_pref'].$this->config['parametr_groups_pref'].'id=?',
					array((int)$_REQUEST['groupId']));
				$pr = $r['max'] + 1;
				if((int)$_REQUEST['after_id']){
					$temp = $this->getItem(array('priority'), (int)$_REQUEST['after_id'], $this->config['parametr_items'], $this->config['parametr_items_pref']);
					if($temp['priority']){
						$pr  = $temp['priority'];
						$this->Db->queryExec('UPDATE '.$this->config['parametr_items'].' SET
							'.$this->config['parametr_items_pref'].'priority='.$this->config['parametr_items_pref'].'priority+1
							WHERE '.$this->config['parametr_items_pref'].$this->config['parametr_groups_pref'].'id=? AND '.$this->config['parametr_items_pref'].'priority>?',
							array((int)$_REQUEST['groupId'], $pr));
						++$pr;
					}
				}
				$item['id'] = $this->Db->queryInsert('INSERT INTO '.$this->config['parametr_items'].'
					('.$this->config['parametr_items_pref'].$this->config['parametr_groups_pref'].'id,
					'.$this->config['parametr_items_pref'].'name,
					'.$this->config['parametr_items_pref'].'note,
					'.$this->config['parametr_items_pref'].'priority)
					VALUES (?, ?, ?, ?)', 
				array((int)$_REQUEST['groupId'], $name, $note, $pr));	
			}
			if($_FILES['header']['size']>0){
				$file = $_FILES['header'];	
				if($this->fTypes[$file['type']]){
					$ext = $this->fTypes[$file['type']];
					$imgSrc = '/'.$item['id'].'/img_src.'.$ext;
					$filePath = $this->config['files_path'].$imgSrc;
					if(!is_dir(CLIENT_PATH.$this->config['files_path'].'/'.$item['id'])){
						mkdir(CLIENT_PATH.$this->config['files_path'].'/'.$item['id'], 0775, true);
					}
					$r = $this->getItem($item['id'], array('img_src'), $this->config['parametr_items'], $this->config['parametr_items_pref']);
					if($r['img_src']){
						$this->deleteImage($this->config['files_path'].$r['img_src']);
					}
					copy($file['tmp_name'], CLIENT_PATH.$filePath);
					$this->Db->queryExec('UPDATE '.$this->config['parametr_items'].' SET '.$this->config['parametr_items_pref'].'img_src=? WHERE '.$this->config['parametr_items_pref'].'id=?',
							array($imgSrc, $item['id']));
				} else {
					$this->ret['warning'] .= 'неизвестный тип файла: '.$file['type'];
				}
			}
			if($_REQUEST['delete_file']){
				$r = $this->getItem(array('img_src'), $item['id'], $this->config['parametr_items'], $this->config['parametr_items_pref']);
				if($r['img_src']){
					$this->deleteImage($this->config['files_path'].$r['img_src']);
					$this->Db->queryExec('UPDATE '.$this->config['parametr_items'].' SET '.$this->config['parametr_items_pref'].'img_src="" WHERE '.$this->config['parametr_items_pref'].'id=?',
							array($item['id']));
				}
			}
			$this->Db->queryExec('TRUNCATE TABLE '.$this->config['cache_table']);
			$this->Util->memcacheFlush('Product');
			if($_REQUEST['reload']){
				$_REQUEST['what'] = $item['id'];
				return $this->editItem();
			} else {
				return $this->showItems();
			}
		}

		function saveOptionCategories(){
			$name = substr(stripslashes(trim($_REQUEST['name'])), 0, 48);
			if(!$name){
				$OUT['warning'] = 'Необходимо указать название категории!';
				return $this->editOptionCategories();
			}
			$name = html_entity_decode($name, ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$preName = html_entity_decode(substr(stripslashes(trim($_REQUEST['preffix_name'])), 0, 48), ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$preSel = html_entity_decode(substr(stripslashes(trim($_REQUEST['preffix_select'])), 0, 48), ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$preA = html_entity_decode(substr(stripslashes(trim($_REQUEST['preffix_a'])), 0, 48), ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$item = $this->getItem(array('id'), (int)$_REQUEST['categoryId'], $this->config['parametr_categories'], $this->config['parametr_categories_pref']);
			if($item['id']){
				$this->Db->queryExec('UPDATE '.$this->config['parametr_categories'].' SET
					'.$this->config['parametr_categories_pref'].'name=?,
					'.$this->config['parametr_categories_pref'].'preffix_name=?,
					'.$this->config['parametr_categories_pref'].'preffix_select=?,
					'.$this->config['parametr_categories_pref'].'preffix_a=?
					WHERE '.$this->config['parametr_categories_pref'].'id=?',
					array($name, $preName, $preSel, $preA, $item['id']));
			} else {
				$item['id'] = $this->Db->queryInsert('INSERT INTO '.$this->config['parametr_categories'].'
					('.$this->config['parametr_categories_pref'].'name,
					'.$this->config['parametr_categories_pref'].'preffix_name,
					'.$this->config['parametr_categories_pref'].'preffix_select,
					'.$this->config['parametr_categories_pref'].'preffix_a)
					VALUES (?, ?, ?, ?)',
					array($name, $preName, $preSel, $preA));	
			}
			$this->Db->queryExec('TRUNCATE TABLE '.$this->config['cache_table']);
			$this->Util->memcacheFlush('Product');
			if($_REQUEST['reload']){
				$_REQUEST['categoryId'] = $item['id'];
				return $this->editOptionCategories();
			} else {
				return $this->showOptionCategories();
			}
		}

		function saveOptions($ctgId, $iId, $catId){
			$this->Db->queryExec('DELETE FROM '.$this->config['prod_parametr_links'].'
				WHERE '.$this->config['prod_parametr_links_pref'].$this->config['product_pref'].'id=?',
				array($iId));
			if($ctgId){
				if($_REQUEST['params'] && is_array($_REQUEST['params'])){
					$param = array();
					$r = $this->getItems(array('id', 'type', 'is_filter', 'alias_of'), false, false, false, $this->config['parametr_groups'], $this->config['parametr_groups_pref']);
					foreach ($r as $v){
						$param[$v['id']] = $v;
					}
					foreach($_REQUEST['params'] as $k => $v){
						$itemId = $isFilter = 0;
						$parId = $param[$k]['alias_of'] ? $param[$k]['alias_of'] : $k;
						if($param[$parId]['is_filter'] && ($param[$parId]['type'] > 1)){
							$isFilter = 1;
						}
						foreach ($v as $v1){
							if($param[$parId]['type'] == 1){
								$v1 = stripslashes(trim($v1));
							} elseif($param[$parId]['type'] == 6){
								$v1 = '';
							} else {
								$itemId = (int)$v1;
								$v1 = '';
							}
							$this->Db->queryExec('INSERT INTO '.$this->config['prod_parametr_links'].'
								('.$this->config['prod_parametr_links_pref'].$this->config['product_pref'].'id,
								'.$this->config['prod_parametr_links_pref'].$this->config['catalog_pref'].'id,
								'.$this->config['prod_parametr_links_pref'].$this->config['parametr_categories_pref'].'id,
								'.$this->config['prod_parametr_links_pref'].$this->config['parametr_groups_pref'].'id,
								'.$this->config['prod_parametr_links_pref'].$this->config['parametr_items_pref'].'id,
								'.$this->config['prod_parametr_links_pref'].'is_filter,
								'.$this->config['prod_parametr_links_pref'].'value,
								'.$this->config['prod_parametr_links_pref'].'self_group_id)
								VALUES(?, ?, ?, ?, ?, ?, ?, ?)',
								array($iId, $catId, $ctgId, $parId, $itemId, $isFilter, $v1, $k));	
						}
					}
				}
			}
		}

		function show($out = array()){
			$this->ret['title'] = $this->config['header'];
			ob_start();
			include $this->path.'/data/admin/show_filters.html';
			return ob_get_clean();
		}

		function showGroups(){
			$out = array();
			$tData = array('id', 'name', 'type', 'is_filter', 'is_hidden', 'alias_of');
			$out['items'] = $this->getItems($tData, array($this->config['parametr_categories_pref'].'id' => (int)$_REQUEST['categoryId']), array('priority'), false, $this->config['parametr_groups'], $this->config['parametr_groups_pref']);
			if((int)$_REQUEST['categoryId']){
				$out['category'] = $this->getItem(array('id', 'name'), (int)$_REQUEST['categoryId'], $this->config['parametr_categories'], $this->config['parametr_categories_pref']);
			}
			ob_start();
			include $this->path.'/data/admin/show_groups_filters.html';
			$out['content'] = ob_get_clean();
			$add = '';
			$catTxt = 'Глобальные параметры';
			if($out['category']['id']){
				$add .= '&categoryId='.$out['category']['id'];
				$catTxt = $out['category']['name'];
			}
			$out['menu'][] = array('title' => 'Категория', 'link' => '?m=Filters&action=showGroups'.$add, 'text' => $catTxt);
			return $this->show($out);
		}

		function showItems($items = array()){
			$out = array();
			if(!(int)$_REQUEST['groupId']){
				return $this->showGroups();
			}
			if((int)$_REQUEST['categoryId']){
				$out['category'] = $this->getItem(array('id', 'name'), (int)$_REQUEST['categoryId'], $this->config['parametr_categories'], $this->config['parametr_categories_pref']);
			}
			$out['group'] = $this->getItem(array('id', 'name'), (int)$_REQUEST['groupId'], $this->config['parametr_groups'], $this->config['parametr_groups_pref']);
			$out['items'] = $items ? $items : $this->getItems(array('id', 'name'), array($this->config['parametr_groups_pref'].'id' => (int)$_REQUEST['groupId']), array('priority'),
					'', $this->config['parametr_items'], $this->config['parametr_items_pref']);
			ob_start();
			include $this->path.'/data/admin/show_items_filters.html';
			$out['content'] = ob_get_clean();
			$add = '';
			$catTxt = 'Глобальные параметры';
			if($out['category']['id']){
				$add .= '&categoryId='.$out['category']['id'];
				$catTxt = $out['category']['name'];
			}
			$out['menu'][] = array('title' => 'Категория', 'link' => '?m=Filters&action=showGroups'.$add, 'text' => $catTxt);
			$out['menu'][] = array('title' => 'Параметр', 'link' => '?m=Filters&action=showItems&groupId='.$out['group']['id'].$add, 'text' => $out['group']['name']);
			return $this->show($out);
		}

		function showOptionCategories(){
			$out = array();
			$tData = array('id', 'name');
			$out['items'] = $this->getItems($tData, false, array('name'), '', $this->config['parametr_categories'], $this->config['parametr_categories_pref']);
			ob_start();
			include $this->path.'/data/admin/show_option_categories_filters.html';
			$out['content'] = ob_get_clean();
			return $this->show($out);
		}

		function showOptions($catId, $itemId){
			$out = array();
			if($catId){
				$tData = array($this->config['parametr_items_pref'].'id' => 'param_id', 'self_group_id' => 'group_id', 'value');
				$r = $this->getItems($tData, array($this->config['product_pref'].'id' => $itemId), false, false, $this->config['prod_parametr_links'], $this->config['prod_parametr_links_pref']);
				if($r){
					foreach ($r as $v){
						if($v['param_id'] || $v['value']){
							$out['col_param'][$v['group_id']][$v['param_id']] = $v['value'] ? $v['value'] : 1;
						}
					}
				}
				$r = $this->getItems(array('id', 'name', 'type', 'is_filter', 'is_hidden', 'alias_of'), array($this->config['parametr_categories_pref'].'id' => $catId),
						array('priority'), false, $this->config['parametr_groups'], $this->config['parametr_groups_pref']);
				if($r){
					foreach($r as $v){
						$out['options'][$v['id']] = $v;
						$out['options'][$v['id']]['items'] = $this->getItems(array('id', 'name'),
								array($this->config['parametr_groups_pref'].'id' => ($v['alias_of'] ? $v['alias_of'] : $v['id'])), $v['alias_of'] ? array('name') : false,
								false, $this->config['parametr_items'], $this->config['parametr_items_pref']);
					}
				} else {
					$out['error'] = 'Параметры отсутствуют!';
				}
			} else {
				$out['error'] = 'Категория каталога не связана с категорией параметров!';
			}
			ob_start();
			include $this->path.'/data/admin/show_options_filter.html';
			return ob_get_clean();
		}
	}
}
?>
