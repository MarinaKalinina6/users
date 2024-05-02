<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

final class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function blockMultiple(array $ids): void
    {
        $this->setIsActiveMultiple($ids, false);
    }

    public function unblockMultiple(array $ids): void
    {
        $this->setIsActiveMultiple($ids, true);
    }

    public function deleteMultiple(array $ids): void
    {
        $queryBuilder = $this->createQueryBuilder('user');

        $queryBuilder
            ->delete()
            ->where($queryBuilder->expr()->in('user.id', ':ids'))
            ->setParameters(
                new ArrayCollection([
                    new Parameter('ids', array_map(fn(Uuid $id) => $id->toBinary(), $ids)),
                ]),
            );

        $queryBuilder->getQuery()->execute();
    }

    /**
     * @param array<Uuid> $ids
     */
    private function setIsActiveMultiple(array $ids, bool $isActive): void
    {
        $queryBuilder = $this->createQueryBuilder('user');

        $queryBuilder
            ->update()
            ->set('user.isActive', ':is_active')
            ->where($queryBuilder->expr()->in('user.id', ':ids'))
            ->setParameters(
                new ArrayCollection([
                    new Parameter('is_active', $isActive, Types::BOOLEAN),
                    new Parameter('ids', array_map(fn(Uuid $id) => $id->toBinary(), $ids)),
                ]),
            );

        $queryBuilder->getQuery()->execute();
        $this->redirectToRoute('singin');
    }
}
