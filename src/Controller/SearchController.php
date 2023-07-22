<?php

namespace App\Controller;

use App\Entity\Deal;
use App\Entity\Marchand;
use App\Entity\Promo;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $query = $request->query->get('q');
        $resultsDeals = $this->searchInEntities($em, $query, Deal::class);
        $resultsPromo = $this->searchInEntities($em, $query, Promo::class);
        $resultsMarchands = $this->searchInEntities($em, $query, Marchand::class);
        $results = array_merge($resultsPromo, $resultsDeals, $resultsMarchands);

        $currentDate = new DateTime();
        return $this->render('search/index.html.twig', [
            'results' => $results,
            'currentDate' => $currentDate
        ]);
    }

    private function searchInEntities(EntityManagerInterface $em, string $query, string $entityClass)
    {
        $repository = $em->getRepository($entityClass);
        $results = $repository->searchByKeyword($query);
        return $results;
    }
}
