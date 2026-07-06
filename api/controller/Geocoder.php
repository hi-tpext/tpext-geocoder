<?php

namespace tpext\geocoder\api\controller;

use think\Controller;
use tpext\geocoder\common\Geocoder as GCoder;

class Geocoder extends Controller
{
    /**
     * 直辖市 adcode
     */
    protected $municipal = [110000, 120000, 310000, 500000];

    /**
     * 根据经纬度坐标查找省市区
     * GET /api/geocoder/address?lng=102.71445&lat=25.07481
     */
    public function address()
    {
        $lng = input('lng', '');
        $lat = input('lat', '');

        if ($lng === '' || $lat === '') {
            return json(['code' => 0, 'msg' => '参数错误：缺少经纬度坐标']);
        }
        $coder = new GCoder;
        $res = $coder->address($lng, $lat);

        return json($res);
    }
}
