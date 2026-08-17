<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_penalties')) {
            return;
        }

        Schema::table('account_penalties', function (Blueprint $table): void {
            if (! Schema::hasColumn('account_penalties', 'status')) {
                $table->string('status')->default('active')->after('action_type');
            }

            if (! Schema::hasColumn('account_penalties', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('ends_at');
            }

            if (! Schema::hasColumn('account_penalties', 'resolved_by')) {
                $table->foreignId('resolved_by')->nullable()->after('resolved_at');
            }

            if (! Schema::hasColumn('account_penalties', 'resolved_reason')) {
                $table->text('resolved_reason')->nullable()->after('resolved_by');
            }

            if (! Schema::hasColumn('account_penalties', 'resolved_by_penalty_id')) {
                $table->foreignId('resolved_by_penalty_id')->nullable()->after('resolved_reason');
            }
        });

        $this->addForeignIfMissing('account_penalties_resolved_by_foreign', 'resolved_by', 'users');
        $this->addForeignIfMissing('account_penalties_resolved_by_penalty_id_foreign', 'resolved_by_penalty_id', 'account_penalties');
        $this->addStatusCheckIfMissing();
    }

    public function down(): void
    {
        if (! Schema::hasTable('account_penalties')) {
            return;
        }

        $this->dropConstraintIfExists('account_penalties_status_check');
        $this->dropConstraintIfExists('account_penalties_resolved_by_penalty_id_foreign');
        $this->dropConstraintIfExists('account_penalties_resolved_by_foreign');

        Schema::table('account_penalties', function (Blueprint $table): void {
            foreach (['resolved_by_penalty_id', 'resolved_reason', 'resolved_by', 'resolved_at', 'status'] as $column) {
                if (Schema::hasColumn('account_penalties', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function addForeignIfMissing(string $constraint, string $column, string $foreignTable): void
    {
        if ($this->isSqlite() || ! Schema::hasColumn('account_penalties', $column) || $this->constraintExists($constraint)) {
            return;
        }

        $onDelete = $foreignTable === 'users' ? 'set null' : 'set null';

        DB::statement("alter table account_penalties add constraint {$constraint} foreign key ({$column}) references {$foreignTable} (id) on delete {$onDelete}");
    }

    private function addStatusCheckIfMissing(): void
    {
        if ($this->isSqlite() || ! Schema::hasColumn('account_penalties', 'status') || $this->constraintExists('account_penalties_status_check')) {
            return;
        }

        DB::statement("alter table account_penalties add constraint account_penalties_status_check check (status in ('active', 'resolved', 'expired', 'superseded'))");
    }

    private function dropConstraintIfExists(string $constraint): void
    {
        if ($this->isSqlite() || ! $this->constraintExists($constraint)) {
            return;
        }

        DB::statement("alter table account_penalties drop constraint {$constraint}");
    }

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    private function constraintExists(string $constraint): bool
    {
        return DB::table('pg_constraint')
            ->where('conname', $constraint)
            ->exists();
    }
};