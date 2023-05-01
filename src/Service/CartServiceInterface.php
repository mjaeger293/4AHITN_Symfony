<?php


namespace App\Service;


use App\Entity\Cart;

interface CartServiceInterface
{
    public function getCart(): Cart;

    public function getNumProducts(): int;

    public function cleanUp(): void;

    public function emptyCart(): void;
}
