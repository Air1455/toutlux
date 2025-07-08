<?php

namespace App\Controller\Admin;

use App\Entity\Property;
use App\Entity\User;
use App\Form\Admin\PropertyType;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/properties')]
#[IsGranted('ROLE_ADMIN')]
class PropertyCrudController extends AbstractController
{
    public function __construct(
        private PropertyRepository $propertyRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    #[Route('', name: 'admin_property_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;

        // Récupérer tous les filtres avec valeurs par défaut
        $filters = [
            'search' => $request->query->get('search', ''),
            'type' => $request->query->get('type', ''),
            'available' => $request->query->get('available', ''),
            'verified' => $request->query->get('verified', ''),
            'featured' => $request->query->get('featured', ''),
            'city' => $request->query->get('city', ''),
            'price_min' => $request->query->get('price_min', ''),
            'price_max' => $request->query->get('price_max', ''),
            'surface_min' => $request->query->get('surface_min', ''),
            'surface_max' => $request->query->get('surface_max', ''),
            'rooms_min' => $request->query->get('rooms_min', ''),
        ];

        // Nettoyer les filtres vides pour la requête
        $activeFilters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        try {
            // Utiliser la méthode de recherche avec filtres avancés
            if (!empty($activeFilters)) {
                $result = $this->propertyRepository->findWithAdvancedFilters($activeFilters, $page, $limit);
            } else {
                // Requête simple si aucun filtre
                $properties = $this->propertyRepository->findBy(
                    [],
                    ['createdAt' => 'DESC'],
                    $limit,
                    ($page - 1) * $limit
                );
                $totalProperties = $this->propertyRepository->count([]);

                $result = [
                    'properties' => $properties,
                    'total' => $totalProperties,
                    'totalPages' => (int) ceil($totalProperties / $limit),
                ];
            }

            $properties = $result['properties'];
            $totalProperties = $result['total'];
            $totalPages = $result['totalPages'];

            // Calculer les statistiques pour le template
            $stats = $this->calculatePropertyStats();

            // Récupérer les villes uniques pour les filtres
            $cities = $this->propertyRepository->findUniqueCities();

            // Récupérer les propriétaires pour les filtres
            $owners = $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('u.roles LIKE :role OR u.roles LIKE :role2')
                ->setParameter('role', '%ROLE_USER%')
                ->setParameter('role2', '%ROLE_OWNER%')
                ->orderBy('u.firstName', 'ASC')
                ->getQuery()
                ->getResult();

            return $this->render('admin/property/index.html.twig', [
                'properties' => $properties,
                'stats' => $stats,
                'cities' => $cities,
                'owners' => $owners,
                'totalProperties' => $totalProperties,
                'page' => $page,
                'totalPages' => $totalPages,
                'filters' => $filters, // Toujours passer tous les filtres avec valeurs par défaut
                'hasActiveFilters' => !empty($activeFilters),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du chargement des propriétés: ' . $e->getMessage(), [
                'filters' => $filters,
                'page' => $page
            ]);

            $this->addFlash('error', 'Une erreur est survenue lors du chargement des propriétés.');

            // Retourner une page vide en cas d'erreur
            return $this->render('admin/property/index.html.twig', [
                'properties' => [],
                'stats' => ['total' => 0, 'available' => 0, 'featured' => 0, 'totalViews' => 0],
                'cities' => [],
                'owners' => [],
                'totalProperties' => 0,
                'page' => 1,
                'totalPages' => 0,
                'filters' => $filters,
                'hasActiveFilters' => false,
            ]);
        }
    }

