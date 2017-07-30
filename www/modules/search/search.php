<?php
namespace search{
	use core;
	class Search extends core\CContent{
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
			return $request;
		}

		function getItems($search){
		    $ret = [];
            return $ret;
        }
	}
}
?>