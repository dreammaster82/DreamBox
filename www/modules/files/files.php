<?php
namespace files;
class Files extends \CContent{
	protected $config = array(
		'table' => 'files_children',
		'pref' => 'fc_'
	);
	
	function __construct($m) {
		parent::__construct(__CLASS__, $m);
		$this->config['files_path'] = '/files/_db_files';
	}
	
	function parseRequest($request) {
		$arr = explode('/', trim($request, '/'));
		if(sizeof($arr) == 1 && $arr[0]){
			$ret['file_hash'] = $arr[0];
		} else {
			$this->setError('badRequest');
		}
		return $ret;
	}
}
