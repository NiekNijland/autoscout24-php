<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Testing;

readonly class RecordedCall
{
    /**
     * @param  array<int, mixed>  $args
     */
    public function __construct(
        public string $method,
        public array $args,
    ) {}
}
