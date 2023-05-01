<?php

namespace App\Service;

use App\Entity\CartProduct;
use App\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\Cart;

class CartService implements CartServiceInterface
{
    private SessionInterface $session;
    private ManagerRegistry $registry;
    private AuthService $authService;

    public function __construct(RequestStack $stack, ManagerRegistry $registry, AuthService $authService)
    {
        $this->registry = $registry;
        $this->session = $stack->getSession();
        $this->authService = $authService;

        if ($this->session->isStarted()) {
            $this->session->start();
            $this->session->set("started", true);
        }

        $this->cleanUp();
    }

    public function getCart(): Cart {
        if ($this->authService->isLoggedIn()) {
            /*$cart = $this->registry->getRepository(Cart::class)->findOneBy(
                ["customer" => $this->authService->getCustomer()]
            );*/

            $cart = $this->authService->getCustomer()->getCart();
        } else {
            $cart = $this->registry->getRepository(Cart::class)->findOneBy(
                ["sessionId" => $this->session->getId()]
            );
        }

        if ($cart == null) {
            $cart = new Cart();

            if ($this->authService->isLoggedIn()) {
                $cart->setCustomer($this->authService->getCustomer());
            } else {
                $cart->setSessionId($this->session->getId());
            }

            $cart->setDate(new \DateTime());
        }

        return $cart;
    }

    public function getNumProducts(): int {
        $cart = $this->getCart();

        $products = $cart->getProducts();
        $numproducts = 0;
        foreach ($products as $product) {
            $numproducts += $product->getAmount();
        }

        return $numproducts;
    }

    public function addToCart(Product $product, int $amount = 1): void
    {
        $cart = $this->getCart();

        $cartProduct =
            $this->registry->getRepository(CartProduct::class)
                ->findOneBy(["cart" => $cart, "product" => $product]);

        if ($cartProduct == null) {
            $cartProduct = new CartProduct();
            $cart->addProduct($cartProduct);
            $cartProduct->setCart($cart);
            $cartProduct->setProduct($product);
        }

        $cartProduct->setAmount($cartProduct->getAmount() + $amount);

        //$this->registry->getManager()->persist($this->authService->getCustomer());

        $this->registry->getManager()->persist($cartProduct);
        $this->registry->getManager()->persist($cart);

        $this->registry->getManager()->flush();
    }

    public function removeFromCart(Product $product, int $amount = 1): void
    {
        $cart = $this->getCart();

        $cartProduct =
            $this->registry->getRepository(CartProduct::class)
                ->findOneBy(["cart" => $cart, "product" => $product]);

        $cartProduct->setAmount($cartProduct->getAmount() - $amount);

        if ($cartProduct->getAmount() <= 0) {
            $this->registry->getManager()->remove($cartProduct);
        } else {
            $this->registry->getManager()->persist($cartProduct);
        }

        $this->registry->getManager()->persist($cart);
        $this->registry->getManager()->flush();
    }

    public function cleanUp(): void
    {
        /**
         * Remove all data (> 7 days ago) for
         * not logged in users.
         */

        $date = new \DateTime();
        $date->modify("-7 days");

        $builder = $this->registry->getManager()->getRepository(Cart::class)->createQueryBuilder("c")
            ->delete()
            ->where('c.date < :date')
            ->andWhere('c.sessionId IS NOT NULL')
            ->andWhere('c.customer IS NULL')
            ->setParameter(':date', $date);

        $builder->getQuery()->execute();

    }

    public function emptyCart(): void
    {
        $this->registry->getManager()->remove($this->getCart());
        $this->registry->getManager()->flush();
    }
}
