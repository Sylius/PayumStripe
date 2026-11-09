<?php

declare(strict_types=1);

namespace FluxSE\PayumStripe\Action\Api\WebhookEvent;

use FluxSE\PayumStripe\Request\Api\WebhookEvent\WebhookEvent;
use Stripe\PaymentIntent;
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

        $captureMethod = $paymentIntent->offsetGet('capture_method');
        if (PaymentIntent::CAPTURE_METHOD_AUTOMATIC_ASYNC === $captureMethod) {
            $captureMethod = PaymentIntent::CAPTURE_METHOD_AUTOMATIC;
        }

        if ($this->getSupportedCaptureMethod() === $captureMethod) {
            return $paymentIntent;
        }

        return null;
    }
}
