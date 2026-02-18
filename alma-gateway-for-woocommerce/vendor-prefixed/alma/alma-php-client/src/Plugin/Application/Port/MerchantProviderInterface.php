<?php

namespace Alma\Vendor\Alma\Plugin\Application\Port;

use Alma\Vendor\Alma\Client\Application\DTO\MerchantBusinessEvent\CartInitiatedBusinessEventDto;
use Alma\Vendor\Alma\Client\Application\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEventDto;

interface MerchantProviderInterface
{
    /**
     * @param CartInitiatedBusinessEventDto $cartEventData
     * @return void
     */
    public function sendCartInitiatedBusinessEvent(CartInitiatedBusinessEventDto $cartEventData): void;

    /**
     * @param OrderConfirmedBusinessEventDto $orderConfirmedBusinessEvent
     * @return void
     */
    public function sendOrderConfirmedBusinessEvent(OrderConfirmedBusinessEventDto $orderConfirmedBusinessEvent): void;
}
