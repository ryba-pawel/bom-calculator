<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Material;
use App\Form\MaterialType;
use App\Repository\BomItemRepository;
use App\Repository\MaterialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/materials')]
final class MaterialController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route(name: 'app_material_index', methods: ['GET'])]
    public function index(MaterialRepository $materialRepository): Response
    {
        return $this->render('material/index.html.twig', [
            'materials' => $materialRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_material_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $material = new Material();
        $form = $this->createForm(MaterialType::class, $material);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($material);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Dodano materiał „%s”.', $material->getName()));

            return $this->redirectToRoute('app_material_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('material/new.html.twig', [
            'material' => $material,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_material_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Material $material): Response
    {
        return $this->render('material/show.html.twig', [
            'material' => $material,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_material_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Material $material): Response
    {
        $form = $this->createForm(MaterialType::class, $material);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Zapisano zmiany w materiale „%s”.', $material->getName()));

            return $this->redirectToRoute('app_material_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('material/edit.html.twig', [
            'material' => $material,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_material_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Material $material, BomItemRepository $bomItemRepository): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$material->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Nieprawidłowy token formularza – spróbuj ponownie.');

            return $this->redirectToRoute('app_material_index', [], Response::HTTP_SEE_OTHER);
        }

        $usageCount = $bomItemRepository->count(['material' => $material]);
        if ($usageCount > 0) {
            $this->addFlash('danger', sprintf(
                'Nie można usunąć materiału „%s” – jest używany w BOM (%d poz.). Najpierw usuń go z produktów.',
                $material->getName(),
                $usageCount,
            ));

            return $this->redirectToRoute('app_material_show', ['id' => $material->getId()], Response::HTTP_SEE_OTHER);
        }

        $this->entityManager->remove($material);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('Usunięto materiał „%s”.', $material->getName()));

        return $this->redirectToRoute('app_material_index', [], Response::HTTP_SEE_OTHER);
    }
}
