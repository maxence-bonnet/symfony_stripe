<?php

namespace App\Controller;

use App\Entity\Price;
use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\MyEntityService;
use App\Service\MyStripeService;
use Doctrine\Common\Collections\Collection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProductController extends AbstractController
{
    public function __construct(
        private MyStripeService $stripeService,
        private MyEntityService $entityService,
        private int $stripeAPICalls = 0,
    )
    {
        
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProductRepository $productRepository): Response
    {
        $price = new Price();
        $product = (new Product())->addPrice($price);
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** Creating a new Stripe Product */
            try {
                /** Stripe\Entity */
                $newStripeProduct = $this->stripeService->createProduct($product);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->addFlash('danger', "Error while creating Product : " . $e->getMessage());
                return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
            }
            $this->stripeAPICalls +=1;
            /** Saving its id */
            $product->setStripeProductId($newStripeProduct->id);

            /** Creating a new Stripe Price for this product for each Price provied */
            foreach ($product->getPrices() as $price) {
                try {
                    /** Stripe\Entity */
                    $newStripePrice = $this->stripeService->createPrice($price);
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    $this->addFlash('danger', "Error while creating Price : " . $e->getMessage());
                    return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
                }
                $this->stripeAPICalls +=1;
                /** Saving its id */
                $price->setStripePriceId($newStripePrice->id);
            }

            $productRepository->add($product);

            $this->addFlash('success', "The Product has been successfully created. Stripe API has been called {$this->stripeAPICalls} time(s).");

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/regular/new.html.twig', [
            'current_nav' => 'product_new',
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, ProductRepository $productRepository): Response
    {
        if ($product->getPrices()->isEmpty()) {
            $product->addPrice(new Price());
        }

        $previousProduct = clone $product;
        $previousPrices = clone $product->getPrices();

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newProduct = $product;
            $newPrices = $product->getPrices();

            /** Checking Product fields updates */
            try {
                $this->checkProduct($previousProduct, $newProduct);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->addFlash('danger', "Error while updating Product : " . $e->getMessage());
                return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
            }
            
            /** 
             * Checking Prices fields updates (additions or deletions), unfortunatly
             * Prices' main attributes (currency, unit_amount, recurring options, ...) 
             * can not be updated via API
             */
            try {
                $this->checkPrices($previousPrices, $newPrices);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $this->addFlash('danger', "Error while updating Prices : " . $e->getMessage());
                return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
            }

            $productRepository->add($product);

            $this->addFlash('success', "The Product has been successfully updated. Stripe API has been called {$this->stripeAPICalls} time(s).");

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/regular/edit.html.twig', [
            'current_nav' => 'product',
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('subscription/regular/show.html.twig', [
            'current_nav' => 'product',
            'product' => $product,
        ]);
    }
    /** /REGULAR SUBSCRIPTION */

    /** SCHEDULED SUBSCRIPTION */
    // #[Route('/new/schedule', name: 'app_subscription_new', methods: ['GET', 'POST'])]
    // public function newScheduled(Request $request, SubscriptionRepository $subscriptionRepository): Response
    // {
    //     $price = new Price();
    //     $stripeProduct = (new Product())->addPrice($price);
    //     $subscription = (new Subscription())->setProduct($stripeProduct);
    //     $form = $this->createForm(SubscriptionType::class, $subscription);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $subscriptionRepository->add($subscription);
    //         return $this->redirectToRoute('app_subscription_index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->renderForm('subscription/schedule/new.html.twig', [
    //         'subscription' => $subscription,
    //         'form' => $form,
    //     ]);
    // }   
    /** /SCHEDULED SUBSCRIPTION */

    /** SHARED */
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'current_nav' => 'product',
            'products' => $productRepository->findAllJoinStripe(),
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, ProductRepository $productRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $this->stripeService->archiveProduct($product);
            $productRepository->remove($product);
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
    /** /SHARED */

    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    private function checkProduct(Product $previous, Product $new): void
    {
        if ($this->entityService->entityIsModified($previous, $new)) {
            /** Updating Stripe Product */
            $this->stripeService->updateProduct($new);
            $this->stripeAPICalls +=1;
        }
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException - if the request fails
     */
    private function checkPrices(Collection $previousPrices, Collection $newPrices): void
    {
        /** 1] Checking deletion */
        /** @var Price $previousPrice */
        foreach ($previousPrices as $previousPrice) {
            if (!$newPrices->contains($previousPrice)) {
                /** Price has been deleted, set 'active' from 'true' to 'false', deletion itself does not seem possible via API */
                $this->stripeService->updatePrice($previousPrice);
                $this->stripeAPICalls +=1;
            }
        }

        /** 2] Checking addition */
        /** @var Price $newPrice */
        foreach ($newPrices as $newPrice) {
            if (!$previousPrices->contains($newPrice)) {
                /** Creating a new Price */
                $newStripePrice = $this->stripeService->createPrice($newPrice);
                $this->stripeAPICalls +=1;
                /** Saving its id */
                $newPrice->setStripePriceId($newStripePrice->id);
            }
        }
    }
}
