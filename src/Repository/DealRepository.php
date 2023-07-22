<?php

namespace App\Repository;

use App\Entity\Deal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deal>
 *
 * @method Deal|null find($id, $lockMode = null, $lockVersion = null)
 * @method Deal|null findOneBy(array $criteria, array $orderBy = null)
 * @method Deal[]    findAll()
 * @method Deal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deal::class);
    }

    public function save(Deal $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Deal $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findDealsOfTheWeek(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.createdAt > :date')
            ->setParameter('date', new \DateTime('-7 days'))
            ->getQuery()
            ->getResult();
    }

    public function findFavoritesByUser(User $user)
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.userFavorite', 'uf')
            ->andWhere('uf = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function searchByKeyword(string $query)
    {
        return $this->createQueryBuilder('d')
            ->where('d.title LIKE :query')
            ->orWhere('d.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
}
