<?php

declare(strict_types=1);

namespace FluxSE\PayumStripe\Action\Api\WebhookEvent;

use FluxSE\PayumStripe\Request\Api\WebhookEvent\WebhookEvent;
use Stripe\StripeObject;

abstract class AbstractPaymentIntentAction extends AbstractPaymentAction
{
    abstract protected function getSupportedCaptureMethod(): string;

    protected function retrieveSessionModeObject(WebhookEvent $request): ?StripeObject
    {
        $paymentIntent = parent::retrieveSessionModeObject($request);

        if (null === $paymentIntent) {
            return null;
        }

        if (false === $paymentIntent->offsetExists('capture_method')) {
            return null;
        }

        if ($this->getSupportedCaptureMethod() === $paymentIntent->offsetGet('capture_method')) {
            return $paymentIntent;
        }

        return null;
    }
}
