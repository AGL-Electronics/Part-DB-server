<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Entity\Parts\Part;
use App\Entity\Purchasing\PurchaseOrder;
use App\Entity\Purchasing\PurchaseOrderLine;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Adds a freshly created part to a purchase order, so it does not have to be added on the orders page afterwards.
 */
final class NewPartOrderHelper
{
    public const NEW_ORDER = 'new';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StockRefillCalculator $stockRefill,
        private readonly PurchaseOrderActivity $orderActivity,
    ) {
    }

    /**
     * Orders a new part can be added to: every order that is not fully received yet, newest first.
     *
     * @return list<PurchaseOrder>
     */
    public function selectableOrders(): array
    {
        /** @var list<PurchaseOrder> $orders */
        $orders = $this->entityManager->getRepository(PurchaseOrder::class)->findBy([], ['createdAt' => 'DESC', 'id' => 'DESC']);

        return array_values(array_filter(
            $orders,
            static fn (PurchaseOrder $order): bool => $order->getFulfillment() !== 'received'
        ));
    }

    /**
     * @param string $target an order ID, or NEW_ORDER to create a new order named $newOrderName
     * @param int|null $quantity the quantity to order, or null to use the refill suggestion (at least 1)
     *
     * @return PurchaseOrder|null the order the part was added to, or null if the target was invalid
     */
    public function addToOrder(Part $part, string $target, string $newOrderName, string $kind, ?int $quantity): ?PurchaseOrder
    {
        $creating = $target === self::NEW_ORDER;
        if ($creating) {
            $name = mb_substr(trim($newOrderName), 0, 255);
            if ($name === '') {
                return null;
            }
            $order = new PurchaseOrder();
            $order->setKind($kind);
            $order->setName($name);
            $this->entityManager->persist($order);
        } else {
            $order = ctype_digit($target) ? $this->entityManager->find(PurchaseOrder::class, (int) $target) : null;
            if (!$order instanceof PurchaseOrder || !in_array($order, $this->selectableOrders(), true)) {
                return null;
            }
        }

        $line = $order->findLineForPart($part);
        if (!$line instanceof PurchaseOrderLine) {
            $line = new PurchaseOrderLine();
            $line->setPart($part);
            $line->setTargetStock($this->stockRefill->targetStock($part->getMinAmount()));
            $line->setQuantity($quantity ?? max(1, $this->stockRefill->orderQuantity($part->getAmountSum(), $part->getMinAmount())));
            $order->addLine($line);
        }
        $this->entityManager->flush();

        if ($creating) {
            $this->orderActivity->created($order);
        }
        $this->orderActivity->added($order, [$line]);

        return $order;
    }
}
