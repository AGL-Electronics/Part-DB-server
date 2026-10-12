<?php

declare(strict_types=1);

namespace App\Entity\LogSystem;

use App\Entity\Purchasing\PurchaseOrder;
use App\Entity\Purchasing\PurchaseOrderLine;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PurchaseOrderLogEntry extends AbstractLogEntry
{
    public const ACTION_CREATED = 'created';

    public const ACTION_ADDED = 'added';

    protected string $typeString = 'purchase_order';

    public static function created(PurchaseOrder $order): self
    {
        return new self($order, self::ACTION_CREATED, '', 0);
    }

    public static function added(PurchaseOrder $order, PurchaseOrderLine $line): self
    {
        return new self($order, self::ACTION_ADDED, $line->getLabel(), $line->getQuantity());
    }

    private function __construct(PurchaseOrder $order, string $action, string $label, int $quantity)
    {
        parent::__construct();
        $this->level = LogLevel::INFO;
        $this->extra = [
            'a' => $action,
            'i' => $order->getId() ?? 0,
            'n' => $order->getNumber(),
            'm' => $order->getName(),
            'l' => $label,
            'q' => $quantity,
        ];
    }

    public function getAction(): string
    {
        $action = $this->extra['a'] ?? self::ACTION_CREATED;

        return is_string($action) ? $action : self::ACTION_CREATED;
    }

    public function getOrderId(): int
    {
        return (int) ($this->extra['i'] ?? 0);
    }

    public function getOrderNumber(): string
    {
        $number = $this->extra['n'] ?? '';

        return is_string($number) ? $number : '';
    }

    public function getOrderName(): string
    {
        $name = $this->extra['m'] ?? '';

        return is_string($name) ? $name : '';
    }

    public function getLabel(): string
    {
        $label = $this->extra['l'] ?? '';

        return is_string($label) ? $label : '';
    }

    public function getQuantity(): int
    {
        return (int) ($this->extra['q'] ?? 0);
    }
}
