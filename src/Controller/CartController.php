<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Service\AuthService;
use App\Service\CartServiceInterface;
use App\Service\OrderService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    private SessionInterface $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();

        if (!$this->session->isStarted()) {
            $this->session->start();
            $this->session->set("started", true);
        }
    }


    #[Route('/cart/add/{productId}', name: 'add_to_cart', requirements: ['productId' => '\d+'], methods: ["POST"])]
    public function add(Request $request, ManagerRegistry $doctrine, CartServiceInterface $service, int $productId): Response
    {
        $amount = (int) $request->get("amount");

        $product = $doctrine->getRepository(Product::class)->find($productId);

        $service->addToCart($product, $amount);

        return $this->redirectToRoute('show_cart');
    }

    #[Route('/cart/remove/{productId}', name: 'remove_from_cart', requirements: ['productId' => '\d+'])]
    public function remove(ManagerRegistry $doctrine, CartServiceInterface $service, int $productId): Response
    {
        $product = $doctrine->getRepository(Product::class)->find($productId);

        $service->removeFromCart($product);

        return $this->redirectToRoute('show_cart');
    }

    #[Route('/cart/show', name: 'show_cart')]
    public function show(ManagerRegistry $doctrine, CartServiceInterface $service): Response
    {
        $manager = $doctrine->getManager();

        /**
         * @var $cart Cart
         */
        $cart = $service->getCart();

        if ($cart == null) {
            $cart = new Cart();
            $cart->setSessionId($this->session->getId());
            $cart->setDate(new \DateTime());

            $manager->persist($cart);
            $manager->flush();
        }

        $totalprice = 0;
        foreach($cart->getProducts() as $cartProduct) {
            $totalprice += $cartProduct->getAmount() * $cartProduct->getProduct()->getPrice();
        }


        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'totalprice' => $totalprice,
        ]);
    }

    #[Route('/cart/makeorder', name: 'cart_order')]
    public function order(OrderService $orderService, AuthService $authService): Response
    {
        if ($authService->isLoggedIn()) { //$this->session->get("customer") != null
            $this->session->set("order_registration", true);
            $orderService->createOrderFromCart();
        } else {
            return $this->redirectToRoute("app_register");
        }

        return $this->redirectToRoute("all_products");
    }
}
