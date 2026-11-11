<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Parts\Part;
use App\Entity\Purchasing\PurchaseOrderLine;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Attribute\AsTwigFunction;

final readonly class PurchasingExtension
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Order lines for the given part that still wait for items, newest order first.
     *
     * @return list<PurchaseOrderLine>
     */
    #[AsTwigFunction(name: 'part_open_order_lines')]
    public function openOrderLines(Part $part): array
    {
        if ($part->getID() === null) {
            return [];
        }

        /** @var list<PurchaseOrderLine> $lines */
        $lines = $this->entityManager->createQueryBuilder()
            ->select('line', 'purchaseOrder')
            ->from(PurchaseOrderLine::class, 'line')
            ->join('line.purchaseOrder', 'purchaseOrder')
            ->where('line.part = :part')
            ->andWhere('line.quantity > line.quantityReceived')
            ->setParameter('part', $part)
            ->orderBy('purchaseOrder.createdAt', 'DESC')
            ->addOrderBy('purchaseOrder.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $lines;
    }
}
