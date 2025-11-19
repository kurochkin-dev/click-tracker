<?php
declare(strict_types=1);

namespace App\Core\Container;

use App\Core\Config\Bindings;
use App\Core\Config\ServiceProvider;
use App\Core\Config\Settings;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Exception;
use Psr\Container\ContainerInterface;

class ContainerFactory
{
    /**
     * @throws Exception
     */
    public static function create(string $rootPath): ContainerInterface
    {
        $envPath = $rootPath . '/.env';
        if (is_file($envPath)) {
            (Dotenv::createImmutable($rootPath))->safeLoad();
        }

        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);

        $builder->addDefinitions(Settings::definitions());
        $builder->addDefinitions(ServiceProvider::definitions());
        if (class_exists(Bindings::class)) {
            $builder->addDefinitions(Bindings::definitions());
        }

        return $builder->build();
    }
}