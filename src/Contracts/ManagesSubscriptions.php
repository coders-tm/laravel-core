<?php

namespace Coderstm\Contracts;

use Carbon\Carbon;

interface ManagesSubscriptions
{
    public function valid(): bool;

    public function incomplete(): bool;

    public function expired(): bool;

    public function active(): bool;

    public function recurring(): bool;

    public function canceled(): bool;

    public function onTrial(): bool;

    public function hasDowngrade(): bool;

    public function onGracePeriod(): bool;

    public function hasIncompletePayment(): bool;

    public function swap(int $planId, bool $invoiceNow = true): self;

    public function cancelDowngrade(): self;

    public function renew(): self;

    public function trialDays(int $trialDays): self;

    public function trialUntil($trialUntil): self;

    public function skipTrial(): self;

    public function endTrial(): self;

    public function cancel(): self;

    public function cancelNow(): self;

    public function resume(): self;

    public function withCoupon(?string $coupon): self;

    public function upcomingInvoice(bool $start = false, ?Carbon $dateFrom = null);

    public function pay($paymentMethod, array $options = []): self;

    public function cancelOpenInvoices(): self;

    public function paymentConfirmation($order = null): self;

    public function paymentFailed($order = null): self;

    public function saveAndInvoice(array $options = [], bool $force = false): self;

    public function saveWithoutInvoice(array $options = []): self;

    public function sendRenewNotification(): void;
}
