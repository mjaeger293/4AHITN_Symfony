<?php

namespace App\Controller; // ähnlich einem Package

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response; // ähnlich zu einem import
use Symfony\Component\Routing\Annotation\Route;

class RandomController extends AbstractController
{
    /**
     * @Route("/random/{min}/{max}", name="random", requirements={"min"="\d+", "max"="\d+"}, methods={"GET"})
     */
    public function randomizeAction(int $min, int $max): Response { // Standardwert: HTL STEYR, auch wenn kein / nach echo
        $result = rand($min, $max);

        return $this->render('random/index.html.twig', [ // in templates-Verzeichnis
            'controller_name' => 'HelloWorldController', // Variablen für twig datei
            'min' => $min,
            'max' => $max,
            'result' => $result
        ]);
    }
}
