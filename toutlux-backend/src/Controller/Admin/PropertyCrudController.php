<?php

namespace App\Controller\Admin;

use App\Entity\Property;
use App\Form\Admin\PropertyType;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/properties')]
#[IsGranted('ROLE_ADMIN')]
class PropertyCrudController extends AbstractController
{
    public function __construct(
        private PropertyRepository $propertyRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_properties_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;

        $criteria = [];

        // Filtres
        $type = $request->query->get('type');
        if ($type) {
            $criteria['type'] = $type;
        }

        $available = $request->query->get('available');
        if ($available !== null) {
            $criteria['available'] = $available === '1';
        }

        $verified = $request->query->get('verified');
        if ($verified !== null) {
            $criteria['verified'] = $verified === '1';
        }

        $properties = $this->propertyRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );

        $totalProperties = $this->propertyRepository->count($criteria);

        return $this->render('admin/property/index.html.twig', [
            'properties' => $properties,
            'totalProperties' => $totalProperties,
            'page' => $page,
            'pages' => ceil($totalProperties / $limit),
            'filters' => [
                'type' => $type,
                'available' => $available,
                'verified' => $verified
            ]
        ]);
    }

    #[Route('/{id}', name: 'admin_properties_show')]
    public function show(Property $property): Response
    {
        return $this->render('admin/property/show.html.twig', [
            'property' => $property,
            'images' => $property->getImages(),
            'messages' => $this->entityManager->getRepository(\App\Entity\Message::class)
                ->findByProperty($property)
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_properties_edit')]
    public function edit(Request $request, Property $property): Response
    {
        $form = $this->createForm(PropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Propriété modifiée avec succès.');

            return $this->redirectToRoute('admin_properties_show', ['id' => $property->getId()]);
        }

        return $this->render('admin/property/edit.html.twig', [
            'property' => $property,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_properties_delete', methods: ['POST'])]
    public function delete(Request $request, Property $property): Response
    {
        if ($this->isCsrfTokenValid('delete'.$property->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($property);
            $this->entityManager->flush();

            $this->addFlash('success', 'Propriété supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_properties_index');
    }

    #[Route('/{id}/toggle-verified', name: 'admin_properties_toggle_verified', methods: ['POST'])]
    public function toggleVerified(Property $property): Response
    {
        $property->setVerified(!$property->isVerified());
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'verified' => $property->isVerified()
        ]);
    }

    #[Route('/{id}/toggle-featured', name: 'admin_properties_toggle_featured', methods: ['POST'])]
    public function toggleFeatured(Property $property): Response
    {
        $property->setFeatured(!$property->isFeatured());
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'featured' => $property->isFeatured()
        ]);
    }

    #[Route('/{id}/toggle-available', name: 'admin_properties_toggle_available', methods: ['POST'])]
    public function toggleAvailable(Property $property): Response
    {
        $property->setAvailable(!$property->isAvailable());
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'available' => $property->isAvailable()
        ]);
    }

    #[Route('/stats/overview', name: 'admin_properties_stats')]
    public function stats(): Response
    {
        $stats = $this->propertyRepository->getStatistics();
        $featured = $this->propertyRepository->findFeatured(10);
        $mostViewed = $this->propertyRepository->findMostViewed(10);
        $recent = $this->propertyRepository->findRecent(7, 10);

        return $this->render('admin/property/stats.html.twig', [
            'stats' => $stats,
            'featured' => $featured,
            'mostViewed' => $mostViewed,
            'recent' => $recent
        ]);
    }
}
