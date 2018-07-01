<?php
namespace gallery {
    use core;
    class GalleryViewer extends Gallery implements core\CContentViewer
    {
        function showItems($out)
        {
            ob_start();
            include $this->path . '/data/show_items.html';
            return ob_get_clean();
        }

        function showItem($out)
        {
            $this->ret['title'] = $out['title'];
            $this->ret['description'] = $out['description'];
            $this->ret['keywords'] = $out['keywords'];
            $this->ret['template'] = $out['template'];
            $it = $this->getContentItems();
            foreach ($it as $v) {
                if ($v[1] == $out['id']) {
                    $out['children'][$v[0]] = ['alias' => $v[2], 'name' => $v[3]];
                }
            }
            $id = $out['id'];
            $out['breadcrumb'] = [['/', 'Главная']];
            if ($out['parent_id']) {
                $pId = $out['parent_id'];
                while ($pId) {
                    $id = $pId;
                    $pId = $it[$id][1];
                    $out['breadcrumb'][] = [$this->getFullAlias(['parent_id' => $pId, 'id' => $id])];
                }
            }
            $this->ret['active'] = $it[$id][2];
            $dt = explode('-', reset(explode(' ', $out['posted'])));
            $out['date'] = $dt[2] . ' ' . $this->months[(int)$dt[1]] . ' ' . $dt[0];
            $this->ret['js_before'] .= '<script src="/js/photo_viewer_light.js"></script>';
            $this->ret['js_after'] .= '<script>$(document).ready(function(){$(\'section.content\').WMphoto();});</script>';
            $this->ret['style'] .= '<link rel="stylesheet" href="/css/photo_viewer_light_round/photo_viewer_light_round.css" />';
            $this->ret['style'] .= '<link rel="stylesheet" href="/modules/content/css/style.css" />';
            $this->ret['js_before'] .= '<script defer src="/modules/content/js/script.js"></script>';
            ob_start();
            include $this->path . '/data/show_item.html';
            return ob_get_clean();
        }

        function show()
        {
            if ($this->errors) {
                return $this->Util->error($this->errors);
            }
            if($this->type){
                include_once 'video_object_admin.php';
                $C = new VideoObject();
            } else {
                include_once 'photo_object_admin.php';
                $C = new Gallery_PhotoObject();
            }
            if ($_REQUEST['alias']) {
                $tData = array('id', 'parent_id', 'name', 'content', 'img_src', 'title', 'description', 'keywords', 'toprint', 'parent', 'is_text', 'posted', 'active', 'header');
                $item = $this->getItem($tData, $_REQUEST['alias']);
                if ($item) {
                    return $this->showItem($item);
                }
            }
            return $this->Util->error('Not item');
        }

        function getKeyByLevel($it, $level = 0)
        {
            $ret = array();
            if (is_array($it)) {
                foreach ($it as $k => $v) {
                    $ret[$k] = array($k, $level);
                    if ($v['children']) {
                        $itl = $this->getKeyByLevel($v['children'], $level + 1);
                        foreach ($itl as $k => $v) {
                            $ret[$k] = $v;
                        }
                    }
                }
            }
            return $ret;
        }
    }
}
?>