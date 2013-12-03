<?php
namespace catalog{
	class BasketViewer extends Basket{

		function __construct($m) {
			parent::__construct($m);
		}
		
		function order(){
			$out = array();
			$errors = array();
			if($_REQUEST['hash'][0] != $_SESSION['hash'][0]){
				$errors[] = 'Ошибка запроса.';
			}
			unset($_SESSION['hash']);
			$i = 0;
			$_REQUEST['name'] = implode(' ', array_map(function($str){
				if(!$str = stripslashes(trim($str))){
					$err = '';
					switch($i++){
						case 1:
							$err = 'Имя'; break;
						case 2: 
							$err = 'Отчество'; break; 
						default: 
							$err = 'Фамилию';
					}
					$errors[] = 'Вы забыли указать '.$err;
				}
			}, $_REQUEST['name']));
			
			if(!trim($_REQUEST['phone'])){
				$errors[] = 'Вы забыли указать Телефон';
			}
			if((int)$_REQUEST['delivery'] && !trim($_REQUEST['adres'])){
				$errors[] = 'Вы забыли указать Адрес';
			}
			if(!$errors && $_SESSION['basket']){
				if($C = $this->Core->getClass(array('Product', 'catalog'))){
					$cond = array(array(array('name' => 'id', 'cond' => 'IN', 'data' => array_keys($_SESSION['basket']))));
					$out['items'] = $C->getItems(array('id', 'name', 'alias', 'img_src', 'ca_id', 'ca_alias', 'params'), $cond, false);
					$pr = $C->getPrices(array_keys($out['items']));
					foreach ($out['items'] as $k => $v){
						if($pr[$k]){
							foreach ($pr[$k] as $k1 => $v1){
								if($_SESSION['basket'][$k]['cnt'] >= $k1){
									$out['items'][$k]['price'] = $v1;
								}
							}
						}
					}
					unset($pr);
					if($out['items']){
						$out['name'] = implode(' ', $_REQUEST['name']);
						$out['phone'] = stripslashes(trim($_REQUEST['phone']));
						$out['adres'] = stripslashes(trim($_REQUEST['adres']));
						$email = stripslashes(trim($_REQUEST['email']));
						$out['comment'] = stripslashes(trim($_REQUEST['comment']));
						$out['summa'] = 0;
						$out['mdhash'] = md5(time());
						$out['orderId'] = $this->Db->queryInsert('INSERT INTO '.$this->config['orders_table'].'
							(name, phone, adres, data, email, comment, price, mdhash, delivery, payment)
							VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
							array($out['name'], $out['phone'], $out['adres'], json_encode($_SESSION['basket']), $email, $out['comment'], $out['summa'], $out['mdhash'], (int)$_REQUEST['delivery'], (int)$_REQUEST['payment']));
						//---Увеличиваем количество покупок для продукта
						$cond = array();
						foreach ($out['items'] as $v){
							$cond[$v['id']] = $v['id'];
						}
						$this->Db->queryExec('UPDATE '.$this->config['product_table'].' SET '.$this->config['product_pref'].'order_count='.$this->config['product_pref'].'order_count+1
							WHERE '.$this->config['product_pref'].'id IN('.substr(str_repeat('?,', sizeof($cond)), 0, -1).')', array_values($cond));
						//---Связка продуктов, покупаемых вместе
						$arr = array();
						$arr2 = array();
						foreach ($cond as $k => $v){
							$c2 = $cond;
							unset($c2[$k]);
							$arr[] = str_repeat('('.$k.',?,0),', sizeof($c2));
							$arr2 = array_merge($arr2, array_values($c2));
						}
						unset($c2);
						unset($cond);
						$this->Db->queryExec('INSERT INTO '.$this->config['related_table'].' (pl_pr_id, pl_id, pl_count) VALUES '.substr(implode('', $arr), 0, -1).' ON DUPLICATE KEY UPDATE pl_id=pl_id, pl_count=pl_count+1', $arr2);
						unset($arr);
						unset($arr2);
						$this->ret['title'] = $this->ret['description'] = $this->ret['keywords'] = $out['title'] = 'Заказ №'.$out['orderId'].' успешно сформирован';
						ob_start();
						include $this->path.'/data/order_html_basket.html';
						$body = ob_get_contents();
						$this->Util->mail($this->Core->globalConfig['email']['from_email'], $this->Core->globalConfig['email']['from_text'], $this->Core->globalConfig['email']['from_email'], 'Новый заказ №'.$out['orderId'], $body);
						if($email){
							$this->Util->mail($this->Core->globalConfig['email']['from_email'], $this->Core->globalConfig['email']['from_text'], $email, 'Новый заказ №'.$out['orderId'], $body);
						}
						$this->Util->mail($this->Core->globalConfig['email']['from_email'], $this->Core->globalConfig['email']['from_text'], 'denis@webmechanica.ru', 'Новый заказ №'.$out['orderId'], $body);
						unset($body);
						ob_clean();
						include $this->path.'/data/order_basket.html';
						unset($_SESSION['basket']);
						return ob_get_clean();
					}
				}
			}
			$this->ret['warning'] = implode(' ', $errors);
			return $this->show();
		}

		function show(){
			if($this->errors){
				$this->error();
			}
			$out = array();
			$this->ret['title'] = $this->ret['description'] = $this->ret['keywords'] = $this->ret['content_title'] = 'Корзина';
			if((float)$_REQUEST['delete']){
				unset($_SESSION['basket'][(float)$_REQUEST['delete']]);
			}
			if((int)$_REQUEST['clear']){
				unset($_SESSION['basket']);
			}
			if($_SESSION['basket'] && $C = $this->Core->getClass(array('Product', 'catalog'))){
				$tData = array('id', 'ca_id', 'name', 'img_src', 'alias', 'price', 'params', 'ca_alias');
				$out['items'] = $C->getItems($tData, array(array(array('name' => 'id', 'cond' => 'IN', 'data' => array_keys($_SESSION['basket'])))));
				$this->ret['js_before'] .= '<script defer src="/modules/catalog/js/basket_before.js"></script>';
				$this->ret['js_after'] .= '<script defer src="/modules/catalog/js/basket_after.js"></script>';
			}
			ob_start();
			include $this->path.'/data/show_basket.html';
			return ob_get_clean();
		}
	}
}
?>
