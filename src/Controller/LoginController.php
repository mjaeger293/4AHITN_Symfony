<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Customer;
use App\Service\AuthService;
use App\Service\CartServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthService $authService, Request $request): Response
    {
        $form = $authService->loginForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer = $form->getData();
            if ($authService->login($customer)) {
                return $this->redirectToRoute("all_products");
            }
        }

        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController',
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(Request $request, AuthService $authService): Response
    {
        $authService->logout();
        return $this->redirectToRoute("all_products");
    }

    #[Route('/register', name: 'app_register')]
    public function register(AuthService $authService, Request $request, ManagerRegistry $doctrine, CartServiceInterface $cartService, RequestStack $requestStack): Response
    {
        $form = $authService->registerForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var $customer Customer
             */
            $customer = $form->getData();

            if ($doctrine->getRepository(Customer::class)->findOneBy(['email' => $customer->getEmail()]) == null) {
                $cart = $cartService->getCart();
                $customer->setCart($cart);
                $cart->setSessionId(null);
                $cart->setCustomer($customer);

                $doctrine->getManager()->persist($customer);
                $doctrine->getManager()->persist($cart);
                $doctrine->getManager()->flush();

                if ($authService->login($customer)) {
                    if ($requestStack->getSession()->get("order_registration")) {
                        $requestStack->getSession()->remove("order_registration");

                        return $this->redirectToRoute("cart_order");
                    } else {
                        return $this->redirectToRoute("all_products");
                    }
                }
            }

        }

        return $this->render('register/index.html.twig', [
            'form' => $form,
        ]);
    }
}
