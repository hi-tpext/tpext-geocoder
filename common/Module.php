<?php

namespace tpext\geocoder\common;

use tpext\common\Module as baseModule;

class Module extends baseModule
{
    protected $version = '1.0.2';

    protected $name = 'tpext.geocoder';

    protected $title = '定位坐标转为地址';

    protected $description = '坐标转为地址，支持省市区县三级';

    protected $root = __DIR__ . '/../';

    protected $modules = [
        'api' => ['geocoder'],
    ];
}
