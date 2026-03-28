<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

enum OfferType: string
{
    case New = 'N';
    case Used = 'U';
    case DayRegistration = 'D';
    case Oldtimer = 'O';
    case YoungUsed = 'J';
    case SemiNew = 'S';
}
