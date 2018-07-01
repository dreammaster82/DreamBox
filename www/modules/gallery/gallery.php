<?php
namespace gallery{
    use core;
    use PHPMailer\PHPMailer\Exception;

    class Gallery extends core\CContent{
        protected $config = array(
            'admin' => 'gallery',
            'table' => 'gallery',
            'items_on_page' => 20,
            'pages_in_line' => 25,
            'is_cache' => 1,
            'is_main_cache' => 1,
            'reqId' => 'cId'
        );

        function __construct($m = ''){
            parent::__construct(__CLASS__, $m);
            $this->type = (int)$_REQUEST['v'];
        }

        function parseRequest($request) {
            $ret = array();
            $arr = explode ('/', $request);
            if (sizeof($arr) > 3 || !$arr[0]) $this->setError('badRequest');

            if (!is_numeric($arr[0])) {
                $ret['v'] = array_search($arr[0], ['photo', 'video']);
                array_unshift($arr);
                if (!$ret['v']) $this->setError('badRequest');
            } else {
                $ret['v'] = 0;
            }
            $ret[$this->config['reqId']] = (int)$arr[0];
            if (sizeof($arr) > 1) $ret['id'] = (int)$arr[1];
            return $ret;
        }
    }
}
?>