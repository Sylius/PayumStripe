<?php

declare(strict_types=1);

namespace FluxSE\PayumStripe\Action\Api\WebhookEvent;

use Stripe\Event;
use Stripe\PaymentIntent;

final class PaymentIntentAutomaticAsyncSucceededAction extends AbstractPaymentIntentAction
{
    protected function getSupportedEventTypes(): array
    {
        return [
            Event::PAYMENT_INTENT_SUCCEEDED,
        ];
    }

    protected function getSupportedCaptureMethod(): string
    {
        return PaymentIntent::CAPTURE_METHOD_AUTOMATIC_ASYNC;
    }
}
