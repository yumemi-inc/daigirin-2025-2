<?php

declare(strict_types=1);

namespace Uutan1108\BddSample;

use InvalidArgumentException;

/**
 * シンプルなレジシステムクラス
 */
class RegisterSystem
{
    private array $items = [];
    private array $prices = [
        'りんご' => 150,
        'みかん' => 100,
        'バナナ' => 200
    ];

    public function addItem(string $itemName, int $quantity): void
    {
        if (!isset($this->prices[$itemName])) {
            throw new InvalidArgumentException("商品 {$itemName} の価格が設定されていません");
        }

        // 既存の商品がある場合は数量を追加
        foreach ($this->items as &$item) {
            if ($item['name'] === $itemName) {
                $item['quantity'] += $quantity;
                return;
            }
        }

        // 新しい商品を追加
        $this->items[] = [
            'name' => $itemName,
            'quantity' => $quantity,
            'price' => $this->prices[$itemName]
        ];
    }

    public function removeItem(string $itemName): void
    {
        $this->items = array_filter($this->items, function($item) use ($itemName) {
            return $item['name'] !== $itemName;
        });
    }

    public function getTotal(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
