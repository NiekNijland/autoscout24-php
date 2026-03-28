<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data\Concerns;

use NiekNijland\AutoScout24\Data\FilterOption;
use NiekNijland\AutoScout24\Data\Filters\SharedSearchFilters;
use NiekNijland\AutoScout24\Data\Filters\VehicleSearchFilters;
use NiekNijland\AutoScout24\Data\OfferType;
use NiekNijland\AutoScout24\Data\PowerType;
use NiekNijland\AutoScout24\Data\QueryParams;

trait HasSharedQueryParams
{
    protected function buildQueryParams(
        SharedSearchFilters $shared,
        VehicleSearchFilters $filters,
        int $page,
        ?string $atype = null,
    ): QueryParams {
        $mmmv = null;
        if ($shared->brand !== null) {
            $makeId = $shared->brand->value;
            $model = $shared->model;
            $modelId = $model !== null ? $model->value : '';
            $modelLineId = $model !== null ? ($model->modelLineId ?? '') : '';
            $mmmv = "{$makeId}|{$modelId}|{$modelLineId}|";
        }

        $sort = $shared->sortOrder?->sortField() ?? 'standard';
        $desc = ($shared->sortOrder?->isDescending() ?? false) ? '1' : '0';

        $ustate = null;
        if ($shared->offerTypes !== []) {
            $ustate = implode(',', array_map(
                static fn (OfferType $t): string => $t->value,
                $shared->offerTypes,
            ));
        }

        return new QueryParams(
            mmmv: $mmmv,
            sort: $sort,
            desc: $desc,
            ustate: $ustate,
            atype: $atype,
            cy: $shared->country ?? 'NL',
            pricefrom: $shared->priceFrom,
            priceto: $shared->priceTo,
            fregfrom: $shared->yearFrom,
            fregto: $shared->yearTo,
            kmfrom: $shared->mileageFrom,
            kmto: $shared->mileageTo,
            ccfrom: $shared->ccFrom,
            ccto: $shared->ccTo,
            powerfrom: $shared->powerFrom,
            powerto: $shared->powerTo,
            powertype: $shared->powerType ?? PowerType::Kw,
            body: $this->buildFilterString($filters->bodyTypes),
            fuel: $this->buildFilterString($filters->fuelTypes),
            gear: $this->buildFilterString($filters->gearTypes),
            bcol: $this->buildFilterString($filters->colors),
            custtype: $shared->sellerType?->value,
            eq: $this->buildFilterString($filters->equipment),
            zip: $shared->zip,
            zipr: $shared->radius,
            page: $page,
            offer: $shared->onlineSince,
            excludeDamaged: $shared->excludeDamaged,
            pricetype: $shared->priceType,
        );
    }

    /**
     * @param  FilterOption[]  $options
     */
    private function buildFilterString(array $options): ?string
    {
        if ($options === []) {
            return null;
        }

        return implode(',', array_map(
            static fn (FilterOption $o): string => (string) $o->value,
            $options,
        ));
    }
}
