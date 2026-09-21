<?php
declare(strict_types=1);

final class Reference
{
    public static function generate(string $prefix): string
    {
        return strtoupper($prefix) . '-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(5)));
    }
}
