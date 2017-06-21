<?php
namespace core{
	class Db{
		private $link;
		function connect(){
			try{
				$this->link = new \PDO(
					'mysql:host='.$CONFIG['database']['host'].';dbname='.$this->Core->globalConfig['database']['database'],
					$this->Core->globalConfig['database']['user'],
					$this->Core->globalConfig['database']['pass'],
					array(
						\PDO::MYSQL_ATTR_LOCAL_INFILE => 1,
						\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
					)
				);
			} catch (\PDOException $ex){
				echo'Ошибка подключения: '.$ex->getMessage();
				exit();
			}
		}

		function queryOne($sql, $attr = array(), $pdo = \PDO::FETCH_ASSOC){
			try{
				if($attr){
					$r = $this->link->prepare($sql);
					$r->execute($attr); 
				} else {
					$r = $this->link->query($sql) OR DIE(debug($this->link->errorInfo()).debug(debug_backtrace()));
				}
				$err = $r->errorInfo();
				if($err[1]){
					debug($err);
					debug(debug_backtrace());
				} else {
					$ret = $r->fetch($pdo);
				}
			} catch (\PDOException $e){
				echo $e->getMessage();
			}
			if($ret){
				return $ret;
			} else {
				return array();
			}
		}

		function query($sql, $attr = array(), $pdo = \PDO::FETCH_ASSOC){
			$ret = array();
			try{
				if($attr){
					$r = $this->link->prepare($sql);
					$r->execute($attr);
				} else {
					$r = $this->link->query($sql) OR DIE(debug($this->link->errorInfo()).debug(debug_backtrace()));
				}
				$err = $r->errorInfo();
				if($err[1]){
					debug($err);
					debug(debug_backtrace());
				} else {
					$ret = $r->fetchAll($pdo);
				}
			} catch (\PDOException $e){
				echo $e->getMessage();
			}
			return $ret;
		}

		function queryInsert($sql, $attr = array()){
			$id = 0;
			try{
				if($attr){
					$r = $this->link->prepare($sql);
					$r->execute($attr);
					$err = $r->errorInfo();
					if($err[1]){
						debug($err);
						debug(debug_backtrace());
					} else {
						$r->fetch();
						$id = $this->link->lastInsertId();
					}
				} else {
					$r = $this->link->exec($sql);
					if($r){
						$id = $this->link->lastInsertId();
					}
				}
			} catch (\PDOException $e){
				echo $e->getMessage();
			}
			return $id;
		}

		function queryExec($sql, $attr = array()){
			$rows = 0;
			try{
				if($attr){
					$r = $this->link->prepare($sql);
					$r->execute($attr);
					$err = $r->errorInfo();
					if($err[1]){
						debug($err);
						debug(debug_backtrace());
					} else {
						$r->fetch();
						$rows = 1;
					}
				} else {
					$rows = $this->link->exec($sql);
				}

			} catch (\PDOException $e){
				echo $e->getMessage();
			}
			return $rows;
		}
	}
}
?>