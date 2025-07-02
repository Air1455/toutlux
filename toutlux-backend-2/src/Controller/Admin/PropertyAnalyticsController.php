<?php

namespace App\Controller\Admin;

use App\Entity\Property;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/property-analytics', name: 'admin_property_analytics')]
class PropertyAnalyticsController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    #[Route('', name: '', methods: ['GET'])]
    public function index(): Response
    {
        // Statistiques simples : distribution vente/location, vues, prix moyens, etc.
        $repo = $this->entityManager->getRepository(Property::class);

        $total = $repo->count([]);
        $forSale = $repo->count(['type' => Property::TYPE_SALE]);
        $forRent = $repo->count(['type' => Property::TYPE_RENT]);

        $avgPriceSale = $this->entityManager->createQuery('SELECT AVG(p.price) FROM App\Entity\Property p WHERE p.type = :type')
            ->setParameter('type', Property::TYPE_SALE)
            ->getSingleScalarResult();
        $avgPriceRent = $this->entityManager->createQuery('SELECT AVG(p.price) FROM App\Entity\Property p WHERE p.type = :type')
            ->setParameter('type', Property::TYPE_RENT)
            ->getSingleScalarResult();

        return $this->render('admin/property/property_analytics.html.twig', [
            'total' => $total,
            'forSale' => $forSale,
            'forRent' => $forRent,
            'avgPriceSale' => $avgPriceSale,
            'avgPriceRent' => $avgPriceRent,
        ]);
    }
}
