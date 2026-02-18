<?php

namespace Alma\Vendor\Alma\Plugin\Infrastructure\Repository;

interface GatewayRepositoryInterface {
    public function findOrderedAlmaGateways(): array;
}
