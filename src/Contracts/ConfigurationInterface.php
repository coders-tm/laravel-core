<?php

namespace Coderstm\Contracts;

interface ConfigurationInterface
{
    public function isValid();

    public function optimizeResponse($request, $response);
}
