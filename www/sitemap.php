<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 29.07.2017
 * Time: 9:53
 */
include_once 'include/core.php';
$Core = new Core(Core::UTIL | Core::CONNECTED | Core::MEMCACHE);

ob_start();

echo '<?xml version="1.0" encoding="UTF-8"?>
<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<!-- created with Free Online Sitemap Generator www.xml-sitemaps.com -->

<url>
  <loc>http://'.$Core->globalConfig['site'].'/</loc>
  <changefreq>monthly</changefreq>
  <priority>1.00</priority>
</url>';

$it = [];
foreach($Core->getClass('Content')->getContentItems() as $v){
    $it[$v[0]] = $v;
}

function getAlias($item){
    global $Core, $it;
    if(!$item[1]) return $Core->getClass('Util')->getAlias($item[0], 'content');
    else {
        $arr = [$Core->getClass('Util')->getAlias($item[0], 'content')];
        $parent = $it[$item[1]];
        while($parent){
            $arr[] = $Core->getClass('Util')->getAlias($parent[0], 'content');
            $parent = $parent[$parent[1]];
        }
        return implode('/' ,array_reverse($arr));
    }
}

foreach ($it as $ov){
    echo '<url>
  <loc>http://'.$Core->globalConfig['site'].'/'.getAlias($ov).'/</loc>
  <changefreq>monthly</changefreq>
  <priority>0.80</priority>
</url>';
}

unset($it);

foreach($Core->getClass([Core::CLASS_NAME => 'Articles', Core::MODULE => 'articles'])->getItems(['id'], ['active' => 1], ['posted desc']) as $v){
    echo '<url>
  <loc>http://'.$Core->globalConfig['site'].'/'.$Core->getClass('Util')->getAlias($v[0], 'articles').'/</loc>
  <changefreq>monthly</changefreq>
  <priority>0.64</priority>
</url>';
}

echo '</urlset>';

$content = ob_get_clean();

file_put_contents(CLIENT_PATH.'/sitemap.xml', $content);

echo $content;