<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use App\Service\CostCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', format: 'json')]
final class ProductApiController extends AbstractController
{
    /** Polskie znaki w odpowiedzi zamiast sekwencji \uXXXX. */
    private const JSON_CONTEXT = [
        'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS | \JSON_UNESCAPED_UNICODE,
    ];

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CostCalculator $costCalculator,
    ) {
    }

    #[Route(name: 'api_product_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(
            $this->productRepository->findBy([], ['name' => 'ASC']),
            context: [...self::JSON_CONTEXT, 'groups' => ['product:list']],
        );
    }

    #[Route('/{id}/cost', name: 'api_product_cost', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function cost(int $id): JsonResponse
    {
        $product = $this->productRepository->findWithBom($id);

        if (null === $product) {
            return $this->json(
                ['error' => sprintf('Produkt o id %d nie istnieje.', $id)],
                Response::HTTP_NOT_FOUND,
                context: self::JSON_CONTEXT,
            );
        }

        return $this->json($this->costCalculator->calculate($product), context: self::JSON_CONTEXT);
    }
}
