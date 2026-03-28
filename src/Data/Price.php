<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class Price
{
    public function __construct(
        public string $priceFormatted,
        public ?int $priceRaw = null,
        public ?string $vatLabel = null,
        public bool $isVatLabelLegallyRequired = false,
        public bool $isConditionalPrice = false,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $priceRaw = isset($data['priceRaw']) ? (int) $data['priceRaw'] : null;

        // Attempt to parse the raw price from the formatted string if not provided.
        if ($priceRaw === null && isset($data['priceFormatted'])) {
            $digits = preg_replace('/[^\d]/', '', (string) $data['priceFormatted']);
            if ($digits !== '' && $digits !== '0') {
                $priceRaw = (int) $digits;
            }
        }

        return new self(
            priceFormatted: (string) ($data['priceFormatted'] ?? ''),
            priceRaw: $priceRaw,
            vatLabel: $data['vatLabel'] ?? null,
            isVatLabelLegallyRequired: (bool) ($data['isVatLabelLegallyRequired'] ?? false),
            isConditionalPrice: (bool) ($data['isConditionalPrice'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'priceFormatted' => $this->priceFormatted,
            'priceRaw' => $this->priceRaw,
            'vatLabel' => $this->vatLabel,
            'isVatLabelLegallyRequired' => $this->isVatLabelLegallyRequired,
            'isConditionalPrice' => $this->isConditionalPrice,
        ];
    }
}
