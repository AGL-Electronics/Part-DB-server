<?php

declare(strict_types=1);

namespace App\DataTables\Filters\Constraints\Part;

use App\DataTables\Filters\Constraints\BooleanConstraint;
use App\Entity\Purchasing\PurchaseOrderLine;
use Doctrine\ORM\QueryBuilder;

/**
 * Matches parts that have at least one purchase order line that still waits for items.
 */
class OnOrderConstraint extends BooleanConstraint
{
    public function __construct(?string $identifier = null, ?bool $default_value = null)
    {
        parent::__construct('(
                    SELECT oo_line.id
                    FROM '.PurchaseOrderLine::class.' oo_line
                    WHERE oo_line.part = part.id
                    AND oo_line.quantity > oo_line.quantityReceived
                )', $identifier ?? 'onOrder', $default_value);
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $queryBuilder->andWhere(($this->value ? 'EXISTS ' : 'NOT EXISTS ').$this->property);
    }
}
