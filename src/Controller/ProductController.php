<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\BomItem;
use App\Entity\Product;
use App\Form\BomItemType;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\CostCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly CostCalculator $costCalculator,
    ) {
    }

    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('product/index.html.twig', [
            'costs' => array_map($this->costCalculator->calculate(...), $this->productRepository->findAllWithBom()),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Dodano produkt „%s”. Teraz uzupełnij jego BOM.', $product->getName()));

            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    /**
     * Karta produktu: BOM, podsumowanie kosztów i formularz dodawania pozycji.
     * Id najwyżej 18 cyfr, żeby zawsze mieściło się w int (dłuższe dają 404 z routingu).
     */
    #[Route('/{id}', name: 'app_product_show', requirements: ['id' => '\d{1,18}'], methods: ['GET', 'POST'])]
    public function show(Request $request, int $id): Response
    {
        $product = $this->productRepository->findWithBom($id)
            ?? throw $this->createNotFoundException(sprintf('Produkt o id %d nie istnieje.', $id));

        $bomItem = (new BomItem())->setProduct($product);
        $form = $this->createForm(BomItemType::class, $bomItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->addBomItem($bomItem);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Dodano do BOM: %s.', $bomItem->getMaterial()?->getName()));

            return $this->redirectToRoute('app_product_show', ['id' => $id], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'cost' => $this->costCalculator->calculate($product),
            'bom_form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product): Response
    {
        // Nazwa sprzed handleRequest() – przy błędnym formularzu encja ma już wysłane (np. puste) dane
        $productName = $product->getName();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Zapisano zmiany w produkcie „%s”.', $product->getName()));

            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'product_name' => $productName,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_product_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Product $product): Response
    {
        if (!$this->isCsrfTokenValid('delete-product'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Nieprawidłowy token formularza – spróbuj ponownie.');

            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()], Response::HTTP_SEE_OTHER);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Usunięto produkt „%s”.', $product->getName()));

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route(
        '/{id}/bom/{bomItemId}/delete',
        name: 'app_product_bom_item_delete',
        requirements: ['id' => '\d+', 'bomItemId' => '\d+'],
        methods: ['POST'],
    )]
    public function deleteBomItem(
        Request $request,
        Product $product,
        #[MapEntity(id: 'bomItemId')] BomItem $bomItem,
    ): Response {
        if ($bomItem->getProduct() !== $product) {
            throw $this->createNotFoundException('Ta pozycja nie należy do BOM tego produktu.');
        }

        if (!$this->isCsrfTokenValid('delete-bom-item'.$bomItem->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Nieprawidłowy token formularza – spróbuj ponownie.');

            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()], Response::HTTP_SEE_OTHER);
        }

        // Bez inicjalizacji całej kolekcji Product::$bomItems tylko po to, żeby usunąć jedną pozycję
        $this->entityManager->remove($bomItem);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Usunięto z BOM: %s.', $bomItem->getMaterial()?->getName()));

        return $this->redirectToRoute('app_product_show', ['id' => $product->getId()], Response::HTTP_SEE_OTHER);
    }
}
