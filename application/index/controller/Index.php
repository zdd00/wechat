<?php

namespace app\index\controller;

use think\Controller;
use app\index\model\CityCode;
use think\Db;

class Index extends Controller
{
    public function index()
    {
        var_dump(111);
        phpinfo();
        return 'http://tj.nineton.cn/Heart/index/all?city=CHBJ000700';
    }
}
