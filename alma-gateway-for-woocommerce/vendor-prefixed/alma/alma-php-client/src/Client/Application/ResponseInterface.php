<?php

namespace Alma\Vendor\Alma\Client\Application;

use Alma\Vendor\Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface ResponseInterface extends PsrResponseInterface
{

    public function isError(): bool;
}
