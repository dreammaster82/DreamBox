<?php
namespace catalog{
	class Basket extends \CContent{
		protected $config = array(
			'product_table' => 'product',
			'product_pref' => 'pr_',
			'other_table' => 'product_other',
			'other_pref' => 'po_',
			'orders_table' => 'orders',
			'link_table' => 'product_link',
			'link_pref' => 'pl_',
			'header' => 'Корзина'
		);

		public $ret = array();
		
		function __construct($m) {
			parent::__construct(__CLASS__, $m);
			$this->config['files_path'] = '/files/product';
			if(!$_SESSION['basket']){
				setSession();
				$_SESSION['basket'] = array();
			}
		}

		function parseRequest($request) {
			$ret = array();
			if($request){
				if(substr($request, -1, 1) == '/'){
					$str = substr($request, 0, -1);
					if(in_array($str, array('order', 'basket'))){
						if($str == 'basket'){
							$str = 'show';
						}
						$ret['action'] = $str;
					} else {
						$this->setError('badRequest');
					}
				} else {
					$this->setError('badRequest');
				}
			}
			return $ret;
		}

		function toBasket($id){
			$ret = false;
			if((int)$_REQUEST['delete']){
				unset($_SESSION['basket'][$id]);
				$ret = true;
			} else {
				$r = $this->getItem(array('price'), array($this->config['product_pref'].'id' => $id), $this->config['other_table'], $this->config['other_pref']);
				if($r){
					$cnt = (int)$_REQUEST['count'] ? (int)$_REQUEST['count'] : 1;
					$_SESSION['basket'][$id] = array('price' => $r['price'], 'count' => $cnt);
					$ret = true;
				} else {
					$ret = false;
				}
			}
			return $ret;
		}

		function ajaxProcess(){
			if(!class_exists('Catalog') && is_file($this->path.'/catalog.php')){
				include $this->path.'/catalog.php';
			}
			if(!class_exists('Product') && is_file($this->path.'/product.php')){
				include $this->path.'/product.php';
			}
			if(!class_exists('Filters') && is_file($this->path.'/filters.php')){
				include $this->path.'/filters.php';
			}
			if($_REQUEST['href']){
				$_REQUEST = array_merge($_REQUEST, $this->parseRequest($_REQUEST['href']));
			}
			$action = $_REQUEST['action'];
			if(!$action || !method_exists($this,$action)){
				$action = 'show';
			}
			return $this->$action();
		}
	}
}
?>
