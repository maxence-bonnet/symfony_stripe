<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\UserSubscription;
use App\Repository\SubscriptionRepository;
use App\Service\MyEntityService;
use App\Service\MyStripeService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebHookController extends AbstractController
{
    /**
     * Days until access granted by subscription is removed, for example
     */
    const DAYS_ACCESS_AFTER_UNPAID_INVOICE = 2; 

    /**
     * @var string $webHookSecret
     */
    private $webHookSecret;

    public function __construct (
        private ManagerRegistry $managerRegistry,
        private MyStripeService $stripeService,
        private MyEntityService $entityService,
        private LoggerInterface $logger,
    )
    {
        if ($_ENV['APP_ENV'] === 'dev') {
            // while testing webHookSecret is provided by Stripe CLI (>Stripe listen)
            $this->webHookSecret = $_ENV['STRIPE_WEBHOOK_SECRET_TEST'];
        } else {
            // in prod envrionnement we need to declare our Webhook route in Stripe Dashboard to obtain webHookSecret
            $this->webHookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];
        }
    }

    #[Route('/webhook', methods: ['POST'])]
    public function stripeWebhooks(Request $request)
    {
        $signature = $request->headers->get('stripe-signature');
        $payload = $request->getContent();
        $event = null;
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        try {
            $event = \Stripe\Event::constructFrom(json_decode($payload, true));
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            file_put_contents(dirname(__DIR__) . '/../logs/logs-errors.log', "\n {$now} : {$event->type} [{$event->id}] : {$e->getMessage()}", FILE_APPEND);
            return new Response('', 400);
        }

        if ($this->webHookSecret) {
            try {
                $event = \Stripe\Webhook::constructEvent($payload, $signature, $this->webHookSecret);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                // Invalid signature
                file_put_contents(dirname(__DIR__) . '/../logs/logs-errors.log', "\n {$now} : {$event->type} [{$event->id}] : {$e->getMessage()}", FILE_APPEND);
                return new Response('', 400);
            } catch (\Exception $e) {
                file_put_contents(dirname(__DIR__) . '/../logs/logs-errors.log', "\n {$now} : {$event->type} [{$event->id}] : {$e->getMessage()}", FILE_APPEND);
            } 
        }
        file_put_contents(dirname(__DIR__) . '/../logs/logs.log', "\n {$now} : {$event->type} [{$event->id}]", FILE_APPEND);

        /**
         * @TODO : find a way to update default payment method if the last one failed.
         * For now can't attach + define default any PaymentMethod after payment_intent.succeeded or charge.succeeded 
         * or invoice.payment_succeeded because : "This PaymentMethod was previously used without being attached to 
         * a Customer or was detached from a Customer, and may not be used again." ... :( 
         * 
         */
        switch ($event->type) {
            case 'customer.subscription.updated':
                $this->handleSubscriptionEvent($event);           
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionEvent($event); 
                break;
            case 'invoice.created':
                $this->handleInvoiceEvent($event);
                break;
            case 'invoice.payment_failed':
                $this->handleInvoiceEvent($event);
                break;
            case 'invoice.payment_succeeded':
                $this->handleInvoiceEvent($event);
                break;
            case 'invoice.payment_action_required':
                $this->handleInvoiceEvent($event);
                break;
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event);
                // contains a \Stripe\PaymentIntent
                // Then define and call a method to handle the successful payment intent.
                // handlePaymentIntentSucceeded($paymentIntent);
                break;
            case 'payment_method.attached':
                $this->handlePaymentMethodEvent($event);
                // $paymentMethod = $event->data->object; // contains a \Stripe\PaymentMethod
                // // Then define and call a method to handle the successful attachment of a PaymentMethod.
                // // handlePaymentMethodAttached($paymentMethod);
                break;
            default:
            file_put_contents(dirname(__DIR__) . '/../logs/logs-not-handled.log', "\n {$now} : {$event->type} [{$event->id}]", FILE_APPEND);
        }
        return new JsonResponse('', 200);
    }

    private function handleSubscriptionEvent(\Stripe\Event $event): void
    {
        /** @var \Stripe\Subscription $stripeSubscription  */
        $stripeSubscription = $event->data->object;
        $subscription = $this->retrieveSubscription($stripeSubscription->id);
        if ($subscription) {
            $currentPeriodEnd = \DateTimeImmutable::createFromFormat('U', $stripeSubscription->current_period_end);
            switch ($event->type) {
                case 'customer.subscription.updated':
                    if ($stripeSubscription->cancel_at_period_end) {
                        // Subscription has been canceled
                        $subscription
                            ->setStatus('canceling') // This is a custom status to specify that the subscription will be ended soon (while Stripe status remains 'active')
                            ->setEndsAt($currentPeriodEnd)
                            ->setNextInvoiceAt(null);
                    } elseif ($stripeSubscription->status === 'active') {
                        // Subscription has been resumed ?
                        $subscription
                            ->setStatus($stripeSubscription->status)
                            ->setEndsAt(null)
                            ->setNextInvoiceAt($currentPeriodEnd); // 
                    } else {
                        $subscription->setStatus($stripeSubscription->status);
                    }
                    break;
                case 'customer.subscription.deleted':
                    $subscription
                        ->setStatus($stripeSubscription->status) // This should be 'canceled' (means deleted for Stripe)
                        ->setEndsAt(\DateTimeImmutable::createFromFormat('U', $stripeSubscription->canceled_at))
                        ->setNextInvoiceAt(null);
                    break;
                default:
                    break;
            }            
        }
        $this->persistSubscription($subscription);
    }



    private function handleInvoiceEvent(\Stripe\Event $event): void
    {
        /** @var \Stripe\Invoice $stripeInvoice  */
        $stripeInvoice = $event->data->object;
        if ($stripeInvoice->subscription) {
            try {
                $stripeSubscription = $this->stripeService->retrieveSubscription($stripeInvoice->subscription, true);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $now = (new \DateTime())->format('Y-m-d H:i:s');
                file_put_contents(dirname(__DIR__) . '/../logs/api-calls-errors.log', "\n{$now} : Error while retrieving Subscription {$stripeInvoice->subscription} : {$e->getMessage()}", FILE_APPEND);
            }
            $subscription = $this->retrieveSubscription($stripeSubscription->id);
            if ($subscription) {
                $days = self::DAYS_ACCESS_AFTER_UNPAID_INVOICE;
                $currentPeriodEnd = \DateTimeImmutable::createFromFormat('U', $stripeSubscription->current_period_end);
                $subscription->setLastInvoiceId($stripeSubscription->latest_invoice->id);
                switch ($event->type) {
                    case 'invoice.created':
                        // Common to all events 
                        // $subscription->setLastInvoiceId($stripeSubscription->latest_invoice->id); 
                        break;
                    case 'invoice.payment_failed':
                        $subscription->setStatus($stripeSubscription->status); // This should be 'past_due' or 'incomplete'
                        if ($stripeSubscription->status === 'past_due') {
                            $subscription->setEndsAt($currentPeriodEnd->modify("+ {$days} days"));
                        }
                        break;
                    case 'invoice.action_required':
                        $subscription
                            ->setStatus($stripeSubscription->status) // This should be 'incomplete'
                            ->setEndsAt($currentPeriodEnd->modify("+ {$days} days"));
                        break;
                    case 'invoice.payment_succeeded':
                        $this->checkSubscriptionPaymentMethod($stripeSubscription);
                        $subscription
                            ->setStatus($stripeSubscription->status) // This should be 'active'
                            ->setEndsAt(null) // Not sure about this
                            ->setNextInvoiceAt($currentPeriodEnd);
                        break;
                    default:
                        break;
                }
                $this->persistSubscription($subscription);
            }
        }
    }
    private function handlePaymentIntentSucceeded(\Stripe\Event $event): void
    {

        // /** @var \Stripe\PaymentIntent $paymentIntent */
        // $paymentIntent = $event->data->object;

        // try {
        //     $stripePaymentMethod = $this->stripeService->retrievePaymentMethod($paymentIntent->payment_method);
        // } catch (\Stripe\Exception\ApiErrorException $e) {
        //     $now = (new \DateTime())->format('Y-m-d H:i:s');
        //     file_put_contents(dirname(__DIR__) . '/../logs/api-calls-errors.log', "\n{$now} : Error while retrieving Payment Method {$paymentIntent->payment_method} : {$e->getMessage()}", FILE_APPEND);
        // }

        // try {
        //     $stripeCustomer = $this->stripeService->retrieveCustomer($paymentIntent->customer, true);
        // } catch (\Stripe\Exception\ApiErrorException $e) {
        //     $now = (new \DateTime())->format('Y-m-d H:i:s');
        //     file_put_contents(dirname(__DIR__) . '/../logs/api-calls-errors.log', "\n{$now} : Error while retrieving Customer {$paymentIntent->customer} : {$e->getMessage()}", FILE_APPEND);
        // }

        // /** @var ?\Stripe\PaymentMethod $paymentMethod */
        // $paymentMethod = $stripeCustomer->invoice_settings->default_payment_method;
        // if (null !== $paymentMethod) {
        //     $parameters = [
        //         'card' => $paymentMethod->card,
        //     ];
        //     try {
        //         $this->stripeService->updatePaymentMethod($stripePaymentMethod->id, $parameters);
        //     } catch (\Stripe\Exception\ApiErrorException $e) {
        //         $now = (new \DateTime())->format('Y-m-d H:i:s');
        //         file_put_contents(dirname(__DIR__) . '/../logs/api-calls-errors.log', "\n{$now} : Error while retrieving Customer {$paymentIntent->customer} : {$e->getMessage()}", FILE_APPEND);
        //     }
        // }
    }

    private function handlePaymentMethodEvent(\Stripe\Event $event): void
    {
        /** @var \Stripe\PaymentMethod $paymentMethod  */
        $paymentMethod = $event->data->object;
        switch ($event->type) {
            case 'payment_method.attached':
                $this->updateCustomerDefaultPaymentMethod($paymentMethod->customer, $paymentMethod->id);
            default:
                break;
        }
    }

    private function checkSubscriptionPaymentMethod(\Stripe\Subscription $stripeSubscription): void
    {
        $currentPaymentMethodId = $stripeSubscription->default_payment_method;
        $lastPaymentMethodId = $stripeSubscription->latest_invoice->payment_intent->payment_method;
        
        if ($currentPaymentMethodId !== $lastPaymentMethodId) {
            try {
                $this->stripeService->updateSubscription($stripeSubscription->id, [
                    'default_payment_method' => $lastPaymentMethodId,
                ]);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $now = (new \DateTime())->format('Y-m-d H:i:s');
                file_put_contents(dirname(__DIR__) . '/../logs/api-calls-errors.log', "\n{$now} : Error while updating Subscription {$stripeSubscription->id} to update default payment method : {$e->getMessage()}", FILE_APPEND);
            }    
        }
    }

    private function updateCustomerDefaultPaymentMethod(?string $customerId, ?string $paymentMethodId): void
    {
        try {
            $this->stripeService->updateCustomerDefaultPaymentMethod($customerId, $paymentMethodId);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $now = (new \DateTime())->format('Y-m-d H:i:s');
            file_put_contents(dirname(__DIR__) . '/../logs/api-calls-errors.log', "\n{$now} : Error while updating Customer {$customerId} to update default payment method : {$e->getMessage()}", FILE_APPEND);
        }       
    }

    private function retrieveSubscription(?string $stripeSubscriptionId): ?Subscription
    {
        /** @var SubscriptionRepository $subscriptionRepository */
        $subscriptionRepository = $this->managerRegistry->getRepository(Subscription::class);
        return $subscriptionRepository->findOneBy(['stripeSubscriptionId' => $stripeSubscriptionId]);
    }

    private function persistSubscription(Subscription $subscription): void
    {
        /** @var SubscriptionRepository $subscriptionRepository */
        $subscriptionRepository = $this->managerRegistry->getRepository(Subscription::class);
        $subscriptionRepository->add($subscription);
    }
}