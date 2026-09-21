<?php
declare(strict_types=1);

final class SandboxCardFundingProvider implements CardFundingProvider
{
    public function authorize(array $request): array
    {
        return [
            'status' => config('financial.sandbox_auto_complete', true) ? 'completed' : 'processing',
            'provider_reference' => Reference::generate('SBOX'),
            'mode' => 'sandbox',
            'message' => 'Sandbox simulation only; no external card was charged.',
        ];
    }

    public function mode(): string { return 'sandbox'; }
}
