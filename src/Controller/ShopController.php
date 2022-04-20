<?php

namespace App\Controller;

use App\Entity\Price;
use App\Entity\User;
use App\Entity\Subscription;
use App\Repository\ProductRepository;
use App\Service\MyEntityService;
use App\Service\MyStripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/shop')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ShopController extends AbstractController
{
    /**
     * Public stripe apikey 
     */
    private string $publicKey;

    public function __construct (
        private MyStripeService $stripeService,
        private MyEntityService $entityService,
        private int $stripeAPICalls = 0,
    )
    {
        if ($_ENV['APP_ENV'] === 'dev' || $_ENV['APP_ENV'] === 'test') {
            $this->publicKey = $_ENV['STRIPE_PUBLIC_KEY_TEST'];
        } else {
            $this->publicKey = $_ENV['STRIPE_PUBLIC_KEY'];
        }
    }

    #[Route('/', name: 'app_shop_index')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAllJoinStripe();

        return $this->render('shop/index.html.twig', [
            'products' => $products,
            'current_nav' => 'shop',
        ]);
    }

    /**
     * Subscription checkout integrated with Stripe Element (https://stripe.com/docs/billing/subscriptions/build-subscriptions?ui=elements).
     * Here we build a subscription based on the Price chosen by the customer (User)
     */
    #[Route('/checkout/{id}', name: 'app_shop_checkout')]
    public function checkout(Price $price): Response
    {
        $product = $price->getProduct();

        if (!$product) {
            throw new NotFoundHttpException('Unknown Product');
        }

        /** @var User $user */
        $user = $this->getUser();
        $customer = $user->getCustomer();
        if (null === $customer) {
            try {
                /** Stripe\Entity */
                $stripeCustomer = $this->stripeService->createCustomerFromUser($user);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->addFlash('danger', "Error while creating Customer : " . $e->getMessage());
                return $this->redirectToRoute('app_shop_index', [], Response::HTTP_SEE_OTHER);
            }
            $this->stripeAPICalls +=1;

            /** App\Entity */
            $customer = $this->entityService->createCustomer($user, $stripeCustomer);
            $this->entityService->add(true, $customer);
        }
        
        if ($product->getPurpose() === 'product') {
            throw new \Exception('Regular products not handled yet');
            // LATER
        }

        if ($product->getPurpose() === 'subscription') {
            /** Subscription created from chosen Price */
            try {
                /** Stripe\Entity */
                $stripeSubscription = $this->stripeService->createSubscription($customer, $price);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->addFlash('danger', "Error while creating Subscription : " . $e->getMessage());
                return $this->redirectToRoute('app_shop_index', [], Response::HTTP_SEE_OTHER);
            }
            $this->stripeAPICalls +=1;

            /** App\Entity */
            $subscription = $this->entityService->createSubscription($customer, $price, $stripeSubscription);
            if ($customer->getTestClock()) {
                $immutable = \DateTimeImmutable::createFromMutable($customer->getTestClock()->getFrozenTime());
                $subscription->setCreatedAt($immutable);
            }
            $this->entityService->add(true, $subscription);

            $this->addFlash('success', "You are going to buy a product ! Stripe API has been called {$this->stripeAPICalls} time(s).");

            $clientSecret = $stripeSubscription->latest_invoice->payment_intent->client_secret;
            $returnUrl = $this->generateUrl('app_shop_subscription_result', [
                'subscription_id' => $stripeSubscription->id, // additional parameter given to generate return_url
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        
        return $this->render('shop/stripe-element.html.twig', [
            'current_nav' => 'shop',
            'stripePublicKey' => $this->publicKey,
            'product' => $product,
            'clientSecret' => $clientSecret,
            'returnUrl' => $returnUrl,
        ]);
    }

    /**
     * Retry a previously failed payment.
     * 
     * For this to work, we need to retrieve the failed 'payment_intent' & associated 'client_secret' from Stripe Subscription
     */
    #[Route('/retry/subscription/{id}', name: 'app_shop_retry_subscription', methods: ['POST'])]
    public function checkoutRetry(Subscription $subscription, Request $request): Response
    {                
        if ($this->isCsrfTokenValid('retry'.$subscription->getId(), $request->request->get('_token'))) {
            /** the goal here is to retrieve or create an usable payment_intent to get its client_secret */
            $clientSecret = null;
            /** Retrive Subscription */
            try {
                /** $expand => true to retrieve all needed informations */
                $stripeSubscription = $this->stripeService->retrieveSubscription($subscription->getStripeSubscriptionId(), true);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->addFlash('danger', "Error while creating Customer : " . $e->getMessage());
                return $this->redirectToRoute('app_shop_index', [], Response::HTTP_SEE_OTHER);
            }
            $this->stripeAPICalls +=1;

            // invoice->status = one of ['draft', 'open', 'paid', 'uncollectible', 'void']
            // payment_intent->status = one of ['requires_payment_method', 'requires_confirmation', 'requires_action', 'processing', 'requires_capture', 'canceled', 'succeeded']
            switch ($stripeSubscription->latest_invoice->status) {
                case 'open' : /** last payment_intent can still be used ' */
                    $clientSecret = $stripeSubscription->latest_invoice->payment_intent->client_secret;
                    break;
                case 'draft': /** Last payment_intent failed, last invoice has been voided and a new one has been created for a next attempt*/
                    try {
                        /** finalizing the new draft Invoice generated by Stripe, invoice->status becomes 'open' */
                        $stripeInvoice = $this->stripeService->finalizeInvoice($stripeSubscription->latest_invoice->id);
                    } catch (\Stripe\Exception\ApiErrorException $e) {
                        $this->addFlash('danger', "Error while finalizing Invoice : " . $e->getMessage());
                        return $this->redirectToRoute('app_shop_index', [], Response::HTTP_SEE_OTHER);
                    }
                    $this->stripeAPICalls +=1;
                    $clientSecret = $stripeInvoice->payment_intent->client_secret;
                    break;
                default :
                    // Error :(
                    break;                
            }

            $returnUrl = $this->generateUrl('app_shop_subscription_result', [
                'subscription_id' => $stripeSubscription->id, // additional parameter given to generate return_url
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $product = $subscription->getPrice()->getProduct();

            $this->addFlash('success', "You are going to buy a product ! Stripe API has been called {$this->stripeAPICalls} time(s).");
            
            return $this->render('shop/stripe-element.html.twig', [
                'current_nav' => 'shop',
                'stripePublicKey' => $this->publicKey,
                'product' => $product,
                'clientSecret' => $clientSecret,
                'returnUrl' => $returnUrl,
            ]);
        }
        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Result route after a payment action built from the 'return_url' given previously to Stripe Element Page
     * 
     * Example of URL generated: 
     * https://127.0.0.1:8000/shop/subscription/success?subscription_id=sub_1KmLFrGl9zFk4v1kdGR0wKa7&payment_intent=pi_3KmLFsGl9zFk4v1k11qEWDPQ&payment_intent_client_secret=pi_3KmLFsGl9zFk4v1k11qEWDPQ_secret_wQxtk2y8OYcDydfAgcQ0EV1uZ&redirect_status=succeeded
     * 
     * In any case, it will not be necessary to do update anything here since related events 
     * such as 'invoice.payment_failed', 'invoice.payment_succeeded', etc... are handled by Webhooks
     */
    #[Route('/subscription/result', name: 'app_shop_subscription_result')]
    public function subscriptionResult(Request $request): Response
    {        
        $subscription = $this->entityService->getSubscriptionFromStripeId($request->get('subscription_id'));
        if (!$request->get('redirect_status') || !$request->get('redirect_status') === 'succeeded') {
            return $this->render('shop/cancel.html.twig', [
                'paymentIntent' => $request->get('payment_intent'),
                'product' => $subscription->getPrice()->getProduct(),
                'current_nav' => 'subscription',
            ]);
        }
        return $this->render('shop/success.html.twig', [
            'product' => $subscription->getPrice()->getProduct(),
            'current_nav' => 'subscription',
        ]);
    }
}