    #[Route('/new', name: 'admin_property_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $property = new Property();
        $form = $this->createForm(PropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Assigner automatiquement l'utilisateur actuel comme propriétaire si pas défini
                if (!$property->getOwner()) {
                    $property->setOwner($this->getUser());
                }

                // Définir les valeurs par défaut
                $property->setAvailable(true);
                $property->setVerified(false);
                $property->setFeatured(false);

                $this->entityManager->persist($property);
                $this->entityManager->flush();

                $this->logger->info('Nouvelle propriété créée', [
                    'property_id' => $property->getId(),
                    'title' => $property->getTitle(),
                    'created_by' => $this->getUser()->getId()
                ]);

                $this->addFlash('success', 'Propriété créée avec succès.');

                return $this->redirectToRoute('admin_property_show', ['id' => $property->getId()]);

            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la création de propriété: ' . $e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de la création de la propriété.');
            }
        }

        return $this->render('admin/property/new.html.twig', [
            'form' => $form,
            'property' => $property,
        ]);
    }

    #[Route('/export', name: 'admin_property_export')]
    public function export(Request $request): Response
    {
        // Récupérer tous les filtres possibles
        $filters = [
            'ids' => $request->query->get('ids'), // Pour export sélection
            'search' => $request->query->get('search'),
            'type' => $request->query->get('type'),
            'available' => $request->query->get('available'),
            'verified' => $request->query->get('verified'),
            'featured' => $request->query->get('featured'),
            'city' => $request->query->get('city'),
            'price_min' => $request->query->get('price_min'),
            'price_max' => $request->query->get('price_max'),
            'surface_min' => $request->query->get('surface_min'),
            'surface_max' => $request->query->get('surface_max'),
            'rooms_min' => $request->query->get('rooms_min'),
        ];

        // Nettoyer les filtres vides (sauf 'ids' qui peut être vide)
        $filters = array_filter($filters, function($value, $key) {
            return $key === 'ids' || ($value !== null && $value !== '');
        }, ARRAY_FILTER_USE_BOTH);

        try {
            // Utiliser StreamedResponse pour optimiser la mémoire sur de gros exports
            $response = new StreamedResponse(function() use ($filters) {
                $this->streamCsvExport($filters);
            });

            // Générer un nom de fichier descriptif
            $filename = 'properties_export';
            if (!empty($filters['ids'])) {
                $filename .= '_selection';
            } elseif (!empty($filters)) {
                $filename .= '_filtered';
            }
            $filename .= '_' . date('Y-m-d_H-i-s') . '.csv';

            $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');

            $this->logger->info('Export de propriétés', [
                'filters' => array_keys($filters),
                'filename' => $filename
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'export: ' . $e->getMessage(), ['filters' => $filters]);
            $this->addFlash('error', 'Une erreur est survenue lors de l\'export.');
            return $this->redirectToRoute('admin_property_index');
        }
    }

    /**
     * Stream CSV export pour optimiser la mémoire
     */
    private function streamCsvExport(array $filters): void
    {
        $handle = fopen('php://output', 'w');

        // BOM UTF-8 pour Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Headers CSV
        $headers = [
            'ID', 'Titre', 'Type', 'Prix', 'Surface', 'Pièces', 'Chambres',
            'Ville', 'Code postal', 'Propriétaire', 'Email propriétaire',
            'Disponible', 'Vérifié', 'En vedette', 'Vues', 'Date création'
        ];

        fputcsv($handle, $headers, ';');

        // Traitement par batch pour économiser la mémoire
        $batchSize = 100;
        $offset = 0;

        do {
            $properties = $this->propertyRepository->findForExportBatch($filters, $offset, $batchSize);

            foreach ($properties as $property) {
                $row = [
                    $property->getId(),
                    $property->getTitle(),
                    $property->getType() === 'sale' ? 'Vente' : 'Location',
                    $property->getPrice(),
                    $property->getSurface(),
                    $property->getRooms() ?? '',
                    $property->getBedrooms() ?? '',
                    $property->getCity(),
                    $property->getPostalCode() ?? '',
                    $property->getOwner()?->getFullName() ?? '',
                    $property->getOwner()?->getEmail() ?? '',
                    $property->isAvailable() ? 'Oui' : 'Non',
                    $property->isVerified() ? 'Oui' : 'Non',
                    $property->isFeatured() ? 'Oui' : 'Non',
                    $property->getViewCount(),
                    $property->getCreatedAt()?->format('d/m/Y H:i') ?? ''
                ];

                fputcsv($handle, $row, ';');
            }

            $offset += $batchSize;

            // Libérer la mémoire
            $this->entityManager->clear();

        } while (count($properties) === $batchSize);

        fclose($handle);
    }

    #[Route('/stats/overview', name: 'admin_property_stats')]
    public function stats(): Response
    {
        try {
            // Statistiques de base
            $baseStats = $this->propertyRepository->getStatistics();

            // Statistiques par type avec gestion des cas vides
            $byTypeResult = $this->propertyRepository->createQueryBuilder('p')
                ->select('p.type, COUNT(p.id) as count')
                ->groupBy('p.type')
                ->getQuery()
                ->getResult();

            // Transformer les résultats par type en format plus utilisable
            $byTypeFormatted = ['sale' => 0, 'rent' => 0];
            foreach ($byTypeResult as $type) {
                $byTypeFormatted[$type['type']] = (int) $type['count'];
            }

            // Statistiques par ville (top 10) avec gestion des cas vides
            $byCity = [];
            if ($baseStats['total'] > 0) {
                $byCity = $this->propertyRepository->createQueryBuilder('p')
                    ->select('p.city, COUNT(p.id) as count')
                    ->where('p.city IS NOT NULL')
                    ->groupBy('p.city')
                    ->orderBy('count', 'DESC')
                    ->setMaxResults(10)
                    ->getQuery()
                    ->getResult();
            }

            // Prix moyens par type avec gestion des cas vides
            $avgPriceByType = [];
            if ($baseStats['total'] > 0) {
                $avgPriceByType = $this->propertyRepository->createQueryBuilder('p')
                    ->select('p.type, AVG(p.price) as avgPrice')
                    ->groupBy('p.type')
                    ->getQuery()
                    ->getResult();

                // Convertir les prix en entiers
                foreach ($avgPriceByType as &$priceData) {
                    $priceData['avgPrice'] = (int) $priceData['avgPrice'];
                }
            }

            // Propriétés récentes (30 derniers jours)
            $recent = 0;
            if ($baseStats['total'] > 0) {
                $recent = $this->propertyRepository->createQueryBuilder('p')
                    ->select('COUNT(p.id)')
                    ->where('p.createdAt >= :date')
                    ->setParameter('date', new \DateTimeImmutable('-30 days'))
                    ->getQuery()
                    ->getSingleScalarResult();
            }

            // Combiner toutes les statistiques
            $stats = array_merge($baseStats, [
                'byType' => $byTypeFormatted,
                'byCity' => $byCity,
                'avgPriceByType' => $avgPriceByType,
                'recent' => (int) $recent,
            ]);

            return $this->render('admin/property/stats.html.twig', [
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du calcul des statistiques: ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors du calcul des statistiques.');

            return $this->render('admin/property/stats.html.twig', [
                'stats' => [
                    'total' => 0,
                    'available' => 0,
                    'verified' => 0,
                    'featured' => 0,
                    'totalViews' => 0,
                    'byType' => ['sale' => 0, 'rent' => 0],
                    'byCity' => [],
                    'avgPriceByType' => [],
                    'recent' => 0,
                ],
            ]);
        }
    }

    #[Route('/bulk-action', name: 'admin_property_bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['error' => 'JSON invalide'], 400);
            }

            $action = $data['action'] ?? null;
            $ids = $data['ids'] ?? [];

            if (empty($ids) || !in_array($action, ['verify', 'feature', 'available', 'delete'])) {
                return $this->json(['error' => 'Action invalide ou aucune propriété sélectionnée'], 400);
            }

            $properties = $this->propertyRepository->findBy(['id' => $ids]);
            $processedCount = 0;

            $this->entityManager->beginTransaction();

            try {
                foreach ($properties as $property) {
                    switch ($action) {
                        case 'verify':
                            $property->setVerified(true);
                            break;
                        case 'feature':
                            $property->setFeatured(true);
                            break;
                        case 'available':
                            $property->setAvailable(true);
                            break;
                        case 'delete':
                            $this->entityManager->remove($property);
                            break;
                    }
                    $processedCount++;
                }

                $this->entityManager->flush();
                $this->entityManager->commit();

                $this->logger->info("Action groupée '$action' exécutée", [
                    'action' => $action,
                    'properties_count' => $processedCount,
                    'property_ids' => $ids,
                    'executed_by' => $this->getUser()->getId()
                ]);

                return $this->json([
                    'success' => true,
                    'message' => "Action '$action' exécutée sur $processedCount propriété(s)",
                    'processed_count' => $processedCount
                ]);

            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'action groupée: ' . $e->getMessage(), [
                'action' => $action ?? null,
                'ids' => $ids ?? []
            ]);
            return $this->json(['error' => 'Une erreur est survenue lors de l\'exécution'], 500);
        }
    }

    #[Route('/{id}', name: 'admin_property_show', requirements: ['id' => '\d+'])]
    public function show(Property $property): Response
    {
        try {
            return $this->render('admin/property/show.html.twig', [
                'property' => $property,
                'images' => $property->getImages(),
                'messages' => $property->getMessages()->slice(0, 10), // Derniers 10 messages
                'owner' => $property->getOwner()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'affichage de la propriété: ' . $e->getMessage(), [
                'property_id' => $property->getId()
            ]);
            $this->addFlash('error', 'Une erreur est survenue lors de l\'affichage de la propriété.');
            return $this->redirectToRoute('admin_property_index');
        }
    }

    #[Route('/{id}/edit', name: 'admin_property_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Property $property): Response
    {
        $form = $this->createForm(PropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->flush();

                $this->logger->info('Propriété modifiée', [
                    'property_id' => $property->getId(),
                    'title' => $property->getTitle(),
                    'modified_by' => $this->getUser()->getId()
                ]);

                $this->addFlash('success', 'Propriété modifiée avec succès.');

                return $this->redirectToRoute('admin_property_show', ['id' => $property->getId()]);

            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la modification de propriété: ' . $e->getMessage(), [
                    'property_id' => $property->getId()
                ]);
                $this->addFlash('error', 'Une erreur est survenue lors de la modification.');
            }
        }

        return $this->render('admin/property/edit.html.twig', [
            'property' => $property,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_property_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Property $property): Response
    {
        if ($this->isCsrfTokenValid('delete'.$property->getId(), $request->request->get('_token'))) {
            try {
                $propertyId = $property->getId();
                $propertyTitle = $property->getTitle();

                $this->entityManager->remove($property);
                $this->entityManager->flush();

                $this->logger->info('Propriété supprimée', [
                    'property_id' => $propertyId,
                    'title' => $propertyTitle,
                    'deleted_by' => $this->getUser()->getId()
                ]);

                $this->addFlash('success', 'Propriété supprimée avec succès.');

            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la suppression de propriété: ' . $e->getMessage(), [
                    'property_id' => $property->getId()
                ]);
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_property_index');
    }

    #[Route('/{id}/toggle-verified', name: 'admin_property_toggle_verified', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleVerified(Property $property): JsonResponse
    {
        try {
            $wasVerified = $property->isVerified();
            $property->setVerified(!$wasVerified);
            $this->entityManager->flush();

            $this->logger->info('Statut vérification propriété modifié', [
                'property_id' => $property->getId(),
                'verified' => $property->isVerified(),
                'changed_by' => $this->getUser()->getId()
            ]);

            return $this->json([
                'success' => true,
                'verified' => $property->isVerified(),
                'message' => 'Statut de vérification mis à jour'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du toggle verified: ' . $e->getMessage(), [
                'property_id' => $property->getId()
            ]);
            return $this->json(['error' => 'Erreur lors de la mise à jour'], 500);
        }
    }

    #[Route('/{id}/toggle-featured', name: 'admin_property_toggle_featured', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleFeatured(Property $property): JsonResponse
    {
        try {
            $wasFeatured = $property->isFeatured();
            $property->setFeatured(!$wasFeatured);
            $this->entityManager->flush();

            $this->logger->info('Statut vedette propriété modifié', [
                'property_id' => $property->getId(),
                'featured' => $property->isFeatured(),
                'changed_by' => $this->getUser()->getId()
            ]);

            return $this->json([
                'success' => true,
                'featured' => $property->isFeatured(),
                'message' => 'Statut vedette mis à jour'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du toggle featured: ' . $e->getMessage(), [
                'property_id' => $property->getId()
            ]);
            return $this->json(['error' => 'Erreur lors de la mise à jour'], 500);
        }
    }

    #[Route('/{id}/toggle-available', name: 'admin_property_toggle_available', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleAvailable(Property $property): JsonResponse
    {
        try {
            $wasAvailable = $property->isAvailable();
            $property->setAvailable(!$wasAvailable);
            $this->entityManager->flush();

            $this->logger->info('Statut disponibilité propriété modifié', [
                'property_id' => $property->getId(),
                'available' => $property->isAvailable(),
                'changed_by' => $this->getUser()->getId()
            ]);

            return $this->json([
                'success' => true,
                'available' => $property->isAvailable(),
                'message' => 'Statut de disponibilité mis à jour'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du toggle available: ' . $e->getMessage(), [
                'property_id' => $property->getId()
            ]);
            return $this->json(['error' => 'Erreur lors de la mise à jour'], 500);
        }
    }

    /**
     * Calculer les statistiques des propriétés
     */
    private function calculatePropertyStats(): array
    {
        try {
            return $this->propertyRepository->getStatistics();
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du calcul des statistiques: ' . $e->getMessage());
            return [
                'total' => 0,
                'available' => 0,
                'verified' => 0,
                'featured' => 0,
                'totalViews' => 0,
            ];
        }
    }
}
