<?php

namespace App\Entity;

use App\Repository\OrderProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderProductRepository::class)]
class OrderProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'orderProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private $orderObj;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'orderProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private $productObj;

    #[ORM\Column(type: 'integer')]
    private $amount;

    #[ORM\Column(type: 'decimal', precision: 9, scale: 2)]
    private $price;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderObj(): ?Order
    {
        return $this->orderObj;
    }

    public function setOrderObj(?Order $orderObj): self
    {
        $this->orderObj = $orderObj;

        return $this;
    }

    public function getProductObj(): ?Product
    {
        return $this->productObj;
    }

    public function setProductObj(?Product $productObj): self
    {
        $this->productObj = $productObj;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }
}
