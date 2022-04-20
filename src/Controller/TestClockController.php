<?php

namespace App\Controller;

use App\Entity\TestClock;
use App\Form\TestClockAdvanceType;
use App\Form\TestClockType;
use App\Repository\TestClockRepository;
use App\Service\MyEntityService;
use App\Service\MyStripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/test-clock')]
class TestClockController extends AbstractController
{
    public function __construct (
        private MyStripeService $stripeService,
        private MyEntityService $entityService,
        private int $stripeAPICalls = 0,
    )
    {

    }

    #[Route('/', name: 'app_test_clock_index', methods: ['GET'])]
    public function index(TestClockRepository $testClockRepository): Response
    {
        return $this->render('test_clock/index.html.twig', [
            'current_nav' => 'test_clock',
            'testClocks' => $testClockRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_test_clock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, TestClockRepository $testClockRepository): Response
    {
        $testClock = new TestClock();
        $form = $this->createForm(TestClockType::class, $testClock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $stripeTextClock = $this->stripeService->createTestClock($testClock);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->addFlash('danger', "Error while creating TestClock : " . $e->getMessage());
                return $this->redirectToRoute('app_test_clock_index', [], Response::HTTP_SEE_OTHER);
            }
            $this->stripeAPICalls +=1;

            $testClock->setStripeTestClockId($stripeTextClock->id);
            $testClockRepository->add($testClock);

            $this->addFlash('success', "TestClock created ! Stripe API has been called {$this->stripeAPICalls} time(s).");
            return $this->redirectToRoute('app_test_clock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('test_clock/new.html.twig', [
            'current_nav' => 'test_clock',
            'testClock' => $testClock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_test_clock_show', methods: ['GET'])]
    public function show(TestClock $testClock): Response
    {
        return $this->render('test_clock/show.html.twig', [
            'current_nav' => 'test_clock',
            'testClock' => $testClock,
        ]);
    }

    #[Route('/{id}/advance', name: 'app_test_clock_advance', methods: ['GET', 'POST'])]
    public function advance(Request $request, TestClock $testClock, TestClockRepository $testClockRepository): Response
    {
        $form = $this->createForm(TestClockAdvanceType::class, $testClock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $stripeTextClock = $this->stripeService->advanceTestClock($testClock);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->addFlash('danger', "Error while advancing TestClock : " . $e->getMessage());
                return $this->redirectToRoute('app_test_clock_index', [], Response::HTTP_SEE_OTHER);
            }
            $this->stripeAPICalls +=1;

            $testClockRepository->add($testClock);

            $this->addFlash('success', "TestClock advanced ! Stripe API has been called {$this->stripeAPICalls} time(s).");
            return $this->redirectToRoute('app_test_clock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('test_clock/advance.html.twig', [
            'current_nav' => 'test_clock',
            'testClock' => $testClock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_test_clock_delete', methods: ['POST'])]
    public function delete(Request $request, TestClock $testClock, TestClockRepository $testClockRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$testClock->getId(), $request->request->get('_token'))) {
            try {
                $stripeTextClock = $this->stripeService->deleteTestClock($testClock);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->addFlash('danger', "Error while deleting TestClock : " . $e->getMessage());
                return $this->redirectToRoute('app_test_clock_index', [], Response::HTTP_SEE_OTHER);
            }
            $this->stripeAPICalls +=1;

            $this->addFlash('success', "TestClock deleted ! Stripe API has been called {$this->stripeAPICalls} time(s).");
            $testClockRepository->remove($testClock);
        }

        return $this->redirectToRoute('app_test_clock_index', [], Response::HTTP_SEE_OTHER);
    }
}
