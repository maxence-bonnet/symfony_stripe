<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Subscription;
use App\Exception\CustomerPortalUnavailableException;
use App\Repository\SubscriptionRepository;
use App\Service\MyStripeService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class MySubscriptionController extends AbstractController
{
    public function __construct (
        private SubscriptionRepository $subscriptionRepository,
        private MyStripeService $stripeService,
        private int $stripeAPICalls = 0,
    )
    {

    }

    #[Route('/my/subscription', name: 'app_my_subscription_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $subscriptions = $this->subscriptionRepository->findAllByCustomerJoinAll($user->getCustomer());

        return $this->render('my_subscription/index.html.twig', [
            'current_nav' => 'my_subscription',
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Customer Stripe Portal where its Subscriptions can be managed
     * We can configure what Customer is allowed to do from its Portal : 
     * https://dashboard.stripe.com/test/settings/billing/portal
     */
    #[Route('/my/subscription/portal', name: 'app_my_subscription_portal')]
    public function portal(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $customer = $user->getCustomer();

        if (null === $customer) {
            throw new CustomerPortalUnavailableException('Unknown Customer');
        }

        $returnUrl = $this->generateUrl('app_my_subscription_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $session = $this->stripeService->createPortalSession($customer, $returnUrl);

        return $this->redirect($session->url, 303);
    }

    #[Route('/my/subscription/{id}', name: 'app_my_subscription_show')]
    public function show(Subscription $subscription): Response
    {
        $subscription = $this->subscriptionRepository->joinAll($subscription);

        return $this->render('my_subscription/show.html.twig', [
            'current_nav' => 'my_subscription',
            'subscription' => $subscription,
        ]);
    }

    #[Route('/my/subscription/{id}/cancel', name: 'app_my_subscription_cancel', methods: ['POST'])]
    public function cancel(Subscription $subscription): Response
    {
        $parameters = [
            'cancel_at_period_end' => true,
        ];

        return $this->updateStripeSubscription('canceling', $subscription, $parameters);
    }

    #[Route('/my/subscription/{id}/resume', name: 'app_my_subscription_resume', methods: ['POST'])]
    public function resume(Subscription $subscription): Response
    {
        $parameters = [
            'cancel_at_period_end' => false,
        ];

        return $this->updateStripeSubscription('active', $subscription, $parameters);
    }

    private function updateStripeSubscription(string $status, Subscription $subscription, array $parameters)
    {
        try {
            $stripeSubscription = $this->stripeService->updateSubscription($subscription->getStripeSubscriptionId(), $parameters);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->addFlash('danger', "Error while updating Subscription : " . $e->getMessage());
            return $this->redirectToRoute('app_my_subscription_index', [], Response::HTTP_SEE_OTHER);
        }
        $this->stripeAPICalls +=1;

        if ($status === 'canceling') {
            $subscription
                ->setEndsAt(\DateTimeImmutable::createFromFormat('U', $stripeSubscription->current_period_end))
                ->setNextInvoiceAt(null);
        } elseif ($status === 'active') {
            $subscription
                ->setEndsAt(null)
                ->setNextInvoiceAt(\DateTimeImmutable::createFromFormat('U', $stripeSubscription->current_period_end));     
        }

        $subscription->setStatus($status);

        $this->subscriptionRepository->add($subscription);

        $this->addFlash('success', "Subscription updated ! Stripe API has been called {$this->stripeAPICalls} time(s).");

        return $this->render('my_subscription/show.html.twig', [
            'current_nav' => 'my_subscription',
            'subscription' => $subscription,
        ]);
    }
}
