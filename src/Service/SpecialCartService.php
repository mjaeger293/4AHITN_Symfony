<?php


namespace App\Service;


class SpecialCartService extends CartService
{
    public function getNumProducts(): int
    {
        $cart = $this->getCart();

        $products = $cart->getProducts();

        return $products->count();
    }
}
