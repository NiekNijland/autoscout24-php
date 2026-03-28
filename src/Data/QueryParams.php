<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

final readonly class QueryParams
{
    public function __construct(
        public ?string $mmmv = null,
        public string $sort = 'standard',
        public string $desc = '0',
        public ?string $ustate = null,
        public ?string $atype = null,
        public string $cy = 'NL',
        public ?int $pricefrom = null,
        public ?int $priceto = null,
        public ?int $fregfrom = null,
        public ?int $fregto = null,
        public ?int $kmfrom = null,
        public ?int $kmto = null,
        public ?int $ccfrom = null,
        public ?int $ccto = null,
        public ?int $powerfrom = null,
        public ?int $powerto = null,
        public PowerType $powertype = PowerType::Kw,
        public ?string $body = null,
        public ?string $fuel = null,
        public ?string $gear = null,
        public ?string $bcol = null,
        public ?string $custtype = null,
        public ?string $eq = null,
        public ?string $zip = null,
        public ?int $zipr = null,
        public ?int $page = null,
        public ?string $offer = null,
        public bool $excludeDamaged = false,
        public ?string $pricetype = null,
    ) {}

    /**
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        $params = [];

        if ($this->mmmv !== null) {
            $params['mmmv'] = $this->mmmv;
        }

        $params['sort'] = $this->sort;
        $params['desc'] = $this->desc;

        if ($this->ustate !== null) {
            $params['ustate'] = $this->ustate;
        }

        if ($this->atype !== null) {
            $params['atype'] = $this->atype;
        }

        $params['cy'] = $this->cy;
        $params['powertype'] = $this->powertype->value;

        if ($this->pricefrom !== null) {
            $params['pricefrom'] = $this->pricefrom;
        }

        if ($this->priceto !== null) {
            $params['priceto'] = $this->priceto;
        }

        if ($this->fregfrom !== null) {
            $params['fregfrom'] = $this->fregfrom;
        }

        if ($this->fregto !== null) {
            $params['fregto'] = $this->fregto;
        }

        if ($this->kmfrom !== null) {
            $params['kmfrom'] = $this->kmfrom;
        }

        if ($this->kmto !== null) {
            $params['kmto'] = $this->kmto;
        }

        if ($this->ccfrom !== null) {
            $params['ccfrom'] = $this->ccfrom;
        }

        if ($this->ccto !== null) {
            $params['ccto'] = $this->ccto;
        }

        if ($this->powerfrom !== null) {
            $params['powerfrom'] = $this->powerfrom;
        }

        if ($this->powerto !== null) {
            $params['powerto'] = $this->powerto;
        }

        if ($this->body !== null) {
            $params['body'] = $this->body;
        }

        if ($this->fuel !== null) {
            $params['fuel'] = $this->fuel;
        }

        if ($this->gear !== null) {
            $params['gear'] = $this->gear;
        }

        if ($this->bcol !== null) {
            $params['bcol'] = $this->bcol;
        }

        if ($this->custtype !== null) {
            $params['custtype'] = $this->custtype;
        }

        if ($this->eq !== null) {
            $params['eq'] = $this->eq;
        }

        if ($this->zip !== null) {
            $params['zip'] = $this->zip;
        }

        if ($this->zipr !== null) {
            $params['zipr'] = $this->zipr;
        }

        if ($this->page !== null && $this->page > 1) {
            $params['page'] = $this->page;
        }

        if ($this->offer !== null) {
            $params['offer'] = $this->offer;
        }

        if ($this->excludeDamaged) {
            $params['damaged_listing'] = 'exclude';
        }

        if ($this->pricetype !== null) {
            $params['pricetype'] = $this->pricetype;
        }

        return $params;
    }
}
