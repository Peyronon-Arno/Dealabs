<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\Deal;
use App\Entity\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserAccountController extends AbstractController
{
    #[Route('/user/account', name: 'app_user_account')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_user_appercu');
    }

    #[Route('/user/account/apercu', name: 'app_user_appercu')]
    public function apercu(EntityManagerInterface $em): Response
    {
        /** @var User|null */
        $currentUser = $this->getUser();
        $user = $em->getRepository(User::class)->find($currentUser->getId());
        $badges = $user->getBadges();
        $hotestDealValue = 0;
        foreach ($user->getDeals() as $deal) {
            if ($deal->getDegree() > $hotestDealValue) {
                $hotestDealValue = $deal->getDegree();
            }
        }

        $hotDealPercentage = $this->getPercentageHot($this->getUser());
        $averageDegrees = $this->getAvarageDegrees($em);

        return $this->render('user_account/index.html.twig', [
            'current_tab' => 'apercu',
            'badges' => $badges,
            'hotestDealValue' => $hotestDealValue,
            "hotDealPercentage" => $hotDealPercentage,
            "averageDegrees" => $averageDegrees
        ]);
    }

    private function getAvarageDegrees(EntityManagerInterface $em)
    {
        $deals = $this->getDealsForLastYear($em);
        $totalDegrees = 0;
        $totalDeals = count($deals);

        foreach ($deals as $deal) {
            $totalDegrees += $deal->getDegree();
        }
        $averageDegrees = ($totalDeals > 0) ? $totalDegrees / $totalDeals : 0;
        $averageDegrees = round($averageDegrees, 2);
        return $averageDegrees;
    }

    private function getDealsForLastYear(EntityManagerInterface $em)
    {
        $oneYearAgo = new \DateTime('-1 year');

        $qb = $em->createQueryBuilder();
        $qb->select('d')
            ->from(Deal::class, 'd')
            ->where('d.createdAt >= :startDate')
            ->andWhere('d.createdAt <= :endDate')
            ->andWhere('d.User = :user')
            ->setParameter('startDate', $oneYearAgo)
            ->setParameter('endDate', new \DateTime())
            ->setParameter('user', $this->getUser());

        $deals = $qb->getQuery()->getResult();

        return $deals;
    }

    private function getPercentageHot($user)
    {
        $deals = $user->getDeals();
        $totalDeals = count($deals);
        $countHotDeals = 0;

        foreach ($deals as $deal) {
            if ($deal->getDegree() >= 100) {
                $countHotDeals++;
            }
        }

        $hotDealPercentage = 0;
        if ($totalDeals > 0) {
            $hotDealPercentage = ($countHotDeals / $totalDeals) * 100;
        }
        $hotDealPercentage = round($hotDealPercentage, 2);
        return $hotDealPercentage;
    }

    #[Route('/user/account/deals', name: 'app_user_deals')]
    public function deals(): Response
    {
        /** @var User|null */
        $user = $this->getUser();
        $deals = $user->getDeals();
        $currentDate = new DateTime();
        return $this->render('user_account/index.html.twig', [
            'current_tab' => 'deals',
            'deals' => $deals,
            'currentDate' => $currentDate
        ]);
    }

    #[Route('/user/account/saved', name: 'app_user_saved')]
    public function saved(): Response
    {
        /** @var User|null */
        $user = $this->getUser();
        $savedDeals = $user->getFavoriteDeals();
        $currentDate = new DateTime();

        return $this->render('user_account/index.html.twig', [
            'current_tab' => 'sauvegardes',
            'savedDeals' => $savedDeals,
            'currentDate' => $currentDate
        ]);
    }

    #[Route('/user/account/signaled', name: 'app_user_signal')]
    public function signaled(): Response
    {
        /** @var User|null */
        $user = $this->getUser();
        $signaledDeals = $user->getSignalements();
        $currentDate = new DateTime();

        return $this->render('user_account/index.html.twig', [
            'current_tab' => 'signal',
            'signaledDeals' => $signaledDeals,
            'currentDate' => $currentDate
        ]);
    }

    #[Route('/user/account/alerts', name: 'app_user_alerts')]
    public function alerts(): Response
    {
        $dealsAlert = $this->getDealsAlert();
        $currentDate = new DateTime();

        return $this->render('user_account/index.html.twig', [
            'current_tab' => 'alertes',
            'second_tab' => 'alertes-fil',
            'dealsAlert' => $dealsAlert,
            'currentDate' => $currentDate
        ]);
    }

    #[Route('/user/account/alerts/fil', name: 'app_user_alerts_fil')]
    public function alertsFil(EntityManagerInterface $em): Response
    {

        $dealsAlert = $this->getDealsAlert();
        /** @var User|null */
        $user = $this->getUser();
        $alertes = $user->getAlerts();
        foreach ($alertes as  $alert) {
            $alert->setHasBeenShown(true);
        }
        $em->flush();
        $currentDate = new DateTime();
        return $this->render('user_account/index.html.twig', [
            'current_tab' => 'alertes',
            'second_tab' => 'alertes-fil',
            'alertes' => $alertes,
            'dealsAlert' => $dealsAlert,
            'currentDate' => $currentDate
        ]);
    }

    private function getDealsAlert(): ArrayCollection
    {
        /** @var User|null */
        $user = $this->getUser();
        $dealsAlert = new ArrayCollection();

        foreach ($user->getAlerts() as $alert) {
            $deals = $alert->getDeals();
            foreach ($deals as $deal) {
                $dealsAlert->add($deal);
            }
        }

        return $dealsAlert;
    }

    #[Route('/user/account/alerts/param', name: 'app_user_alerts_param')]
    public function alertsParam(): Response
    {
        /** @var User|null */
        $user = $this->getUser();
        $alertes = $user->getAlerts();
        return $this->render('user_account/index.html.twig', [
            'current_tab' => 'alertes',
            'second_tab' => 'alertes-param',
            'alertes' => $alertes
        ]);
    }

    #[Route('/user/account/alerts/add', name: 'app_user_alerts_add', methods: ['POST'])]
    public function addAlert(Request $request, EntityManagerInterface $em): Response
    {
        $alertText = $request->request->get('alertText');
        $temperature = $request->request->get('temperature');
        $temperature = intval($temperature);
        $notification = $request->request->get('notification');
        if ($notification == "") {
            $notification = false;
        }
        if ($alertText && $temperature >= 0) {
            $alerte = new Alert();
            $alerte->setName($alertText);
            $alerte->setTemperatureMin($temperature);
            $alerte->setNotify($notification);
            $alerte->setHasBeenShown(true);
            $alerte->setUser($this->getUser());
            $em->persist($alerte);
            $em->flush();
        }
        return $this->redirectToRoute('app_user_alerts_param');
    }

    #[Route('/user/account/params', name: 'app_user_params')]
    public function params(): Response
    {
        $user = $this->getUser();
        // dd($user);
        return $this->render('user_account/params/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/account/params/editImage', name: 'app_user_params_edit_image')]
    public function editImage(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User|null */
        $user = $this->getUser();
        $file = $request->files->get('avatar');
        if ($file) {
            $directory = __DIR__ . '/../../public/images/upload/users/';
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
            $newFileName = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->move($directory, $newFileName);
            $user->setImageName($newFileName);

            $em->persist($user);
            $em->flush();
        }

        return $this->render('user_account/params/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/account/params/deleteImage/', name: 'app_user_params_delete_image')]
    public function deleteImage(EntityManagerInterface $em, Filesystem $filesystem): Response
    {
        /** @var User|null */
        $user = $this->getUser();

        $fileName = $user->getImage();
        $user->setImageName(null);
        $user->setImage(null);
        $user->setImageSize(null);
        $em->persist($user);
        $em->flush();


        $filesystem->remove('__DIR__ . "/../../public/images/upload/users/"' . $fileName);

        return $this->render('user_account/params/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/account/params/editPseudo', name: 'app_user_params_edit_pseudo')]
    public function editPseudo(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User|null */
        $user = $this->getUser();
        $newPseudo = $request->query->get('value');
        if ($newPseudo) {
            $user->setUsername($newPseudo);
            $em->persist($user);
            $em->flush();
        }
        return $this->render('user_account/params/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/account/params/editEmail', name: 'app_user_params_edit_email')]
    public function editEmail(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User|null */
        $user = $this->getUser();
        $newEmail = $request->query->get('value');
        if ($newEmail) {
            $user->setEmail($newEmail);
            $em->persist($user);
            $em->flush();
        }
        return $this->render('user_account/params/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/account/params/editPassword', name: 'app_user_params_edit_password')]
    public function editPassword(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User|null */
        $user = $this->getUser();
        $newPassword = $request->query->get('value');
        if ($newPassword) {
            $user->setPassword($newPassword);
            $em->persist($user);
            $em->flush();
        }
        return $this->render('user_account/params/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/account/params/deleteAccount', name: 'app_user_params_delete_account')]
    public function deleteAccount(EntityManagerInterface $em, SessionInterface $session): Response
    {
        /** @var User|null */
        $user = $this->getUser();
        $user->setUsername($user->getUsername() . '-' . $user->getId());
        $user->setEmail($user->getUsername() . '-' . $user->getId() . '@' . $user->getUsername() . '.unknown');
        $session->invalidate();
        $em->flush();
        return $this->redirectToRoute('app_logout');
    }
}
