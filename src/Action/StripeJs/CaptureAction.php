<?php

declare(strict_types=1);

namespace FluxSE\PayumStripe\Action\StripeJs;

use ArrayObject;
use FluxSE\PayumStripe\Action\AbstractCaptureAction;
use FluxSE\PayumStripe\Request\Api\Resource\CreatePaymentIntent;
use FluxSE\PayumStripe\Request\CaptureAuthorized;
use FluxSE\PayumStripe\Request\StripeJs\Api\RenderStripeJs;
use Payum\Core\Request\Generic;
use Stripe\ApiResource;
use Stripe\PaymentIntent;

class CaptureAction extends AbstractCaptureAction
{
    /**
     * PaymentIntent statuses that still require the buyer to pay, i.e. the Elements form
     * must be (re)rendered so the very same intent can be confirmed.
     */
    private const PAYABLE_PAYMENT_INTENT_STATUSES = [
        PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
        PaymentIntent::STATUS_REQUIRES_CONFIRMATION,
        PaymentIntent::STATUS_REQUIRES_ACTION,
    ];

    protected function createApiResource(ArrayObject $model, Generic $request): ApiResource
    {
        $createRequest = new CreatePaymentIntent($model->getArrayCopy());
        $this->gateway->execute($createRequest);

        return $createRequest->getApiResource();
    }

    protected function render(ApiResource $captureResource, Generic $request): void
    {
        $token = $this->getRequestToken($request);
        $actionUrl = $token->getAfterUrl();

        $renderRequest = new RenderStripeJs($captureResource, $actionUrl);
        $this->gateway->execute($renderRequest);
    }

    protected function processNotNew(ArrayObject $model, Generic $request): void
    {
        parent::processNotNew($model, $request);

        // Specific case of authorized payments being captured
        // If it isn't an authorized PaymentIntent then nothing is done
        $captureAuthorizedRequest = new CaptureAuthorized($this->getRequestToken($request));
        $captureAuthorizedRequest->setModel($model);
        $this->gateway->execute($captureAuthorizedRequest);

        // When the PaymentIntent is reused while still awaiting payment (e.g. the buyer reloaded
        // the payment page before paying), re-render the Stripe Elements form so the very same
        // intent can be confirmed — without this, Payum would redirect to the after URL and leave
        // the buyer unable to complete the payment.
        /** @var string|null $status */
        $status = $model->offsetGet('status');
        if (!in_array($status, self::PAYABLE_PAYMENT_INTENT_STATUSES, true)) {
            return;
        }

        $this->render(PaymentIntent::constructFrom($model->getArrayCopy()), $request);
    }
}
