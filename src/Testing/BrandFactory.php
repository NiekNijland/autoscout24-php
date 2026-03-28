<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Testing;

use NiekNijland\AutoScout24\Data\Brand;

class BrandFactory
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function make(array $overrides = []): Brand
    {
        $defaults = [
            'value' => 68,
            'label' => 'Suzuki',
        ];

        $data = array_merge($defaults, $overrides);

        return new Brand(
            value: (int) $data['value'],
            label: (string) $data['label'],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return Brand[]
     */
    public static function makeMany(int $count, array $overrides = []): array
    {
        $brands = [];
        for ($i = 0; $i < $count; $i++) {
            $brands[] = self::make(array_merge($overrides, [
                'value' => ($overrides['value'] ?? 68) + $i,
                'label' => ($overrides['label'] ?? 'Brand').' '.($i + 1),
            ]));
        }

        return $brands;
    }
}
