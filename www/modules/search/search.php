<?php
namespace search{
	use core;
	class Search extends core\CContent{
		public $ret = array();
		
		function __construct($m) {
			parent::__construct(__CLASS__, $m);
		}

		function parseRequest($request) {
			return $request;
		}

		function getItems($search){
		    $ret = [];
            $search = mb_strtolower($search);
		    $it = parent::getItems(
		        ['id', 'parent_id', 'name', 'content', 'posted'],
                [[['name' => 'LOWER(co_name)', 'cond' => 'LIKE "%'.$search.'%"', 'is_name' => true], 'or', ['name' => 'LOWER(co_content)', 'cond' => 'LIKE "%'.$search.'%"', 'is_name' => true]]],
                ['posted DESC'],
                null,
                'content',
                'co_'
            );

            $listIt = null;

		    foreach ($it as $v){
		        $rel = 0;
		        if(mb_strpos($v['name'], $search) !== false) $rel += 4;
                if(mb_strpos($v['content'], $search) !== false) $rel += 2;
                $v['rel'] = $rel;

                $v['content'] = mb_substr(strip_tags($v['content']), 0, 300);

                if(!$v['parent_id']) $v['alias'] = $this->Util->getAlias($v['id'], 'content');
                else {
                    if(!$listIt) $listIt = parent::getItems(['id', 'parent_id'], null, null, 'id', 'content', 'co_');
                    $parentId = $listIt[$v['id']]['parent_id'];
                    $arr = [$this->Util->getAlias($v['id'], 'content')];
                    while($parentId){
                        $arr[] = $this->Util->getAlias($parentId, 'content');
                        $parentId = $listIt[$parentId]['parent_id'];
                    }
                    $v['alias'] = implode('/', array_reverse($arr));
                }

		        array_push($ret, $v);
            }

            $it = parent::getItems(
                ['id', 'name', 'content', 'posted'],
                [[['name' => 'LOWER(ar_name)', 'cond' => 'LIKE "%'.$search.'%"', 'is_name' => true], 'or', ['name' => 'LOWER(ar_content)', 'cond' => 'LIKE "%'.$search.'%"', 'is_name' => true]]],
                ['posted DESC'],
                null,
                'articles',
                'ar_'
            );

            foreach ($it as $v){
                $rel = 0;
                if(mb_strpos($v['name'], $search) !== false) $rel += 3;
                if(mb_strpos($v['content'], $search) !== false) $rel += 1;
                $v['rel'] = $rel;

                $v['content'] = mb_substr(strip_tags($v['content']), 0, 300);

                $v['alias'] = $this->Util->getAlias($v['id'], 'articles');
                array_push($ret, $v);
            }


            usort($ret, function($a, $b){
                return $b['rel'] - $a['rel'];
            });

            return $ret;
        }
	}
}
?>