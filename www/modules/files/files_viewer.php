<?php
namespace files;
class FilesViewer extends Files{
	function __construct($m) {
		parent::__construct($m);
	}
	
	function process() {
		if($_REQUEST['file_hash']){
			$item = $this->getItem(['file_src', 'name'], ['active' => 1, 'hache' => $_REQUEST['file_hash']]);
			if($item){
				$mime = mime_content_type(CLIENT_PATH.$this->config['files_path'].$item['file_src']);
				header('Content-Type: ' . $mime);
				header('Expires: ' . $now);
				if(strpos($_SERVER[HTTP_USER_AGENT], "MSIE")){
					header('Content-Disposition: inline; filename='.$item['name']);
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Pragma: public');
				} else {
					header('Content-Disposition: attachment; filename='.$item['name']);
					header('Pragma: no-cache');
				}
				readfile(CLIENT_PATH.$this->config['files_path'].$item['file_src']);
				exit;
			} else {
				return $this->error();
			}
		} else {
			return $this->error();
		}
	}
}
?>