<?php

namespace App\Service;

use App\Entity\Customer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AuthService
{
    private SessionInterface $session;
    private ManagerRegistry $registry;
    private FormFactoryInterface $formFactory;
    private UrlGeneratorInterface $router;

    /**
     * @var Customer|null
     * Local instance of the logged in customer.
     * This instance is within every request the same object,
     * because the AuthService - Class is autowired and therefore
     * it's treated like a singleton.
     */
    private ?Customer $customer = null;

    public function __construct(RequestStack $stack, ManagerRegistry $registry, FormFactoryInterface $formFactory, UrlGeneratorInterface $router)
    {
        $this->registry = $registry;
        $this->session = $stack->getSession();
        $this->formFactory = $formFactory;
        $this->router = $router;

        if (!$this->session->isStarted()) {
            $this->session->start();
            $this->session->set("started", true);
        }
    }

    public function isLoggedIn(): bool
    {
        return $this->getCustomer() != null;
    }

    public function getCustomer(): ?Customer
    {
        if ($this->customer == null && $this->session->get("customer") != null) {
            /**
             * If the customer instance was not loaded yet:
             * -> get it from the database
             *
             * Why?
             * -> because the object saved in the session is
             *    serialized and holds not the same data as
             *    the object loaded from the database.
             */
            $this->customer = $this->registry->getRepository(Customer::class)->find($this->session->get("customer"));
        }

        return $this->customer;
    }

    public function login(Customer $customer): bool
    {
        $customer = $this->registry->getRepository(Customer::class)
            ->findOneBy(
                [
                    "email" => $customer->getEmail(),
                    "password" => $customer->getPassword()
                ]
            );

        if ($customer != null) {
            $this->session->set("customer", $customer);
            return true;
        }

        return false;
    }

    public function logout(): void
    {
        $this->session->remove("customer");
    }

    public function loginForm(): FormInterface
    {
        $customer = new Customer();

        $form = $this->formFactory->createBuilder(FormType::class, $customer)
            ->setAction($this->router->generate("app_login"))
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('save', SubmitType::class, ['label' => 'Login'])
            ->getForm();


        return $form;
    }

    public function registerForm(): FormInterface
    {
        $customer = new Customer();

        $form = $this->formFactory->createBuilder(FormType::class, $customer)
            ->setAction($this->router->generate("app_register"))
            ->add('email', EmailType::class)
            ->add('firstname', TextType::class)
            ->add('lastname', TextType::class)
            ->add('street', TextType::class)
            ->add('zip', NumberType::class)
            ->add('city', TextType::class)
            ->add('password', RepeatedType::class,
                ['type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'first_options'  => ['label' => 'Passwort'],
                'second_options' => ['label' => 'Passwort wiederholen']])
            ->add('save', SubmitType::class, ['label' => 'Registrieren'])
            ->getForm();

        return $form;
    }
}
