<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Migration;

use PDO;
use Throwable;

/**
 * Manages database migrations
 */
readonly class MigrationRunner
{
    public function __construct(
        private PDO $pdo,
        private string $migrationsPath
    ) {
        $this->ensureMigrationsTable();
    }

    /**
     * Ensures migrations table exists
     */
    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Finds all migration files in migrations directory
     *
     * @return array<string, string> Array of [filename => fullPath]
     */
    public function findMigrations(): array
    {
        $files = glob($this->migrationsPath . '/*.php');
        if ($files === false) {
            return [];
        }

        $migrations = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/^\d{14}_(.+)\.php$/', $filename)) {
                $migrations[$filename] = $file;
            }
        }

        ksort($migrations);
        return $migrations;
    }

    /**
     * Gets list of applied migrations
     *
     * @return array<string> Array of migration names
     */
    public function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT name FROM migrations ORDER BY name");
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $result ?: [];
    }

    /**
     * Checks if migration is applied
     *
     * @param string $migrationName
     * @return bool
     */
    public function isApplied(string $migrationName): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM migrations WHERE name = ?");
        $stmt->execute([$migrationName]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Marks migration as applied
     *
     * @param string $migrationName
     */
    public function markApplied(string $migrationName): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (name) VALUES (?)");
        $stmt->execute([$migrationName]);
    }

    /**
     * Marks migration as rolled back
     *
     * @param string $migrationName
     */
    public function markRolledBack(string $migrationName): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE name = ?");
        $stmt->execute([$migrationName]);
    }

    /**
     * Loads migration class from file
     *
     * @param string $filePath
     * @return Migration
     * @throws \RuntimeException
     */
    public function loadMigration(string $filePath): Migration
    {
        require_once $filePath;

        $filename = basename($filePath, '.php');
        $className = $this->filenameToClassName($filename);
        $fullClassName = 'App\\Infrastructure\\Database\\Migrations\\' . $className;

        if (!class_exists($fullClassName)) {
            throw new \RuntimeException("Migration class {$fullClassName} not found in {$filePath}");
        }

        $migration = new $fullClassName($this->pdo);
        if (!$migration instanceof Migration) {
            throw new \RuntimeException("Class {$fullClassName} does not implement Migration interface");
        }

        return $migration;
    }

    /**
     * Converts filename to class name
     *
     * @param string $filename
     * @return string
     */
    private function filenameToClassName(string $filename): string
    {
        $parts = explode('_', $filename, 2);
        $name = $parts[1] ?? $parts[0];
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    /**
     * Applies all pending migrations
     *
     * @return array<string> Array of applied migration names
     */
    public function up(): array
    {
        $migrations = $this->findMigrations();
        $applied = $this->getAppliedMigrations();
        $appliedNow = [];

        foreach ($migrations as $filename => $filePath) {
            if (in_array($filename, $applied, true)) {
                continue;
            }

            try {
                $migration = $this->loadMigration($filePath);
                echo "Applying migration: {$filename}...\n";
                $migration->up();
                $this->markApplied($filename);
                $appliedNow[] = $filename;
                echo "Migration {$filename} applied successfully.\n";
            } catch (Throwable $e) {
                echo "Error applying migration {$filename}: " . $e->getMessage() . "\n";
                throw $e;
            }
        }

        return $appliedNow;
    }

    /**
     * Rolls back last migration
     *
     * @return string|null Name of rolled back migration or null
     */
    public function down(): ?string
    {
        $applied = $this->getAppliedMigrations();
        if (empty($applied)) {
            echo "No migrations to roll back.\n";
            return null;
        }

        $migrations = $this->findMigrations();
        $lastApplied = end($applied);

        if (!isset($migrations[$lastApplied])) {
            echo "Migration file {$lastApplied} not found.\n";
            return null;
        }

        try {
            $migration = $this->loadMigration($migrations[$lastApplied]);
            echo "Rolling back migration: {$lastApplied}...\n";
            $migration->down();
            $this->markRolledBack($lastApplied);
            echo "Migration {$lastApplied} rolled back successfully.\n";
            return $lastApplied;
        } catch (Throwable $e) {
            echo "Error rolling back migration {$lastApplied}: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Shows migration status
     *
     * @return array{applied: array<string>, pending: array<string>}
     */
    public function status(): array
    {
        $allMigrations = array_keys($this->findMigrations());
        $applied = $this->getAppliedMigrations();
        $pending = array_diff($allMigrations, $applied);

        return [
            'applied' => $applied,
            'pending' => array_values($pending),
        ];
    }

    /**
     * Rolls back all migrations
     *
     * @return array<string> Array of rolled back migration names
     */
    public function reset(): array
    {
        $applied = $this->getAppliedMigrations();
        if (empty($applied)) {
            return [];
        }

        $rolledBack = [];
        $migrations = $this->findMigrations();
        // Откатываем в обратном порядке
        $applied = array_reverse($applied);

        foreach ($applied as $migrationName) {
            if (!isset($migrations[$migrationName])) {
                continue;
            }

            try {
                $migration = $this->loadMigration($migrations[$migrationName]);
                echo "Rolling back migration: {$migrationName}...\n";
                $migration->down();
                $this->markRolledBack($migrationName);
                $rolledBack[] = $migrationName;
                echo "Migration {$migrationName} rolled back successfully.\n";
            } catch (Throwable $e) {
                echo "Error rolling back migration {$migrationName}: " . $e->getMessage() . "\n";
                throw $e;
            }
        }

        return $rolledBack;
    }

    /**
     * Drops all tables and reapplies migrations
     *
     * @return array<string> Array of applied migration names
     */
    public function fresh(): array
    {
        echo "Dropping all tables...\n";

        // Получаем все таблицы
        $stmt = $this->pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($tables)) {
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            foreach ($tables as $table) {
                // Пропускаем таблицу migrations
                if ($table === 'migrations') {
                    continue;
                }
                $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
                echo "Dropped table: {$table}\n";
            }
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        } else {
            echo "No tables to drop.\n";
        }

        // Очищаем таблицу migrations вместо дропа
        echo "Clearing migrations table...\n";
        $this->pdo->exec("TRUNCATE TABLE migrations");

        echo "Applying migrations...\n";
        return $this->up();
    }
}

