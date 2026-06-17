<?php

declare(strict_types=1);

namespace FluxSE\PayumStripe\Action;

use FluxSE\PayumStripe\Request\Api\Resource\AllInvoice;
use FluxSE\PayumStripe\Request\Api\Resource\RetrievePaymentIntent;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\GetStatusInterface;
use Stripe\Checkout\Session;
use Stripe\Invoice;
use Stripe\PaymentIntent;

class StatusSessionAction extends AbstractStatusAction
{
    public function isMarkedStatus(GetStatusInterface $request, ArrayObject $model): bool
    {
        /** @var string|null $status */
        $status = $model->offsetGet('status');
        if (null === $status) {
            return false;
        }

        /** @var string $paymentStatus */
        $paymentStatus = $model->offsetGet('payment_status');

        if ($this->isCaptureStatus($status, $paymentStatus)) {
            $request->markCaptured();

            return true;
        }

        if ($this->isExpiredStatus($status)) {
            $request->markExpired();

            return true;
        }

        $isPendingStatus = $this->isPendingStatus($status, $paymentStatus);
        // The Session `status` is 'complete' but the `payment_status` is 'not_paid'
        // meaning the payment is processing, or it failed
        if ($isPendingStatus && $this->isSubscriptionCanceledStatus($model)) {
            $request->markCanceled();

            return true;
        }

        if ($isPendingStatus) {
            $request->markPending();

            return true;
        }

        if ($this->isNewStatus($status, $paymentStatus)) {
            $request->markNew();

            return true;
        }

        return false;
    }

    protected function isCaptureStatus(string $status, string|null $paymentStatus): bool
    {
        if (Session::STATUS_COMPLETE !== $status) {
            return false;
        }

        return Session::PAYMENT_STATUS_UNPAID !== $paymentStatus;
    }

    protected function isExpiredStatus(string $status): bool
    {
        return Session::STATUS_EXPIRED === $status;
    }

    /**
     * Check the underlying PaymentIntent attached to the subscription
     * to know the payment_status of the subscription.
     * (only way to do that because there is no info on the Session object).
     */
    protected function isSubscriptionCanceledStatus(ArrayObject $model): bool
    {
        /** @var string|null $subscriptionId */
        $subscriptionId = $model->offsetGet('subscription');
        if (null === $subscriptionId) {
            return false;
        }

        $paymentIntent = $this->retrievePaymentIntentRelatedToASubscription($subscriptionId);
        if (null === $paymentIntent) {
            return false;
        }

        /* @see https://stripe.com/docs/payments/intents */
        return in_array($paymentIntent->offsetGet('status'), [
            PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
            PaymentIntent::STATUS_CANCELED,
        ], true);
    }

    protected function retrievePaymentIntentRelatedToASubscription(string $subscriptionId): ?PaymentIntent
    {
        $allInvoice = new AllInvoice([
            'subscription' => $subscriptionId,
            'limit' => 1,
        ]);
        $this->gateway->execute($allInvoice);

        /** @var Invoice|null $invoice */
        $invoice = $allInvoice->getApiResources()->first();
        if (null === $invoice) {
            return null;
        }

        /** @var string|null $paymentIntentId */
        $paymentIntentId = $invoice->offsetGet('payment_intent');
        if (null === $paymentIntentId) {
            return null;
        }
        $retrievePaymentIntent = new RetrievePaymentIntent($paymentIntentId);
        $this->gateway->execute($retrievePaymentIntent);

        /** @var PaymentIntent $paymentIntent */
        $paymentIntent = $retrievePaymentIntent->getApiResource();

        return $paymentIntent;
    }

    protected function isPendingStatus(string $status, string|null $paymentStatus): bool
    {
        if (Session::STATUS_COMPLETE !== $status) {
            return false;
        }

        return Session::PAYMENT_STATUS_UNPAID === $paymentStatus;
    }

    protected function isNewStatus(string $status, string|null $paymentStatus): bool
    {
        if (Session::STATUS_OPEN !== $status) {
            return false;
        }

        return in_array($paymentStatus, [
            Session::PAYMENT_STATUS_NO_PAYMENT_REQUIRED,
            Session::PAYMENT_STATUS_UNPAID,
        ], true);
    }

    public function getSupportedObjectName(): string
    {
        return Session::OBJECT_NAME;
    }
}
