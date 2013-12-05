<?php
namespace admin{
	class HiperLink2 extends AdminFunctions{

		protected $config = array(
			'header' => 'Вставка ссылки на статью',
			'pages_in_line' => 10,
			'items_on_page' => 20
		);

		private $classes = array(
			'News' => array('Новости', 'news'),
			'Content' => array('Страницы', 'content'),
		);

		public $ret = array();

		function process(){
			$action = method_exists($this, $_REQUEST['action']) ? $_REQUEST['action'] : 'show';
			return $this->$action();
		}


		function show(){
			$this->ret['title'] = $this->ret['description'] = $this->ret['keywords'] = $this->config['header'];
			$ret .= '<div class="admin_content">
				<div class="header">'.$this->config['header'].'</div>
				<form name="hiperLink_form" method="POST">
					<input id="idArea" type="hidden" name="id" value="'.$_REQUEST['id'].'" />
					<input type="hidden" name="window" value="'.$_REQUEST['window'].'" />
					'.($_REQUEST['class'] ? '<input type="hidden" name="class" value="'.$_REQUEST['class'].'" />' : '').'
					<div style="padding-bottom:20px;border-bottom:1px solid #BDBDBD">
						<div style="margin-bottom:15px">Текст ссылки</div>
						<input type="text" id="linkName" name="name" value="'.stripslashes($_REQUEST['name']).'" style="width:60%;margin-bottom:15px" />
						<div style="margin-bottom:15px">Всплывающий текст</div>
						<textarea name="text" id="linkText" style="width:60%;height:150px">'.stripslashes($_REQUEST['text']).'</textarea>
					</div>
					<div style="padding:20px 0px">';
			$class = strtolower(trim($_REQUEST['class']));
			if($class && is_file(MODULES_PATH.'/'.$class.'/'.$class.'.php')){
				$ret .= '<div style="margin-bottom:15px"><button name="class" value="">Выбор категорий</button></div>';
				$ret .= '<center style="font-weight:bold;margin-bottom:15px;">'.$this->classes[$_REQUEST['class']][0].'</center>';
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => $_REQUEST['class'], \Core::MODULE => $this->classes[$_REQUEST['class']][1], \Core::ADMIN => true));
				$items = $C->getItems(array('alias', 'id', 'name'), array('active' => 1));
				if($items){
					if($_REQUEST['class'] != 'Content'){
						$cnt = $C->getCountItems(array('active' => 1));
						$ret .= $this->getPages($cnt);
						foreach ($items as $v){
							$href = '/'.$class.'/'.$v['id'].'_'.$v['alias'].'.html';
							$ret .= '<table class="oneelem hovered"><tr><td style="width:100%">'.$v['name'].'</td><td><button type="button" onclick="sendPost(\''.$href.'\');">Выбрать</button></td></tr></table>';
						}
						$this->ret['script'] = '<script src="hiperLink2.js"></script>';
					} else {
						foreach ($items as $v){
							$href = '/'.$class.'/'.$v['alias'];
							$ret .= '<table class="oneelem hovered"><tr><td style="width:100%">'.$v['name'].'</td><td><button type="button" onclick="sendPost(\''.$href.'\');">Выбрать</button></td></tr></table>';
						}
						$this->ret['script'] = '<script src="hiperLink2.js"></script>';
					}
				} else {
					$ret .= 'Категория пуста';
				}
			} else {
				$ret .= '
					<center style="font-weight:bold;margin-bottom:15px;">Выберите категорию</center>';
				foreach ($this->classes as $k => $v){
					$ret .= '
						<table class="oneelem hovered"><tr><td style="width:100%">'.$v[0].'</td><td><button name="class" value="'.$k.'">Выбрать</button></td></tr></table>';
				}
			}
			$ret .= '
				</div>
			</div>';
			return $ret;
		}

		function getPages($cnt, $lnk = ''){
			$data = array();
			$data['page'] = (int)$_REQUEST['page'];
			$data['iteration'] = ceil($cnt / $this->config['items_on_page']);
			if($data['iteration'] == 1){
				return $ret;
			}
			$first = floor($data['page'] / $this->config['pages_in_line']) *  $this->config['pages_in_line'];
			if($data['iteration'] <= $this->config['pages_in_line'] || $data['iteration'] <= ($first + $this->config['pages_in_line'])){
				$data['end'] = $data['iteration'];
			} else {
				$data['end'] = $first + $this->config['pages_in_line'];
			}
			$ret = '<div class="pages">';
			$data['i'] = $first;
			$add = array();
			if($_REQUEST['sort']){
				$add[] = '&sort='.$_REQUEST['sort'];
			}
			if($_REQUEST['find']){
				$add[] = '&find='.$_REQUEST['find'];
			}
			if($_REQUEST['no_edit']){
				$add[] = '&no_edit='.$_REQUEST['no_edit'];
			}
			if($lnk){
				$add[] = $lnk;
			}
			$data['add'] = '&'.implode('&', $add);
			ob_start();
			include CLIENT_PATH.'/admin/data/get_pages.html';
			return ob_get_clean();
		}
	}
}
?>
