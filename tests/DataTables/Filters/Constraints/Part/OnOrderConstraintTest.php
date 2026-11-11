<?php

declare(strict_types=1);

namespace App\Tests\DataTables\Filters\Constraints\Part;

use App\DataTables\Filters\Constraints\Part\OnOrderConstraint;
use App\Entity\Parts\Category;
use App\Entity\Parts\Part;
use App\Entity\Purchasing\PurchaseOrder;
use App\Entity\Purchasing\PurchaseOrderLine;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('DB')]
final class OnOrderConstraintTest extends KernelTestCase
{
    public function testMatchesOnlyPartsWithAnOutstandingOrderLine(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $category = $em->find(Category::class, 1);
        if (!$category instanceof Category) {
            self::markTestSkipped('Fixture category was not found.');
        }

        $open = $this->part($em, $category);
        $received = $this->part($em, $category);
        $none = $this->part($em, $category);

        $order = new PurchaseOrder();
        $order->setName('On-order-filter');
        $openLine = new PurchaseOrderLine();
        $openLine->setPart($open);
        $openLine->setQuantity(5);
        $openLine->addQuantityReceived(2);
        $order->addLine($openLine);
        $doneLine = new PurchaseOrderLine();
        $doneLine->setPart($received);
        $doneLine->setQuantity(3);
        $doneLine->addQuantityReceived(3);
        $order->addLine($doneLine);
        $em->persist($order);
        $em->flush();

        $ids = [$open->getID(), $received->getID(), $none->getID()];
        self::assertSame([$open->getID()], $this->matching($em, $ids, true));
        self::assertSame([$received->getID(), $none->getID()], $this->matching($em, $ids, false));
        self::assertSame($ids, $this->matching($em, $ids, null));
    }

    private function part(EntityManagerInterface $em, Category $category): Part
    {
        $part = new Part();
        $part->setName('On-order-'.bin2hex(random_bytes(3)));
        $part->setCategory($category);
        $em->persist($part);

        return $part;
    }

    /**
     * @param list<int|null> $ids
     *
     * @return list<int>
     */
    private function matching(EntityManagerInterface $em, array $ids, ?bool $value): array
    {
        $queryBuilder = $em->createQueryBuilder()
            ->select('part.id')
            ->from(Part::class, 'part')
            ->where('part.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('part.id', 'ASC');
        $constraint = new OnOrderConstraint();
        $constraint->setValue($value);
        $constraint->apply($queryBuilder);

        return array_map('intval', array_column($queryBuilder->getQuery()->getArrayResult(), 'id'));
    }
}
