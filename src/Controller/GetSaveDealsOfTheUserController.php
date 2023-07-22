<?php

namespace App\Controller;

use App\Repository\DealRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GetSaveDealsOfTheUserController extends AbstractController
{
    private DealRepository $dealRepository;

    public function __construct(DealRepository $dealRepository)
    {
        $this->dealRepository = $dealRepository;
    }

    public function __invoke()
    {
        //Get user by jwt
        $user = $this->getUser();
        $deals = $this->dealRepository->findFavoritesByUser($user);

        return $deals;
    }
}
