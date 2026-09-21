<?php
declare(strict_types=1);

final class ManualCardFundingProvider implements CardFundingProvider
{
    public function authorize(array $request): array
    {
        return [
            'status' => 'pending',
            'provider_reference' => Reference::generate('MNL'),
            'mode' => 'manual_partner_review',
            'message' => 'Funding request submitted for manual review.',
        ];
    }

    public function mode(): string { return 'manual_partner_review'; }
}
