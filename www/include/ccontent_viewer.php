<?php
namespace core{
	interface CContentViewer{
		function show();
		function showItem($item);
		function showItems($items);
	}
}
?>