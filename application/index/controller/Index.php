<?php

namespace app\index\controller;

use think\Controller;
use app\index\model\CityCode;
use think\Db;

class Index extends Controller
{
    public function index()
    {

        return 'http://tj.nineton.cn/Heart/index/all';
    }
}
