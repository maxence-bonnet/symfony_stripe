<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use App\Service\MyEntityService;
use App\Service\MyStripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/customer')]
class CustomerController extends AbstractController
{
    public function __construct (
        private MyStripeService $stripeService,
        private MyEntityService $entityService,
        private int $stripeAPICalls = 0,
    )
    {

    }

    #[Route('/', name: 'app_customer_index', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository): Response
    {
        return $this->render('customer/index.html.twig', [
            'current_nav' => 'customer',
            'customers' => $customerRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CustomerRepository $customerRepository): Response
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** Stripe\Entity */
                $stripeCustomer = $this->stripeService->createCustomer($customer, $customer->getTestClock());
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->addFlash('danger', "Error while creating Customer : " . $e->getMessage());
                return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
            }
            $this->stripeAPICalls +=1;

            $customer->setStripeCustomerId($stripeCustomer->id);
            $customerRepository->add($customer);

            $this->addFlash('success', "Customer created ! Stripe API has been called {$this->stripeAPICalls} time(s).");
            return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('customer/new.html.twig', [
            'current_nav' => 'customer_new',
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_customer_show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        return $this->render('customer/show.html.twig', [
            'current_nav' => 'customer',
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}', name: 'app_customer_delete', methods: ['POST'])]
    public function delete(Request $request, Customer $customer, CustomerRepository $customerRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$customer->getId(), $request->request->get('_token'))) {
            try {
                $stripeCustomer = $this->stripeService->deleteCustomer($customer);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->addFlash('danger', "Error while deleting Customer : " . $e->getMessage());
                return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
            }
            $this->stripeAPICalls +=1;

            $this->addFlash('success', "Customer deleted ! Stripe API has been called {$this->stripeAPICalls} time(s).");
            $customerRepository->remove($customer);
        }

        return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
    }
}
