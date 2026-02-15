<?php

namespace Coderstm\Contracts;

interface SubscriptionGateway
{
    public function setup(mixed $options = null): array;

    public function getProviderId();

    public function completeSetup($setupId);

    public function create(array $options = []);

    public function update(array $params = []);

    public function cancel(array $metadata = []);

    public function charge($description, array $metadata = []);

    public function updatePlan(bool $hasIntervalChanged, bool $hasPriceChanged);
}
