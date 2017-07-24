<?php
namespace articles{
	use core;
	class Articles extends core\CContent {
		protected $config = array(
			'header' => 'Articles',
			'table' => 'articles',
			'pref' => 'ar_',
			'items_on_page' => 20,
			'pages_in_line' => 10,
			'reqId' => 'id'
		);

		function __construct($m) {
			parent::__construct(__CLASS__, $m);
		}

		function parseRequest($request) {
			$ret = array();
			if(+$request){
			    $ret['id'] = +$request;
            } else {
                if(strpos($request, '/') !== false){
                    $this->setError('badRequest');
                }
                if(strpos($request, '.html') !== false){
                    list($ret['id'], $ret['alias']) = explode('_', str_replace('.html', '', $request), 2);
                    if(!(float)$ret['id']){
                        $this->setError('badRequest');
                    }
                } else {
                    $this->setError('badRequest');
                }
            }

			return $ret;
		}

		function getPath($item = array()){
			$ret = array();
			if($item){
				$ret[] = array('link' => '/'.strtolower($this->class).'/', 'name' => $this->config['header']);
				$ret[] = array('link' => '', 'name' => $item['name']);
			} else {
				$ret[] = array('link' => '', 'name' => $this->config['header']);
			}
			return $ret;
		}
	}
}
?>