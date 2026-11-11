<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Entity\Parts\Part;
use App\Entity\Purchasing\PurchaseOrder;
use App\Entity\Purchasing\PurchaseOrderLine;
use Doctrine\ORM\EntityManagerInterface;

final readonly class OpenOrderLines
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Order lines for the given part that still wait for items, newest order first.
     *
     * @return list<PurchaseOrderLine>
     */
    public function forPart(Part $part, ?PurchaseOrder $except = null): array
    {
        if ($part->getID() === null) {
            return [];
        }

        $query = $this->entityManager->createQueryBuilder()
            ->select('line', 'purchaseOrder')
            ->from(PurchaseOrderLine::class, 'line')
            ->join('line.purchaseOrder', 'purchaseOrder')
            ->where('line.part = :part')
            ->andWhere('line.quantity > line.quantityReceived')
            ->setParameter('part', $part)
            ->orderBy('purchaseOrder.createdAt', 'DESC')
            ->addOrderBy('purchaseOrder.id', 'DESC');
        if ($except instanceof PurchaseOrder && $except->getId() !== null) {
            $query->andWhere('purchaseOrder != :except')->setParameter('except', $except);
        }

        /** @var list<PurchaseOrderLine> $lines */
        $lines = $query->getQuery()->getResult();

        return $lines;
    }
}
