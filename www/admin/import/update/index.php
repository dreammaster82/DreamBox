<?php

include('../../../include/config.php');
include('../../../include/util.php');	
include("../../../include/forall.php");

class update
{
	var $pricename = "price/price.txt";	// файл с оптовым прайсом
	var $separator = "\t";				// разделитель

	function ping()
	{
		echo "1";
	}

	function loadPrice()
	{
		
		$this->_loadPrice();
	}

	function _loadPrice(){
		global $OUT;
		$price = $this->_getPrice();
		$id = array();
		foreach ($price as $k => $v){
			$id[$k] = $k;
		}
		if(sizeof($id)){
			$r = db::query('SELECT id FROM product WHERE id IN('.implode(',',$id).')');
			//$sql = 'INSERT INTO product (id, price) VALUES ';
			$count=0;
			foreach($r as $v){
				db::queryExec('UPDATE product SET price='.$price[$v['id']].' WHERE id='.$v['id']);
				//$sql .= '('.$v['id'].','.$price[$v['id']].'),';
				$count++;
			}
			//$sql = substr($sql, 0, -1).' ON DUPLICATE KEY UPDATE price=price';

			$ret = "Загрузка прошла успешно. Загружено {$count} позиций.";
		} else {
			$ret = 'Ошибка загрузки';
		}
		$OUT['content'] .= $ret;
	}

	function _getPrice()
	{
		$price = array();
	
		// 0 - id (1С код)
		// 1 - название
		// 2 - цена 
		// 3 - валюта 
		if(is_file($this->pricename)){
			$strok = explode("\r\n", file_get_contents($this->pricename));
			foreach ($strok as $v){
				$s = explode("\t", $v);
				if((int)$s[0] && sizeof($s) > 2){
					$price[(int)$s[0]] = (float)$s[2];
				}
			}
		}
		return $price;
	}

	function _process()
	{
		$action = $_REQUEST[action];

		if($_FILES[userfile][size])
		{
			$file = $_FILES[userfile];
			if($action == "loadPrice")
			{
				copy($file[tmp_name],$this->pricename);
				$this->pricename = $file[tmp_name];
			}
		} elseif(!$action) echo "copy file failed!";

		if($action && method_exists($this,$action)) $this->$action();
	}
}

$update = new update();
$update ->_process();
$OUT[header] = "ПРАЙС-ЛИСТЫ";
if($_SESSION[menu]) $OUT[menu] = $_SESSION[menu];
include('../../../data/admin.html');
?>