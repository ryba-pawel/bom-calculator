<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Strona kalkulatora; dane pobiera po stronie przeglądarki z REST API (public/js/calculator.js).
 */
final class CalculatorController extends AbstractController
{
    #[Route('/kalkulator', name: 'app_calculator', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('calculator/index.html.twig');
    }
}
