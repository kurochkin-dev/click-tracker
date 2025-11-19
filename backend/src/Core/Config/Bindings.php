<?php
declare(strict_types=1);

namespace App\Core\Config;

class Bindings
{
    public static function definitions(): array
    {
        return [
            // Interface::class => DI\get(Concrete::class),
        ];
    }
}