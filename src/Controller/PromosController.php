<?php

namespace App\Controller;

use App\Entity\Promo;
use App\Entity\Marchand;
use App\Entity\Category;
use App\Entity\Comment;
use App\Form\PromoType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PromosController extends AbstractController
{
    #[Route('/promos', name: 'app_promos')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $promos = $entityManager->getRepository(Promo::class)->findAll();
        usort($promos, function ($promo1, $promo2) {
            $notation1 = $promo1->getNotations()->count();
            $notation2 = $promo2->getNotations()->count();

            if ($notation1 == $notation2) {
                return 0;
            }

            return ($notation1 > $notation2) ? -1 : 1;
        });
        $marchands = $entityManager->getRepository(Marchand::class)->findAll();
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $currentDate = new DateTime();
        return $this->render('promos/index.html.twig', [
            'promos' => $promos,
            'marchands' => $marchands,
            'categories' => $categories,
            'currentDate' => $currentDate
        ]);
    }

    #[Route('/promo/add', name: 'app_promo_add')]
    public function addPromo(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        $promo = new Promo();
        $form = $this->createForm(PromoType::class, $promo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$promo->getTitle() || !$promo->getDescription() || !$promo->getReduction() || !$promo->getCode() || !$promo->getExpirationDate()) {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires');
                return $this->redirectToRoute('app_promo_add');
            }

            $promo = $form->getData();
            $promo->setCreatedAt(new DateTime());
            $em->persist($promo);
            $em->flush();

            return $this->redirectToRoute('app_promo_detail', [
                'id' => $promo->getId(),
            ]);
        }

        return $this->render('promos/promo-add/index.html.twig', [
            'form' => $form->createView(),
            'username' => $this->getUser(),
        ]);
    }

    #[Route('/promo/{id}', name: 'app_promo_detail')]
    public function promoDetails(Promo $promo): Response
    {
        $currentDate = new DateTime();
        $expirationDate = $promo->getExpirationDate();
        $remainingTime = $currentDate->diff($expirationDate);
        return $this->render('promos/promo-details/index.html.twig', [
            'promo' => $promo,
            'remainingTime' => $remainingTime
        ]);
    }

    #[Route('/promo/addComment/{id}', name: 'app_promo_addComment')]
    public function addComment(Promo $promo, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->getUser()) {
            $commentText = $request->request->get('comment');

            if ($commentText) {
                $comment = new Comment();
                $comment->setValue($commentText);
                $comment->setPromo($promo);
                $user = $this->getUser();
                $comment->setUser($user);
                $em->persist($comment);
                $em->flush();
            }

            return $this->redirectToRoute('app_promo_detail', [
                'id' => $promo->getId(),
            ]);
        }

        return $this->redirectToRoute('app_login');
    }
}
