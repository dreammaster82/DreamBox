<?php
namespace search{
	use core;
	class SearchViewer extends Search implements core\CContentViewer{
        function __construct($m) {
            parent::__construct($m);
            $this->config['admin'] = 'content';
        }

        function showItems($out){
            ob_start();
            include $this->path.'/data/show_items.html';
            return ob_get_clean();
        }

        function showItem($item){}

        function show(){
            if($this->errors){
                return $this->Util->error($this->errors);
            }
            if($_REQUEST['s']){
                $req = stripcslashes($_REQUEST['s']);
                $out['items'] = $this->getItems($req);
                $out['request'] = $req;
                ob_start();
                include $this->path.'/data/show_items.html';
                return ob_get_clean();
            }
            return $this->Util->error('Incorrect request');
        }
	}
}
?>