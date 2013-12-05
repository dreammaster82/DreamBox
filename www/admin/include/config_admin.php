<?php
$CFG = array(
	'globalpath'	=> '<span class=\"path\"> > администрирование сайта</span>',
	'title'			=> ' :: администрирование',
	'admin_menu' 	=> array(
		array(
			'admin'	=> 'files',
			'text'	=> 'Менеджер файлов',
			'link'	=> '/admin/files/',
			'show'	=> 1,
			'check'	=> 1
		),
		array(
			'admin' => 'users',
			'text'	=> 'Администраторы',
			'link'	=> '/admin/admin_users/',
			'show'	=> 1,
			'check'	=> 1
		),
		array(
			'admin' => 'catalog',
			'text'	=> 'Каталог',
			'link'	=> '/admin/catalog/',
			'show'	=> 1,
			'check'	=> 1
		),
		array(
			'admin' => 'news',
			'text'	=> 'Новости',
			'link'	=> '/admin/news/',
			'show'	=> 1,
			'check'	=> 1
		),
		array(
			'admin' => 'slider',
			'text'	=> 'Слайдер',
			'link'	=> '/admin/admin_slider/',
			'show'	=> 1,
			'check'	=> 1
		),
		array(
			'admin' => 'tips',
			'text'	=> 'Советы',
			'link'	=> '/admin/tips/',
			'show'	=> 1,
			'check'	=> 1
		),
		array(
			'admin' => 'content',
			'text'	=> 'Содержание страниц',
			'link'	=> '/admin/content/',
			'show'	=> 1,
			'check'	=> 1
		),
		array(
			'admin' => 'catalog',
			'text'	=> 'Фильтры',
			'link'	=> '/admin/catalog/?cl=Filters',
			'show'	=> 1,
			'check'	=> 1,
			'noviews' => 1,
			'module' => 'filters'
		),
		array(
			'admin' => 'gallery',
			'text'	=> 'Фото-видео галлерея',
			'link'	=> '/admin/gallery/',
			'show'	=> 1,
			'check'	=> 1
		),
		array(
			'admin' => 'admin',
			'text'	=> 'Очистить кэш изображений',
			'link'	=> '/admin/?action=clearimg',
			'show'	=> 1,
			'check'	=> 0,
			'noviews' => 1
		),
		array(
			'admin' => 'converter',
			'text'	=> 'НТМL конвертер',
			'link'	=> '/admin/?action=converter',
			'show'	=> 1,
			'check'	=> 0,
		),
	),
	'templates'  => array('content'),
);
?>