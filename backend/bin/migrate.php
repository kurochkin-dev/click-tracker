<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Container\ContainerFactory;
use App\Infrastructure\Database\Migration\MigrationRunner;

$command = $argv[1] ?? null;
$arg = $argv[2] ?? null;

if ($command === null) {
    echo "Usage: php migrate.php [command] [options]\n";
    echo "Commands:\n";
    echo "  create <name>  - Create a new migration file\n";
    echo "  up             - Apply all pending migrations\n";
    echo "  down           - Roll back the last migration\n";
    echo "  reset          - Roll back all migrations\n";
    echo "  fresh          - Drop all tables and reapply migrations\n";
    echo "  status         - Show migration status\n";
    exit(1);
}

$root = dirname(__DIR__, 2);
$container = ContainerFactory::create($root);
$pdo = $container->get(PDO::class);
$migrationsPath = __DIR__ . '/../src/Infrastructure/Database/Migrations';
$runner = new MigrationRunner($pdo, $migrationsPath);

try {
    match ($command) {
        'create' => createMigration($migrationsPath, $arg),
        'up' => runUp($runner),
        'down' => runDown($runner),
        'reset' => runReset($runner),
        'fresh' => runFresh($runner),
        'status' => showStatus($runner),
        default => throw new \InvalidArgumentException("Unknown command: {$command}")
    };
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Creates a new migration file
 *
 * @param string $migrationsPath
 * @param string|null $name
 */
function createMigration(string $migrationsPath, ?string $name): void
{
    if ($name === null || $name === '') {
        echo "Error: Migration name is required.\n";
        echo "Usage: php migrate.php create <MigrationName>\n";
        exit(1);
    }

    $timestamp = date('YmdHis');
    $className = toPascalCase($name);
    $filename = "{$timestamp}_{$name}.php";
    $filePath = $migrationsPath . '/' . $filename;

    if (file_exists($filePath)) {
        echo "Error: Migration file {$filename} already exists.\n";
        exit(1);
    }

    $template = <<<PHP
<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Migrations;

use App\Infrastructure\Database\Migration\Migration;
use PDO;

readonly class {$className} implements Migration
{
    public function __construct(private PDO \$pdo)
    {
    }

    /**
     * Returns migration name
     *
     * @return string
     */
    public function getName(): string
    {
        return '{$name}';
    }

    /**
     * Applies migration
     */
    public function up(): void
    {
        // TODO: Implement migration
    }

    /**
     * Rolls back migration
     */
    public function down(): void
    {
        // TODO: Implement rollback
    }
}
PHP;

    file_put_contents($filePath, $template);
    echo "Migration file created: {$filename}\n";
}

/**
 * Converts string to PascalCase
 *
 * @param string $string
 * @return string
 */
function toPascalCase(string $string): string
{
    return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $string)));
}

/**
 * Runs up command
 *
 * @param MigrationRunner $runner
 * @throws Throwable
 */
function runUp(MigrationRunner $runner): void
{
    $applied = $runner->up();
    if (empty($applied)) {
        echo "No pending migrations.\n";
    } else {
        echo "\nApplied " . count($applied) . " migration(s).\n";
    }
}

/**
 * Runs down command
 *
 * @param MigrationRunner $runner
 * @throws Throwable
 */
function runDown(MigrationRunner $runner): void
{
    $rolledBack = $runner->down();
    if ($rolledBack === null) {
        echo "No migrations to roll back.\n";
    }
}

/**
 * Shows migration status
 *
 * @param MigrationRunner $runner
 */
function showStatus(MigrationRunner $runner): void
{
    $status = $runner->status();

    echo "Migration Status:\n\n";
    echo "Applied migrations (" . count($status['applied']) . "):\n";
    if (empty($status['applied'])) {
        echo "  (none)\n";
    } else {
        foreach ($status['applied'] as $migration) {
            echo "  ✓ {$migration}\n";
        }
    }

    echo "\nPending migrations (" . count($status['pending']) . "):\n";
    if (empty($status['pending'])) {
        echo "  (none)\n";
    } else {
        foreach ($status['pending'] as $migration) {
            echo "  ○ {$migration}\n";
        }
    }
}

/**
 * Runs reset command
 *
 * @param MigrationRunner $runner
 * @throws Throwable
 */
function runReset(MigrationRunner $runner): void
{
    $rolledBack = $runner->reset();
    if (empty($rolledBack)) {
        echo "No migrations to roll back.\n";
    } else {
        echo "\nRolled back " . count($rolledBack) . " migration(s).\n";
    }
}

/**
 * Runs fresh command
 *
 * @param MigrationRunner $runner
 * @throws Throwable
 */
function runFresh(MigrationRunner $runner): void
{
    $applied = $runner->fresh();
    echo "\nApplied " . count($applied) . " migration(s).\n";
}
