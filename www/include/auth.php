<?php
class Auth{

	protected $config = array(
		'table' => 'admin_user',
		'pref' => 'au_'
	);
	
	public $user = array();
	
	function process(){
		if($_SESSION['admin']['hash'] && !$_REQUEST['logout']){
			list($id, $hash) = decode_hash($_SESSION['admin']['hash']);
			$u = $this->getUserById($id);
			if($u && md5($u['id'].$u['login'].$u['permis']) == $hash){
				$this->user = $u;
			}
		}
		return $this->user;
	}

	function getUser($login, $pass){
		$user = $this->Db->queryOne('SELECT
			'.$this->config['pref'].'id AS id,
			'.$this->config['pref'].'login AS login,
			'.$this->config['pref'].'ap_id AS ap_id,
			'.$this->config['pref'].'permis AS permis
			FROM '.$this->config['table'].'
			WHERE '.$this->config['pref'].'login=? AND '.$this->config['pref'].'pass=? AND '.$this->config['pref'].'active=1', array($login, $pass));
		$user['permission'] = $this->getPermission($user['permis']);
		return $user;
	}

	function getUserById($id){
		$user = $this->Db->queryOne('SELECT
			'.$this->config['pref'].'id AS id,
			'.$this->config['pref'].'login AS login,
			'.$this->config['pref'].'ap_id AS ap_id,
			'.$this->config['pref'].'permis AS permis
			FROM '.$this->config['table'].'
			WHERE '.$this->config['pref'].'id=?', array($id));
		$user['permission'] = $this->getPermission($user['permis']);
		return $user;
	}
	
	function getPermission($pr){
		if(!$this->Core->cfg){
			include ADMIN_PATH.'/include/config_admin.php';
			$this->Core->cfg = $CFG;
		}
		$ret = array();
		if($pr == 0xFFFFFFFF){
			$ret['root'] = true;
			foreach ($this->Core->cfg['admin_menu'] as $v){
				$ret[$v['admin']] = true;
			}
		} else {
			foreach($this->Core->cfg['admin_menu'] as $k => $v){
				if(($pr >> $k) & 1){
					$ret[$v['admin']] = true;
				}
			}
		}
		return $ret;
	}
}
?>
