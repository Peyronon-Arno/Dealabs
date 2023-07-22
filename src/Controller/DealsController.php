<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\Badge;
use App\Entity\Comment;
use App\Entity\Marchand;
use App\Entity\Category;
use App\Entity\Deal;
use App\Entity\User;
use App\Form\DealsType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class DealsController extends AbstractController
{
    #[Route('/deals', name: 'app_deals')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $deals = $entityManager->getRepository(Deal::class)->findAll();
        usort($deals, function ($deal1, $deal2) {
            $notation1 = $deal1->getNotations()->count();
            $notation2 = $deal2->getNotations()->count();

            if ($notation1 == $notation2) {
                return 0;
            }

            return ($notation1 > $notation2) ? -1 : 1;
        });
        $marchands = $entityManager->getRepository(Marchand::class)->findAll();
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $currentDate = new DateTime();
        return $this->render('deals/index.html.twig', [
            'deals' => $deals,
            'marchands' => $marchands,
            'categories' => $categories,
            'currentDate' => $currentDate
        ]);
    }

    #[Route('/deal/{id}', name: 'app_deals_details')]
    public function dealDetails(Deal $deal): Response
    {
        $currentDate = new DateTime();
        $expirationDate = $deal->getExpirationDate();
        $remainingTime = $currentDate->diff($expirationDate);
        return $this->render('deals/deal-details/index.html.twig', [
            'deal' => $deal,
            'remainingTime' => $remainingTime
        ]);
    }

    #[Route('/deals/{id}/edit', name: 'app_deals_edit')]
    public function dealEdit(Deal $deal, Request $request, EntityManagerInterface $em): Response
    {
        if ($deal->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de modifier cette annonce');
        }

        $form = $this->createForm(DealsType::class, $deal);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $deal = $form->getData();

            //Check required fields
            if (!$deal->getTitle() || !$deal->getDescription() || !$deal->getPrice()) {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires');
                return $this->redirectToRoute('app_deals_add');
            }

            $em->persist($deal);
            $em->flush();

            return $this->redirectToRoute('app_deals_details', [
                'id' => $deal->getId(),
            ]);
        }

        return $this->render('deals/deal-edit/index.html.twig', [
            'form' => $form->createView(),
            'deal' => $deal
        ]);
    }

    #[Route('deals/add', name: 'app_deals_add')]
    public function add(Request $request, EntityManagerInterface $em, MailerInterface $mailer, RouterInterface $router): Response
    {
        $deal = new Deal();
        $deal->setUser($this->getUser());

        $form = $this->createForm(DealsType::class, $deal);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //Check required fields
            if (!$deal->getTitle() || !$deal->getDescription() || !$deal->getPrice()) {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires');
                return $this->redirectToRoute('app_deals_add');
            }

            $deal = $form->getData();
            $deal->setCreatedAt(new DateTime());

            $em->persist($deal);
            $em->flush();
            /** @var User|null */
            $user = $this->getUser();
            $badge = $this->findBadgeByUserAndName($em, $user->getId(), "Badge cobaye");
            if ($badge) {
                $badge->setCurrentValue($badge->getCurrentValue() + 1);
                $em->persist($badge);
            }

            $userRepo = $em->getRepository(User::class);
            $users = $userRepo->findAll();
            foreach ($users as $user) {
                foreach ($user->getAlerts() as $alert) {
                    $temperatureMin = $alert->getTemperatureMin();
                    $alertName = $alert->getName();
                    $dealName = $deal->getTitle();
                    if (($temperatureMin == 0 || str_contains($dealName, $alertName)) && !$alert->containsDeal($deal)) {
                        $alert->addDeal($deal);
                        $alert->setHasBeenShown(false);
                    }

                    $this->generateEmailForAlert($deal, $alert, $mailer, $user, $router);
                }
            }

            $em->flush();

            return $this->redirectToRoute('app_deals_details', [
                'id' => $deal->getId(),
            ]);
        }

        return $this->render('deals/add_deals/index.html.twig', [
            'form' => $form->createView(),
            'username' => $this->getUser()
        ]);
    }

    public function findBadgeByUserAndName(EntityManagerInterface $em, int $userId, string $badgeName): ?Badge
    {
        $qb = $em->createQueryBuilder();

        $qb->select('b')
            ->from('App\Entity\Badge', 'b')
            ->join('b.users', 'u')
            ->where('u.id = :userId')
            ->andWhere('b.title = :badgeName')
            ->setParameter('userId', $userId)
            ->setParameter('badgeName', $badgeName);

        return $qb->getQuery()->getOneOrNullResult();
    }



    #[Route('/deal/addComment/{id}', name: 'app_deal_addComment')]
    public function addComment(Deal $deal, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User|null */
        $currentUser = $this->getUser();
        if ($currentUser) {
            $commentText = $request->request->get('comment');

            if ($commentText) {
                $comment = new Comment();
                $comment->setValue($commentText);
                $comment->setDeal($deal);
                $user = $this->getUser();
                $comment->setUser($user);
                $em->persist($comment);
                $em->flush();
                $badge = $this->findBadgeByUserAndName($em, $currentUser->getId(), "Badge rapport de stage");
                if ($badge) {
                    $badge->setCurrentValue($badge->getCurrentValue() + 1);
                    $em->persist($badge);
                    $em->flush();
                }
            }

            return $this->redirectToRoute('app_deals_details', [
                'id' => $deal->getId(),
            ]);
        }

        return $this->redirectToRoute('app_login');
    }


    #[Route('/deal/addDealFavorite/{id}', name: 'app_deal_addDealFavorite')]
    public function addDealFavorite(Deal $deal, EntityManagerInterface $em): Response
    {

        /** @var User|null */
        $user = $this->getUser();

        if ($user) {
            if ($user->getFavoriteDeals()->contains($deal)) {
                $user->removeFavoriteDeal($deal);
            } else {
                $user->addFavoriteDeal($deal);
            }
            $em->persist($user);
            $em->flush();
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/deal/signalDeal/{id}', name: 'app_deal_signalDeal')]
    public function signalDeal(Deal $deal, EntityManagerInterface $em, MailerInterface $mailer): Response
    {

        /** @var User|null */
        $user = $this->getUser();

        if ($user) {
            if ($user->getSignalements()->contains($deal)) {
                $user->removeSignalement($deal);
            } else {
                $user->addSignalement($deal);
            }
            $email = (new Email())
                ->from('dealabs@admin.com')
                ->to('admin@admin.admin')
                ->subject('Nouvelle alerte')
                ->text('Bonjour, ' . $user->getUsername() . ' à signalé le deal ' . $deal->getTitle());
            $mailer->send($email);
            $em->persist($user);
            $em->flush();
        }

        return $this->redirectToRoute('app_home');
    }

    private function generateEmailForAlert(Deal $deal, Alert $alert, MailerInterface $mailer, User $user, RouterInterface $router)
    {
        if ($alert->isNotify()) {
            $userEmail = $user->getEmail();
            $dealUrl = $router->generate('app_deals_details', ['id' => $deal->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $email = (new Email())
                ->from('dealabs@admin.com')
                ->to($userEmail)
                ->subject('Nouvelle alerte')
                ->text('Bonjour, vous avez une nouvelle alerte. Voici le lien du deal : ' . $dealUrl);
            $mailer->send($email);
        }
    }
}
