<?php

namespace tpext\geocoder\common;

class Geocoder
{
    /**
     * 直辖市 adcode
     */
    protected static $municipal = [110000, 120000, 310000, 500000];

    /**
     * 根据经纬度坐标查找省市区
     * GET /api/geocoder/address?lng=102.71445&lat=25.07481
     * @param float|string $lng 经度
     * @param float|string $lat 纬度
     * @return array 区域信息数组 {
     *     "code": 1,
     *     "data": {
     *         "province": "云南省",
     *         "city": "昆明市",
     *         "area": "五华区",
     *         "province_code": "53",
     *         "city_code": "5301",
     *         "area_code": "530102"
     *     }
     * }
     */
    public static function address($lng, $lat)
    {
        $lng = floatval($lng);
        $lat = floatval($lat);

        // 第一步：匹配省份（000000.json）
        $provinces = self::loadFeatures('000000');
        $matchedProvince = null;

        foreach ($provinces as $p) {
            if (self::pointInGeometry($lng, $lat, $p['geometry'])) {
                $matchedProvince = $p;
                break;
            }
        }

        if (!$matchedProvince) {
            return ['code' => 0, 'msg' => '未找到匹配的区域'];
        }

        $provinceProps = $matchedProvince['properties'];
        $provinceCode = $provinceProps['adcode'];
        $provinceName = $provinceProps['name'];

        // 第二步：匹配区县（省文件）
        $districts = self::loadFeatures((string) $provinceCode);
        $matchedDistrict = null;

        foreach ($districts as $d) {
            if (self::pointInGeometry($lng, $lat, $d['geometry'])) {
                $matchedDistrict = $d;
                break;
            }
        }

        $districtName = '';
        $districtCode = '';
        $cityCode = '';
        $cityName = '';

        if ($matchedDistrict) {
            $districtProps = $matchedDistrict['properties'];
            $districtName = $districtProps['name'];
            $districtCode = $districtProps['adcode'];
            $cityCode = $districtProps['parent']['adcode'] ?? 0;

            // 直辖市：区县直接挂在省级下，用省名作为市名
            if (in_array($cityCode, self::$municipal)) {
                $cityName = $provinceName;
            } else {
                $cityName = self::getCityName($cityCode);
            }
        }

        // 如果没有区县，市名用省名兜底
        if (!$cityName) {
            $cityName = $provinceName;
        }
        if (!$cityCode) {
            $cityCode = $provinceCode;
        }

        return [
            'code' => 1,
            'data' => [
                'province' => $provinceName,
                'city' => $cityName,
                'area' => $districtName,
                'province_code' => $provinceCode / 10000,
                'city_code' => $cityCode / 100,
                'area_code' => $districtCode,
            ],
        ];
    }

    /**
     * 加载指定 adcode 的 GeoJSON features（带缓存）
     * @param string $adcode 省份代码，"000000" 表示全国省份文件
     */
    public static function loadFeatures($adcode)
    {
        $areaData = cache('tpext_deocoder_' . $adcode);
        if (!$areaData) {
            $path = Module::getInstance()->getRoot() . "data/{$adcode}.json";
            if (!is_file($path)) {
                $areaData = [];
            } else {
                $content = file_get_contents($path);
                $data = json_decode($content, true);
                $areaData = $data['features'] ?? [];
                cache('tpext_deocoder_' . $adcode, $areaData);
            }
        }

        return $areaData;
    }

    /**
     * 根据城市 adcode 查找城市名称
     * @param string $adcode 城市代码
     * @return string 城市名称
     */
    public static function getCityName($adcode)
    {
        $cityNames = cache('tpext_deocoder_city_names');
        if (!$cityNames) {
            $path = Module::getInstance()->getRoot() . 'data/city_names.json';
            $cityNames = is_file($path) ? json_decode(file_get_contents($path), true) : [];
            cache('tpext_deocoder_city_names', $cityNames);
        }
        return $cityNames[$adcode] ?? '';
    }

    /**
     * 判断点是否在 Geometry 内
     * @param float $lng 经度
     * @param float $lat 纬度
     * @param array $geometry GeoJSON Geometry 对象
     * @return bool
     */
    public static function pointInGeometry($lng, $lat, $geometry)
    {
        $type = $geometry['type'] ?? '';

        if ($type === 'Polygon') {
            return self::pointInPolygonRing($lng, $lat, $geometry['coordinates'][0]);
        }

        if ($type === 'MultiPolygon') {
            foreach ($geometry['coordinates'] as $polygon) {
                if (self::pointInPolygonRing($lng, $lat, $polygon[0])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 射线法判断点是否在多边形环内
     * @param float $lng 经度
     * @param float $lat 纬度
     * @param array $ring 多边形环坐标
     * @return bool
     */
    public static function pointInPolygonRing($lng, $lat, $ring)
    {
        $inside = false;
        $n = count($ring);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $ring[$i][0];
            $yi = $ring[$i][1];
            $xj = $ring[$j][0];
            $yj = $ring[$j][1];

            if (
                (($yi > $lat) !== ($yj > $lat)) &&
                ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)
            ) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
