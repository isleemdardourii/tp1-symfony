<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategorieController extends AbstractController
{
    // 1. Liste des catégories
    #[Route('/categories', name: 'app_categories')]
    public function index(CategorieRepository $categorieRepository): Response
    {
        return $this->render('categorie/index.html.twig', [
            'categories' => $categorieRepository->findAll(),
        ]);
    }

    // 2. Créer une catégorie
    #[Route('/categories/nouvelle', name: 'app_categorie_nouvelle')]
    public function nouvelle(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($categorie);
            $em->flush();

            $this->addFlash('success', 'Catégorie créée avec succès !');

            return $this->redirectToRoute('app_categories');
        }

        return $this->render('categorie/nouvelle.html.twig', [
            'formulaire' => $form,
        ]);
    }

    // 3. Afficher une catégorie et ses articles
    #[Route('/categories/{id}', name: 'app_categorie_show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        CategorieRepository $categorieRepository
    ): Response {
        $categorie = $categorieRepository->find($id);

        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        return $this->render('categorie/show.html.twig', [
            'categorie' => $categorie,
        ]);
    }

    // 4. Modifier une catégorie
    #[Route('/categories/{id}/modifier', name: 'app_categorie_modifier', requirements: ['id' => '\d+'])]
    public function modifier(
        int $id,
        Request $request,
        CategorieRepository $categorieRepository,
        EntityManagerInterface $em
    ): Response {
        $categorie = $categorieRepository->find($id);

        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Catégorie modifiée avec succès !');

            return $this->redirectToRoute('app_categories');
        }

        return $this->render('categorie/modifier.html.twig', [
            'formulaire' => $form,
            'categorie' => $categorie,
        ]);
    }

    // 5. Supprimer une catégorie avec protection CSRF
    #[Route('/categories/{id}/supprimer', name: 'app_categorie_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimer(
        int $id,
        Request $request,
        CategorieRepository $categorieRepository,
        EntityManagerInterface $em
    ): Response {
        $categorie = $categorieRepository->find($id);

        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        if (!$this->isCsrfTokenValid(
            'supprimer_categorie_' . $categorie->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // Bonus : empêcher la suppression si des articles existent
        if ($categorie->getArticles()->count() > 0) {
            $this->addFlash(
                'error',
                'Impossible de supprimer cette catégorie : elle contient des articles.'
            );

            return $this->redirectToRoute('app_categories');
        }

        $em->remove($categorie);
        $em->flush();

        $this->addFlash('success', 'Catégorie supprimée avec succès !');

        return $this->redirectToRoute('app_categories');
    }
}
