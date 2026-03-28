<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Testing;

use NiekNijland\AutoScout24\Data\Model;

class ModelFactory
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function make(array $overrides = []): Model
    {
        $defaults = [
            'value' => 77700,
            'label' => 'DR-Z4SM',
            'makeId' => 68,
            'modelLineId' => null,
        ];

        $data = array_merge($defaults, $overrides);

        return new Model(
            value: (int) $data['value'],
            label: (string) $data['label'],
            makeId: (int) $data['makeId'],
            modelLineId: isset($data['modelLineId']) ? (int) $data['modelLineId'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return Model[]
     */
    public static function makeMany(int $count, array $overrides = []): array
    {
        $models = [];
        for ($i = 0; $i < $count; $i++) {
            $models[] = self::make(array_merge($overrides, [
                'value' => ($overrides['value'] ?? 77700) + $i,
                'label' => ($overrides['label'] ?? 'Model').' '.($i + 1),
            ]));
        }

        return $models;
    }
}
