<?php

namespace Simp\Public\Module\custom;

use Simp\Core\components\rest_data_source\RestDataSource;
use Simp\Core\lib\routes\Route;

class ProductDataSource extends RestDataSource
{
    public function __construct(Route $route, array $options)
    {
        parent::__construct($route, $options);
    }
}