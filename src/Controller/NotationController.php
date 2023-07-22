<?php

namespace App\Controller;

use App\Entity\Badge;
use App\Entity\Deal;
use App\Entity\Notation;
use App\Entity\Promo;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class NotationController extends AbstractController
{
    #[Route('/deals/{id}/rate/positive', name: 'app_deals_rate_positive')]
    public function addPositiveNotationDeal(Deal $deal, EntityManagerInterface $em, MailerInterface $mailer, RouterInterface $router): Response
    {
        $this->updateBadge($em);
        return $this->addNotationDeal('positive', $deal, $em, $mailer, $router);
    }

    #[Route('/deals/{id}/rate/negative', name: 'app_deals_rate_negative')]
    public function addNegativeNotationDeal(Deal $deal, EntityManagerInterface $em, MailerInterface $mailer, RouterInterface $router): Response
    {
        $this->updateBadge($em);
        return $this->addNotationDeal('negative', $deal, $em, $mailer, $router);
    }

    #[Route('/promo/{id}/rate/positive', name: 'app_promo_rate_positive')]
    public function addPositiveNotationPromo(Promo $promo, EntityManagerInterface $em): Response
    {
        $this->updateBadge($em);
        return $this->addNotationPromo('positive', $promo, $em);
    }

    #[Route('/promo/{id}/rate/negative', name: 'app_promo_rate_negative')]
    public function addNegativeNotationPromo(Promo $promo, EntityManagerInterface $em): Response
    {
        $this->updateBadge($em);
        return $this->addNotationPromo('negative', $promo, $em);
    }

    private function updateBadge(EntityManagerInterface $em)
    {
        $badge = $this->getBadge($em);
        if ($badge) {
            $badge->setCurrentValue($badge->getCurrentValue() + 1);
            $em->persist($badge);
            $em->flush();
        }
    }

    private function getBadge(EntityManagerInterface $em)
    {
        /** @var User|null */
        $user = $this->getUser();
        return $this->findBadgeByUserAndName($em, $user->getId(), "Badge surveillant");
    }

    private function findBadgeByUserAndName(EntityManagerInterface $em, int $userId, string $badgeName): ?Badge
    {
        $qb = $em->createQueryBuilder();
        $qb->select('b')
            ->from(Badge::class, 'b')
            ->join('b.users', 'u')
            ->where('u.id = :userId')
            ->andWhere('b.title = :badgeName')
            ->setParameter('userId', $userId)
            ->setParameter('badgeName', $badgeName);

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function addNotationDeal(string $type, Deal $deal, EntityManagerInterface $em, MailerInterface $mailer, RouterInterface $router): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            $this->addFlash('error', 'Vous devez être connecté pour noter une annonce');
            return $this->redirectToRoute('app_deals_details', [
                'id' => $deal->getId(),
            ]);
        }
        if ($deal->isRatedByUser($user)) {
            $this->addFlash('error', 'Vous avez déjà noté cette annonce');
            return $this->redirectToRoute('app_deals_details', [
                'id' => $deal->getId(),
            ]);
        }

        $notation = new Notation();
        $notation->setUser($user);
        $notation->setDeal($deal);
        $notation->setScore($type === 'positive' ? 1 : -1);
        $em->persist($notation);

        $deal->addNotation($notation);
        $deal->setDegree(max(0, $deal->getDegree() + $notation->getScore()));

        $em->flush();

        $this->updateAlertes($em, $deal, $mailer, $router);
        $this->addFlash('success', 'Votre note a bien été prise en compte');

        return $this->redirectToRoute('app_deals_details', [
            'id' => $deal->getId(),
        ]);
    }

    private function updateAlertes(EntityManagerInterface $em, Deal $deal, MailerInterface $mailer, RouterInterface $router)
    {
        $degree = $deal->getDegree();
        $users = $em->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            foreach ($user->getAlerts() as $alert) {
                $temperatureMin = $alert->getTemperatureMin();
                if ($degree == $temperatureMin) {
                    $alert->addDeal($deal);
                    $alert->setHasBeenShown(false);
                }
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
        $em->flush();
    }

    private function addNotationPromo(string $type, Promo $promo, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            $this->addFlash('error', 'Vous devez être connecté pour noter une annonce');
            return $this->redirectToRoute('app_promo_detail', [
                'id' => $promo->getId(),
            ]);
        }
        if ($promo->isRatedByUser($user)) {
            $this->addFlash('error', 'Vous avez déjà noté cette annonce');
            return $this->redirectToRoute('app_promo_detail', [
                'id' => $promo->getId(),
            ]);
        }

        $notation = new Notation();
        $notation->setUser($user);
        $notation->setPromo($promo);
        $notation->setScore($type === 'positive' ? 1 : -1);
        $em->persist($notation);

        $promo->addNotation($notation);
        $promo->setDegree(max(0, $promo->getDegree() + $notation->getScore()));

        $em->flush();

        $this->addFlash('success', 'Votre note a bien été prise en compte');

        return $this->redirectToRoute('app_promo_detail', [
            'id' => $promo->getId(),
        ]);
    }
}
