<?php

namespace App\Repository;

use App\Entity\Marchand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Marchand>
 *
 * @method Marchand|null find($id, $lockMode = null, $lockVersion = null)
 * @method Marchand|null findOneBy(array $criteria, array $orderBy = null)
 * @method Marchand[]    findAll()
 * @method Marchand[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MarchandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Marchand::class);
    }

    public function save(Marchand $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Marchand $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function searchByKeyword(string $query)
    {
        return $this->createQueryBuilder('d')
            ->where('d.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
}
