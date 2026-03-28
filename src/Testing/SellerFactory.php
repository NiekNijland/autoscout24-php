<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Testing;

use NiekNijland\AutoScout24\Data\Phone;
use NiekNijland\AutoScout24\Data\Seller;
use NiekNijland\AutoScout24\Data\SellerLinks;

class SellerFactory
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function make(array $overrides = []): Seller
    {
        $defaults = [
            'id' => '24101423',
            'type' => 'Dealer',
            'companyName' => 'Test Dealer B.V.',
            'contactName' => 'Afdeling Verkoop',
        ];

        $data = array_merge($defaults, $overrides);

        return new Seller(
            id: (string) $data['id'],
            type: (string) $data['type'],
            companyName: $data['companyName'] ?? null,
            contactName: $data['contactName'] ?? null,
            phones: $data['phones'] ?? [new Phone('Office', '+31 172 650005', '+31172650005')],
            links: $data['links'] ?? new SellerLinks,
        );
    }
}
