<?php

namespace Alma\Vendor\Alma\Client\Tests\Integration\Application\Endpoint;

use Alma\Vendor\Alma\Client\Application\CurlClient;
use Alma\Vendor\Alma\Client\Application\Endpoint\AbstractEndpoint;
use Alma\Vendor\Alma\Client\Tests\Integration\ClientTestHelper;
use PHPUnit\Framework\TestCase;

abstract class AbstractEndpointTest extends TestCase
{
    protected ?CurlClient $almaClient;
    protected ?AbstractEndpoint $endpoint;

    public function setUp(): void
    {
        $this->almaClient = ClientTestHelper::getAlmaClient();
    }
    public function tearDown(): void
    {
        $this->almaClient = null;
        $this->endpoint = null;
    }
}