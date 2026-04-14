[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Quality Score][ico-code-quality]][link-code-quality]
[![codecov][ico-codecov]][link-codecov]

## Payum Stripe gateways

This library is design to add gateways to Payum to support Stripe checkout session (with SCA support)
and Stripe JS using Stripe JS Elements.

Apart from the gateways you can use this library to make calls to the Stripe API directly
using `Request` classes : [(All|Create|Delete|Retrieve|Update)*.php](./src/Request/Api/Resource)
which are using the related actions : [(All|Create|Delete|Retrieve|Update)*Action.php](./src/Action/Api/Resource).
You can also build your own `Request/Action` classes to fit your need.

> If you are using Symfony, use the bundle : [flux-se/payum-stripe-bundle](https://github.com/FLUX-SE/PayumStripeBundle)

> If you are using Sylius, use the plugin : [flux-se/sylius-payum-stripe-plugin](https://github.com/FLUX-SE/SyliusPayumStripePlugin)

## Installation

Install using Composer :

```bash
composer require flux-se/payum-stripe
```

Choose one of [php-http/client-implementation](https://packagist.org/providers/php-http/client-implementation),
the most used is [php-http/guzzle7-adapter](https://packagist.org/packages/php-http/guzzle6-adapter)

```bash
composer require  php-http/guzzle6-adapter
```

## Gateways configuration

 - [Stripe Checkout Session](docs/stripe-checkout-session/README.md)

   Support :
   - ["One-time payments"](https://stripe.com/docs/payments/accept-a-payment)
   - ["Place a hold on a card" (Authorize)](https://stripe.com/docs/payments/capture-later)
   - ["Subscription"](https://stripe.com/docs/payments/checkout/subscriptions/starting)
   - ["Set up future payments"](https://stripe.com/docs/payments/save-and-reuse#checkout)

   > Canceling a `PaymentIntent` is also available using `Payum\Core\Request\Cancel`.    
   > Refunding a `PaymentIntent` is also available using `Payum\Core\Request\Refund`.    

 - [Stripe JS](docs/stripe-js/README.md)

   Support :
   - ["Accept a payment"](https://stripe.com/docs/payments/accept-a-payment?integration=elements)
   - ["Place a hold on a card" (Authorize)](https://stripe.com/docs/payments/capture-later)

   > Canceling a `PaymentIntent` is also available using `Payum\Core\Request\Cancel`.    
   > Refunding a `PaymentIntent` is also available using `Payum\Core\Request\Refund`.

## More

### What to use with this payum library ?

 - A Symfony bundle : [flux-se/payum-stripe-bundle](https://github.com/Sylius/PayumStripeBundle)
 - A Sylius plugin : [flux-se/sylius-payum-stripe-plugin](https://github.com/Sylius/PayumStripePlugin)

## Authors

This package was originally created by [Harman Professional, Inc.](https://github.com/flux-se)

Kudos to [Prometee](https://github.com/Prometee) and [all contributors](../../contributors) 🙏

## License

This package is released under the [MIT License](LICENSE).

[ico-version]: https://img.shields.io/packagist/v/FLUX-SE/payum-stripe.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-github-actions]: https://github.com/FLUX-SE/PayumStripe/workflows/Build/badge.svg
[ico-code-quality]: https://img.shields.io/scrutinizer/g/FLUX-SE/PayumStripe.svg?style=flat-square
[ico-codecov]: https://codecov.io/gh/FLUX-SE/PayumStripe/branch/master/graph/badge.svg

[link-packagist]: https://packagist.org/packages/flux-se/payum-stripe
[link-github-actions]: https://github.com/FLUX-SE/PayumStripe/actions?query=workflow%3A"Build"
[link-scrutinizer]: https://scrutinizer-ci.com/g/FLUX-SE/PayumStripe/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/FLUX-SE/PayumStripe
[link-codecov]: https://codecov.io/gh/FLUX-SE/PayumStripe
