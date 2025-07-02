<?php

namespace App\Controller\Admin;

use App\Entity\Property;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reports/financial', name: 'admin_reports_financial')]
class FinancialReportsController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    #[Route('', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $repo = $this->entityManager->getRepository(Property::class);

        // Exemples d’agrégats
        $totalSale = $this->entityManager->createQuery('SELECT SUM(p.price) FROM App\Entity\Property p WHERE p.type = :type')
            ->setParameter('type', Property::TYPE_SALE)
            ->getSingleScalarResult();
        $totalRent = $this->entityManager->createQuery('SELECT SUM(p.price) FROM App\Entity\Property p WHERE p.type = :type')
            ->setParameter('type', Property::TYPE_RENT)
            ->getSingleScalarResult();

        return $this->render('admin/financial/reports_financial.html.twig', [
            'totalSale' => $totalSale,
            'totalRent' => $totalRent,
        ]);
    }
}
