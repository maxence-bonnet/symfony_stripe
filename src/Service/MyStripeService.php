<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Price;
use App\Entity\Product;
use App\Entity\TestClock;
use App\Entity\User;
use App\Repository\CustomerRepository;

class MyStripeService
{
    private \Stripe\StripeClient $stripe;

    public function __construct (
        private CustomerRepository $customerRepository
    )
    {
        if ($_ENV['APP_ENV'] === 'dev' || $_ENV['APP_ENV'] === 'test') {
            $apiKey = $_ENV['STRIPE_SECRET_KEY_TEST'];
        } else {
            $apiKey = $_ENV['STRIPE_SECRET_KEY'];
        }

        $this->stripe = new \Stripe\StripeClient($apiKey);
    }

    /** PRODUCT */
    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function createProduct(Product $product): \Stripe\Product
    {
        return $this->stripe->products->create([
            'name' => $product->getName(),
            'description' => $product->getDescription(),
        ]);
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function updateProduct(Product $product): \Stripe\Product
    {
        return $this->stripe->products->update($product->getStripeProductId(), [
            'name' => $product->getName(),
            'description' => $product->getDescription(),                   
        ]);
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function archiveProduct(Product $product): \Stripe\Product
    {
        return $this->stripe->products->update($product->getStripeProductId(), [
            'active' => false,                
        ]);
    }
    /** /PRODUCT */

    /** PRICE */
    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function createPrice(Price $price): \Stripe\Price
    {
        $parameters = [
            'currency' => $price->getCurrency(),
            'product' => $price->getProduct()->getStripeProductId(),
            'unit_amount' => 100 * $price->getPrice(),
        ];
        
        if ($price->getType() === 'recurring') {
            $parameters['recurring'] = [
                'interval' => $price->getRecurringInterval(),
                'interval_count' => $price->getRecurringCount(),
            ];
        }
        return $this->stripe->prices->create($parameters);
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function updatePrice(Price $price): \Stripe\Price
    {
        return $this->stripe->prices->update($price->getStripePriceId(), [
            'active' => false,
        ]);
    }
    /** /PRICE */

    /** CUSTOMER */
    /**
     * Create new Stripe Customer with optional TestClock
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function createCustomer(Customer $customer, ?TestClock $testClock = null): \Stripe\Customer
    {
        $parameters = [
            'email' => $customer->getUser()->getEmail(),
            'name' => $customer->getUser()->getEmail(), // :( 
        ];

        if ($testClock) {
            $parameters['test_clock'] = $testClock->getStripeTestClockId();
        }

        return $this->stripe->customers->create($parameters);
    }

    /**
     * Create new Stripe Customer from given User
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function createCustomerFromUser(User $user): \Stripe\Customer
    {
        return $this->stripe->customers->create([
            'email' => $user->getEmail(),
            'name' => $user->getEmail(), // :( 
        ]);
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function retrieveCustomer(?string $customerId, bool $expand = false): \Stripe\Customer
    {
        $parameters = [];
        if ($expand) {
            $parameters = [
                'expand' => ['default_payment_method']
            ];
        }
        return $this->stripe->customers->retrieve($customerId, $parameters);
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function updateCustomerDefaultPaymentMethod(?string $customerId, ?string $paymentMethodId): \Stripe\Customer
    {
        $parameters = [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ]
        ];
        return $this->stripe->customers->update($customerId, $parameters);
    }

    /**
     * Permanently deletes a customer. It cannot be undone. Also immediately cancels any active subscriptions on the customer.
     * 
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function deleteCustomer(Customer $customer): \Stripe\Customer
    {
        return $this->stripe->customers->delete($customer->getStripeCustomerId());
    }
    /** /CUSTOMER */

    /** PAYMENT_METHOD */
    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function retrievePaymentMethod(?string $paymentMethodId, array $parameters = null): \Stripe\PaymentMethod
    {
        return $this->stripe->paymentMethods->retrieve($paymentMethodId, $parameters);
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function updatePaymentMethod(?string $paymentMethodId, array $parameters): \Stripe\PaymentMethod
    {
        return $this->stripe->paymentMethods->retrieve($paymentMethodId, $parameters);
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function attachPaymentMethod(?string $paymentMethodId, ?string $customerId): \Stripe\PaymentMethod
    {
        return $this->stripe->paymentMethods->attach($paymentMethodId, ['customer' => $customerId]);
    }
    /** /PAYMENT_METHOD */

    /** SUBSCRIPTION */
    /**
     * Create a new Stripe Subscription for given User.
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function createSubscription(Customer $customer, Price $price): \Stripe\Subscription
    {
         return $this->stripe->subscriptions->create([
            'customer' => $customer->getStripeCustomerId(),
            'items' => [[
                'price' => $price->getStripePriceId(),
            ]],
            'payment_behavior' => 'default_incomplete',
            'expand' => ['latest_invoice.payment_intent'],
        ]);
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function updateSubscription(string $stripeSubscriptionId, $parameters = null): ?\Stripe\Subscription
    {
        return $this->stripe->subscriptions->update($stripeSubscriptionId, $parameters);
    }

    /**
     * Retrieve Stripe Subscription from its id
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function retrieveSubscription(string $stripeSubscriptionId, $expand = false): ?\Stripe\Subscription
    {
        $parameters = null;
        if ($expand) {
            /**
             * 'expand' allows direct access to 'lastest_invoice' -> 'payment_intent' -> 'client_secret', what we are looking for
             */
            $parameters = [
                'expand' => ['latest_invoice.payment_intent'],
            ];
        }
        return $this->stripe->subscriptions->retrieve($stripeSubscriptionId, $parameters);
    }
    /** /SUBSCRIPTION */

    /** INVOICE */
    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     * @return \Stripe\Invoice with payment_intent expanded
     */
    public function finalizeInvoice(string $stripeInvoiceId, $parameters = null): ?\Stripe\Invoice
    {
        // $parameters = array_merge(['expand' => 'payment_intent']);
        return $this->stripe->invoices->finalizeInvoice($stripeInvoiceId, ['expand' => ['payment_intent']]);
    }

    /** /INVOICE */


    /** PORTAL */

    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function createPortalSession(Customer $customer, string $returnUrl): \Stripe\BillingPortal\Session
    {
        return $this->stripe->billingPortal->sessions->create([
          'customer' => $customer->getStripeCustomerId(),
          'return_url' => $returnUrl,            
        ]);
    }
    /** /PORTAL */

    /** TESTCLOCK */
    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function createTestClock(TestClock $testClock): \Stripe\TestHelpers\TestClock
    {
        return $this->stripe->testHelpers->testClocks->create([
            'frozen_time' => $testClock->getFrozenTime()->format('U'),
        ]);
    } 

    /**
     * The time to advance the test clock. 
     * 
     * Note : must be after the test clock’s current frozen time.
     * Cannot be more than two intervals in the future from the shortest subscription in this test clock. 
     * If there are no subscriptions in this test clock, it cannot be more than two years in the future.
     * 
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function advanceTestClock(TestClock $testClock): \Stripe\TestHelpers\TestClock
    {
        return $this->stripe->testHelpers->testClocks->advance($testClock->getStripeTestClockId(), [
            'frozen_time' => $testClock->getFrozenTime()->format('U'),
        ]);
    } 

    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    public function deleteTestClock(TestClock $testClock): \Stripe\TestHelpers\TestClock
    {
        return $this->stripe->testHelpers->testClocks->delete($testClock->getStripeTestClockId());
    }
    /** /TESTCLOCK */
}