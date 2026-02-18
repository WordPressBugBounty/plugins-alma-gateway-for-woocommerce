<?php

namespace Alma\Vendor\Alma\Plugin\Infrastructure\Repository;

use Alma\Vendor\Alma\Plugin\Infrastructure\Adapter\OrderAdapterInterface;

interface OrderRepositoryInterface {
    public function getById(int $orderId): OrderAdapterInterface;
}
