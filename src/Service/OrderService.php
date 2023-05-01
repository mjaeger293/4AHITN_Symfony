<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderProduct;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OrderService
{
    private ManagerRegistry $registry;
    private FormFactory $formFactory;
    private UrlGeneratorInterface $router;
    private CartServiceInterface $cartService;
    private AuthService $authService;

    /**
     * @param ManagerRegistry $registry
     * @param FormFactory $formFactory
     * @param UrlGeneratorInterface $router
     * @param CartServiceInterface $cartService
     * @param AuthService $authService
     */
    public function __construct(ManagerRegistry $registry,
                                FormFactoryInterface $formFactory,
                                UrlGeneratorInterface $router,
                                CartServiceInterface $cartService,
                                AuthService $authService)
    {
        $this->registry = $registry;
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->cartService = $cartService;
        $this->authService = $authService;
    }

    public function createOrderFromCart() {
        $cart = $this->cartService->getCart();

        // create a new order - Object
        $order = new Order();

        $totalPrice = 0;

        // create order products
        foreach ($cart->getProducts() as $cartProduct) {
            $orderProduct = new OrderProduct();
            $orderProduct->setAmount($cartProduct->getAmount());
            $orderProduct->setPrice($cartProduct->getProduct()->getPrice());
            $orderProduct->setProductObj($cartProduct->getProduct());
            $orderProduct->setOrderObj($order);

            $totalPrice += $orderProduct->getPrice() * $orderProduct->getAmount();

            $order->addOrderProduct($orderProduct);
            $this->registry->getManager()->persist($orderProduct);
        }

        // link the customer to the order
        $order->setCustomer($this->authService->getCustomer());
        $order->setDate(new \DateTime());
        $order->setState(Order::ORDER_OPEN);
        $order->setPrice($totalPrice);

        // publish to database
        $this->registry->getManager()->persist($order);
        $this->registry->getManager()->flush();

        $this->cartService->emptyCart();
    }
}
