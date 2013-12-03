<?php
namespace catalog{
	class ProductViewer extends Product implements \CContentViewer{

		function __construct($m) {
			parent::__construct($m);
		}

		function showItems($out){
			ob_start();
			include $this->path.'/data/show_items_product.html';
			return ob_get_clean();
		}

		function showItem($out){
			$this->ret['title'] = $out['name'];
			$this->ret['description'] = $out['name'];
			$this->ret['keywords'] = $out['name'];
			if($C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Catalog', \Core::MODULE => 'catalog'))){
				$out['itemprop_category'] = $C->getItempropCat($out['ca_id']);
				$out['parent'] = $C->getItem(array('id', 'img_src', 'name'), $out['ca_id']);
			}
			$out['path'] = $this->config['files_path'].'/'.implode('/', array_slice(explode('-', reset(explode(' ', $out['posted']))), 0, 2));
			$out['images'] = $this->Util->getImagesArray($out['path'].'/'.$out['id']);
			if($out['images']){
				$this->ret['js_before'] .= '<link rel="stylesheet" type="text/css" href="/css/photo_viewer_light_round/photo_viewer_light_round.css" /><script src="/modules/catalog/js/jquery.CImager.js"></script>';
				$this->ret['js_after'] .= '<script>$(document).ready(function(){$(\'.product > .image\').CImager({prev_type:\'prodbig\',big_image:\'.big_image\',animationSpeed:200});});</script>';
			}
			if($out['analog']){
				$out['analog'] = array_slice($out['analog'], 0, 3, true);
			}
			$out['href'] = '/catalog'.$out['c_alias'].'/'.$out['id'].'_'.$out['alias'].'.html';
			if((int)$_REQUEST['to_basket']){
				$this->Core->getClass(array('Basket', 'catalog'))->toBasket($out['id']);
			}
			$_SESSION['catalog_alias'] = $out['ca_alias'];
			$this->ret['js_before'] .= '<script defer src="/modules/catalog/js/product_before.js"></script>';
			$this->ret['js_after'] .= '<script defer src="/modules/catalog/js/product_after.js"></script>';
			ob_start();
			include $this->path.'/data/show_item_product.html';
			return ob_get_clean();
		}

		function show(){
			if($this->errors){
				return $this->error();
			}
			$item = $this->getItem(array('id', 'name', 'ca_id', 'alias', 'img_src', 'content', 'articul', 'spec', 'params', 'price', 'links', 'posted'), $this->id);
			if($item){
				return $this->showItem($item);
			} else {
				return false;
			}
		}
	}
}
?>
