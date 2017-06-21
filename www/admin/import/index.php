<?php
include realpath($_SERVER['DOCUMENT_ROOT']).'/include/core.php';
include ADMIN_PATH.'/include/admin_functions.php';

class Import extends \core\admin\AdminFunctions{
	private $config = array(
		'file' => 'import.txt',
		'xls' => 'price.csv',
		'sql_file_other' => 'sql_price_other.txt',
		'table' => 'product',
		'other_table' => 'product_other'
	), $error = '';

	function getPrice(){
		header('Content-Type: text/csv; boundary="--======================--"');
		header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')){
			header('Content-Disposition: inline; filename='.$this->config['xls']);
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		} else {
			header('Content-Disposition: attachment; filename='.$this->config['xls']);
			header('Pragma: no-cache');
		}
		echo iconv('UTF-8', 'Windows-1251//IGNORE', $this->fullPrice());
		exit;
	}

	private function fullPrice(){
		$ret = 'Id;Название;Цена;Ценовой тип (руб., руб. кв.м.);Скидка %'."\n";
		$cArr = array();
		if($C = $this->Core->getClass(['Catalog', 'catalog'])){
			$cArr = $C->getCatalogArray();
		}
		if($C = $this->Core->getClass(['Product', 'catalog'])){
			$items = $C->getItems(['id', 'ca_id', 'price', 'price_type', 'sale', 'name', 'articul'], false, ['ca_id', 'id']);
			if($items){
				$cId = 0;
				foreach ($items as $v){
					if($v['ca_id'] != $cId){
						$cId = $v['ca_id'];
						$ret .= strtoupper($cArr[$cId]['name'])."\n";
					}
					$ret .= $v['articul'].';'.$v['name'].';'.$v['price'].';'.$this->Core->globalConfig['price_type'][$v['price_type']].';'.$v['sale']."\n";
				}
			}
		}
		return $ret;
	}

	function loadPrice(){
		$i = 0;
		if($_FILES){
			foreach ($_FILES as $k => $v){
				$file = $_FILES[$k];
			}
			if(!is_dir('./include')){
				mkdir('./include', 0775, true);
			}
			if(copy($file['tmp_name'], './include/'.$this->config['file'])){
				ob_start();
				readfile('./include/'.$this->config['file']);
				$arr = explode("\n", ob_get_clean());
				if($arr){
					$r = $this->Db->query('SELECT pr_id AS id FROM '.$this->config['table']);
					$items = [];
					foreach ($r as $v){
						$items[$v['id']] = $v['id'];
					}
					if($items){
						$ret = '';
						$pt = array_flip($this->Core->globalConfig['price_type']);
						$enc = '';
						foreach ($arr as $v){
							if(!$enc){
								$enc = strlen($name) == strlen(mb_convert_encoding($name, 'CP1251', 'UTF-8')) ? 'CP1251' : 'UTF-8';
							}
							if($enc == 'CP1251'){
								$v = mb_convert_encoding($v, 'UTF-8', 'CP1251');
							}
							$arr1 = explode(';', $v);
							if(sizeof($arr1) == 5 && $items[(float)$arr1[0]]){
								$ret .= (float)$arr1[0]."\t".(float)$arr1[2]."\t".(int)$arr1[4]."\t".$pt[$arr1[3]]."\t0\n";
								++$i;
							}
						}
						if($ret){
							if(file_put_contents('./include/'.$this->config['sql_file_other'], $ret) !== false){
								$this->Core->getClass('Db')->queryExec('TRUNCATE TABLE '.$this->config['other_table']);
								$this->Core->getClass('Db')->queryExec('LOAD DATA LOCAL INFILE "./include/'.$this->config['sql_file_other'].'" INTO TABLE '.$this->config['other_table']);
							} else {
								$this->error = 'Ошибка создания sql файла';
							}
							unset($ret);
						}
					} else {
						$this->error = 'Товары отсутствуют';
					}
				} else {
					$this->error = 'Ошибка открытия локального файла';
				}
			} else {
				$this->error = 'Ошибка создания локального файла';
			}
		} else {
			$this->error = 'Ошибка передачи файла';
		}
		if(!$this->error){
			$this->error = '<div class="warning">База успешно обновлена. Обновленно '.$i.' товаров</div>';
		}
		return $this->show();
	}
	
	public function process(){
		$this->Auth = $this->Core->getClass('Auth');
		$this->ret['menu'] = $this->showAdminMenu();
		if(!is_dir(CLIENT_PATH.'/data/log/')){
			mkdir(CLIENT_PATH.'/data/log/', 0775, true);
		}
		$this->ret['cont_id'] = strtolower(__CLASS__);
		$action = method_exists($this, $_REQUEST['action']) ? $_REQUEST['action'] : 'show';
		error_log(date('d.m.Y H:i').'	'.$this->Auth->user['login'].'	action='.$action.'	Import operations'."\n", 3, CLIENT_PATH.'/data/log/log.log');
		return $this->$action();
	}
	
	function show(){
		return ($this->error ? '<div class="warning">'.$this->error.'</div>' : '').'
		<form name="postdata" method="post" enctype="multipart/form-data">
			<b>Импорт прайс-листа</b><br>
			<input type="file" name="file" />
			<input type="submit" class="button">
			<input type="hidden" name="action" value="loadPrice">
		</form>			
		<div><a href="?action=getPrice"><b>Экспорт прайс-листа</b></a></div>';
	}
}

$Core = new Core();
$Core->getClass('Auth')->process();
$out['content'] = $Core->process(['Import', '']);
$out['js'] = $Core->ret['js_before'].$Core->ret['js_after'];
unset($Core->ret['js_before']);
unset($Core->ret['js_after']);
if($Core->ret){
	foreach ($Core->ret as $k => $v){
		$out[$k] = $v;
		unset($Core->ret[$k]);
	}
}
include ADMIN_PATH.'/data/admin.html';

?>