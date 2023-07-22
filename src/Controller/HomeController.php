<?php

namespace App\Controller;

use App\Entity\Deal;
use App\Entity\Marchand;
use App\Entity\Promo;
use App\Entity\Category;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $dateLimite = new \DateTime('-1 week');

        $deals = $entityManager
            ->getRepository(Deal::class)
            ->createQueryBuilder('d')
            ->where('d.createdAt >= :dateLimite')
            ->setParameter('dateLimite', $dateLimite)
            ->getQuery()
            ->getResult();
        $promos = $entityManager->getRepository(Promo::class)
            ->createQueryBuilder('d')
            ->where('d.createdAt >= :dateLimite')
            ->setParameter('dateLimite', $dateLimite)
            ->getQuery()
            ->getResult();

        $items = array_merge($deals, $promos);
        shuffle($items);
        usort($items, function ($items1, $items2) {
            $comments1 = $items1->getComments()->count();
            $comments2 = $items2->getComments()->count();

            if ($comments1 == $comments2) {
                return 0;
            }

            return ($comments1 > $comments2) ? -1 : 1;
        });
        $marchands = $entityManager->getRepository(Marchand::class)->findAll();
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $currentDate = new DateTime();
        return $this->render('home/index.html.twig', [
            'items' => $items,
            'marchands' => $marchands,
            'categories' => $categories,
            'currentDate' => $currentDate
        ]);
    }

    #[Route('/marchand/{id}', name: 'app_marchand_promos')]
    public function dealDetails(Marchand $marchand): Response
    {
        $currentDate = new DateTime();
        $promos = $marchand->getPromos();
        return $this->render('marchands/index.html.twig', [
            'promos' => $promos,
            'currentDate' => $currentDate
        ]);
    }
}
