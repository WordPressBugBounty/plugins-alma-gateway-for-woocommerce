<?php

namespace Alma\Vendor\Alma\Plugin\Infrastructure\Repository;

use Alma\Vendor\Alma\Plugin\Infrastructure\Adapter\ProductAdapterInterface;

interface ProductRepositoryInterface {
    public function getById(int $productId): ProductAdapterInterface;
}
