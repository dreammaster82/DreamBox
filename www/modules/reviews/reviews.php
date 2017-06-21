<?php
namespace reviews{
	use core;
	class Reviews extends core\CContent {
		protected $config = array(
			'header' => 'Отзывы и вопросы',
			'table' => 'reviews',
			'pref' => 'rev_',
			'items_on_page' => 20,
			'pages_in_line' => 10,
			'reqId' => 'id'
		);
		
		protected $types = array('Отзыв', 'Вопрос');
		
		function __construct($m) {
			parent::__construct(__CLASS__, $m);
		}

		function parseRequest($request) {
			$ret = array();
			if($request != '/'){
				$this->setError('badRequest');
			}
			return $ret;
		}

		function getPath($item = array()){
			return array(array('link' => '', 'name' => $this->config['header']));
		}
		
		function appendReview(array $data){
			return $this->Db->queryInsert('INSERT INTO '.$this->config['table'].' 
					('.$this->config['pref'].'name,
					'.$this->config['pref'].'email,
					'.$this->config['pref'].'text,
					'.$this->config['pref'].'ask,
					'.$this->config['pref'].'type,
					'.$this->config['pref'].'raiting)
					VALUES
					(?, ?, ?, "", ?, ?)',
					array($data['name'], $data['email'], $data['text'], (int)$data['type'], (int)$data['raiting']));
		}
	}
}
?>