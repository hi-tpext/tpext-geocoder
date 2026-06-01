# 定位坐标转为地址

## 安装

### 方式1 composer 安装

```bash
composer require ichynul/tpext-geocoder
```

### 方式2 extend 安装

支持`extend`方式在线安装

## 使用方式

GET /api/geocoder/address?lng=xxx&lat=xxx

返回示例：

```json
{
    "code": 1,
    "data": {
        "province": "云南省",
        "city": "昆明市",
        "area": "五华区",
        "province_code": 53,
        "city_code": 5301,
        "area_code": 530102
    }
}
```

## webman 跨越设置

由于 webman 通过控制器判断当前应用

`\tpext\geocoder\api\controller\Geocoder` 识别为 `geocoder` 应用，而不是 `api`

```php
return [
    // 全局中间件
    '' => [
    ],
    'api' => [
        \app\middleware\Cors::class,//跨域中间件（根据实际情况设置）
    ],
    'geocoder' => [
        \app\middleware\Cors::class,//为geocoder也设置一个跨域中间件
    ],
];
```

## 数据源 <https://datav.aliyun.com/portal/school/atlas/area_selector>
