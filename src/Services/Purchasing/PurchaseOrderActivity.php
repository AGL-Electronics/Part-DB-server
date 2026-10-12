<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Entity\LogSystem\PurchaseOrderLogEntry;
use App\Entity\Purchasing\PurchaseOrder;
use App\Entity\Purchasing\PurchaseOrderLine;
use App\Services\LogSystem\EventLogger;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Writes purchase-order create and line-add events into the history log.
 */
final class PurchaseOrderActivity
{
    public function __construct(
        private readonly EventLogger $eventLogger,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function created(PurchaseOrder $order): void
    {
        $this->write([PurchaseOrderLogEntry::created($order)]);
    }

    /**
     * @param list<PurchaseOrderLine> $lines
     */
    public function added(PurchaseOrder $order, array $lines): void
    {
        $entries = [];
        foreach ($lines as $line) {
            $entries[] = PurchaseOrderLogEntry::added($order, $line);
        }
        $this->write($entries);
    }

    /**
     * @param list<PurchaseOrderLogEntry> $entries
     */
    private function write(array $entries): void
    {
        if ($entries === []) {
            return;
        }

        $stored = false;
        foreach ($entries as $entry) {
            $stored = $this->eventLogger->log($entry) || $stored;
        }
        if ($stored) {
            $this->entityManager->flush();
        }
    }
}
