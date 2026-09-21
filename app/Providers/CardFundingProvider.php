<?php
declare(strict_types=1);

interface CardFundingProvider
{
    public function authorize(array $request): array;
    public function mode(): string;
}
