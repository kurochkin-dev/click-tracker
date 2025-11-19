<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Migration;

/**
 * Migration interface
 */
interface Migration
{
    /**
     * Returns migration name (without timestamp)
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Applies migration
     */
    public function up(): void;

    /**
     * Rolls back migration
     */
    public function down(): void;
}

