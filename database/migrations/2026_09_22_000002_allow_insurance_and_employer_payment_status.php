<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * appointments.payment_status was created as an enum of pending/completed/failed/cancelled.
 * Insurance-confirmed and employer-paid visits store "insurance" and "employer", which PostgreSQL
 * rejected because of that check constraint. Allow them.
 */
return new class extends Migration
{
    private const ALLOWED = ['pending', 'completed', 'failed', 'cancelled', 'insurance', 'employer'];

    public function up(): void
    {
        $this->apply(self::ALLOWED);
    }

    public function down(): void
    {
        DB::table('appointments')->whereIn('payment_status', ['insurance', 'employer'])->update(['payment_status' => 'completed']);
        $this->apply(['pending', 'completed', 'failed', 'cancelled']);
    }

    private function apply(array $values): void
    {
        $list = "'" . implode("','", $values) . "'";

        match (DB::getDriverName()) {
            'pgsql' => DB::unprepared(
                'ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_payment_status_check; '
                . "ALTER TABLE appointments ADD CONSTRAINT appointments_payment_status_check CHECK (payment_status IS NULL OR payment_status IN ({$list}))"
            ),
            'mysql', 'mariadb' => DB::statement("ALTER TABLE appointments MODIFY payment_status ENUM({$list}) NULL"),
            default => null,   // SQLite in tests does not enforce the enum
        };
    }
};
