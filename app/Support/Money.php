<?php
declare(strict_types=1);

final class Money
{
    public static function fromDecimal(string $value): int
    {
        $value = trim(str_replace([',', '$'], '', $value));
        if (!preg_match('/^(?:0|[1-9]\d*)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            throw new InvalidArgumentException('Enter a valid USD amount with no more than two decimal places.');
        }
        [$whole] = explode('.', $value . '.');
        $fraction = str_pad($matches[1] ?? '', 2, '0');
        $cents = ((int) $whole * 100) + (int) $fraction;
        if ($cents > PHP_INT_MAX) {
            throw new InvalidArgumentException('Amount is too large.');
        }
        return $cents;
    }

    public static function format(int $cents, bool $withCode = false): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        $formatted = $sign . '$' . number_format(intdiv($cents, 100)) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
        return $withCode ? $formatted . ' USD' : $formatted;
    }

    public static function decimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        return $sign . intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function percentageFee(int $amountCents, int $basisPoints, int $flatCents = 0): int
    {
        return intdiv(($amountCents * $basisPoints) + 9999, 10000) + $flatCents;
    }
}
