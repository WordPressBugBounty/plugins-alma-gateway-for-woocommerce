<?php

namespace Alma\Vendor\Alma\Plugin\Application\Port;

use Alma\Vendor\Alma\Client\Application\DTO\EligibilityDto;
use Alma\Vendor\Alma\Client\Domain\Entity\EligibilityList;

interface EligibilityProviderInterface
{
    /**
     * Retrieve the eligibility list based on the current cart total.
     */
    public function retrieveEligibility(EligibilityDto $eligibilityDto) : void;

    /**
     * Get the eligibility list.
     */
    public function getEligibilityList(EligibilityDto $eligibilityDto): EligibilityList;
}
