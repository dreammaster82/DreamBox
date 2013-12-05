<?php
namespace news{
	class News extends \CContent {
		protected $config = array(
			'header' => 'Новости',
			'table' => 'news',
			'pref' => 'n_',
			'items_on_page' => 20,
			'pages_in_line' => 10,
			'reqId' => 'id'
		);

		function __construct($m) {
			parent::__construct(__CLASS__, $m);
		}

		function parseRequest($request) {
			$ret = array();
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