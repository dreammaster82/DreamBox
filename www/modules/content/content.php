<?php
namespace content{
	use core;
	class Content extends core\CContent{
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
			$ret['alias'] = $request;
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
				$r = $this->getItems(array('id', 'parent_id', 'name'), array('active' => 1, 'on_top' => 1), array('priority'));

				$arr = array();
				foreach ($r as $v){
                    if(!$GLOBALS['realAlias']){

                    }
					$arr[$v['parent_id']][$v['id']] = $v;
                    $alias = $this->Util->getAlias($v['id'], 'content');

                    if(!$alias) throw new \Error("Ошибка алиаса");
					$arr[$v['parent_id']][$v['id']]['real_alias'] = '/'.$alias;
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
			if($item['parent_id']){
				$arr = array();
				if(class_exists('HandlerSocket')){
					$hs = new \HandlerSocket($this->Core->globalConfig['database']['host'], $this->Core->globalConfig['handler_socket']['port']);
					$fields = array(
						$this->config['pref'].'id',
						$this->config['pref'].'parent_id',
						$this->config['pref'].'header'
					);
					if($hs->openIndex(1, $this->Core->globalConfig['database']['database'], $this->config['table'], \HandlerSocket::PRIMARY, implode(',', $fields))){
						$pId = $item['parent_id'];
						while($pId){
							$it = $hs->executeSingle(1, '=', array($pId));
							$pId = $it[0][1];
							$arr[] = array('header' => $it[0][2]);
						}
					}
					unset($hs);
				}
				$arr = array_reverse($arr);
				foreach ($arr as $v){
					$ret[] = array('link' => '/'.$v['alias'], 'name' => $v['header']);
				}
			}
			if($item){
				$ret[] = array('link' => '', 'name' => $item['header']);
			}
			return $ret;
		}
		
		function getContentItems(){
			if(isset($this->Memcache) && $it = $this->Memcache->get('getContentItems')){
				return $it;
			}
			$r = $this->Db->query('SELECT
				'.$this->config['pref'].'id ,
				'.$this->config['pref'].'parent_id,
				'.$this->config['pref'].'name,
				'.$this->config['pref'].'on_top
				FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'active=1
				ORDER BY '.$this->config['pref'].'priority', false, \PDO::FETCH_NUM);
			$it = [];
			foreach ($r as $v){
                $alias = $this->Util->getAlias($v[0], 'content');

                if(!$alias) throw new \Error("Ошибка алиаса");

                array_push($v, $alias);
				$it[$v[0]] = $v;
			}
			if(isset($this->Memcache)){
				$this->Memcache->set('getContentItems', $it, $this->class);
			}
			return $it;
		}

        function getFullAlias($item) {
            $parent = null;
            if ($item['parent_id']) {
                $parent = $this->getItem(['id', 'parent_id'], $item['parent_id']);
            }

            return ($parent ? $this->getFullAlias($parent).'/' : '').$this->Util->getAlias($item['id'], 'content');
        }
	}
}
?>