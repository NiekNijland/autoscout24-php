<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

enum SellerType: string
{
    case Dealer = 'D';
    case Private = 'P';
}
