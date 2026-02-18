<?php

namespace Alma\Vendor\Alma\Plugin\Application\Port;

use Alma\Vendor\Alma\Client\Domain\Entity\FeePlanList;

interface FeePlanProviderInterface
{
    /**
     * Get the fee plan list.
     *
     * @param bool $forceRefresh Whether to force a refresh of the fee plan list.
     *
     * @return FeePlanList
     */
    public function getFeePlanList( bool $forceRefresh = false ): FeePlanList;
}
