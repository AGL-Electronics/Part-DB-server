<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Parts\Part;
use App\Entity\Purchasing\PurchaseOrderLine;
use App\Services\Purchasing\OpenOrderLines;
use Twig\Attribute\AsTwigFunction;

final readonly class PurchasingExtension
{
    public function __construct(private OpenOrderLines $openOrderLines)
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
        return $this->openOrderLines->forPart($part);
    }
}
