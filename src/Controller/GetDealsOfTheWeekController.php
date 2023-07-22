<?php

namespace App\Controller;

use App\Repository\DealRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GetDealsOfTheWeekController extends AbstractController
{
    private DealRepository $dealRepository;

    public function __construct(DealRepository $dealRepository)
    {
        $this->dealRepository = $dealRepository;
    }

    public function __invoke(): array
    {
        $deals = $this->dealRepository->findDealsOfTheWeek();

        return $deals;
    }
}