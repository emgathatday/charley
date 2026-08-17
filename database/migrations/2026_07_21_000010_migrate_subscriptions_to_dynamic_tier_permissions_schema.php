<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->upgradeSubscriptionTiers();
        $this->upgradePartnerSubscriptions();
        $this->upgradeSubscriptionPayments();
        $this->createSubscriptionPermissions();
        $this->createSubscriptionTierPermissions();
        $this->createSubscriptionUsageCounters();
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_usage_counters');
        Schema::dropIfExists('subscription_tier_permissions');
        Schema::dropIfExists('subscription_permissions');

        if (Schema::hasTable('partner_subscriptions')) {
            Schema::table('partner_subscriptions', function (Blueprint $table): void {
                foreach (['auto_renew', 'cancelled_at', 'cancellation_reason'] as $column) {
                    if (Schema::hasColumn('partner_subscriptions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('subscription_tiers')) {
            DB::statement('drop index if exists subscription_tiers_code_unique');
            Schema::table('subscription_tiers', function (Blueprint $table): void {
                foreach (['code', 'display_name', 'description', 'billing_cycle', 'duration_days', 'sort_order', 'is_public', 'created_at', 'updated_at'] as $column) {
                    if (Schema::hasColumn('subscription_tiers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function upgradeSubscriptionTiers(): void
    {
        if (! Schema::hasTable('subscription_tiers')) {
            Schema::create('subscription_tiers', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('display_name');
                $table->text('description')->nullable();
                $table->decimal('monthly_price', 12, 2)->default(0);
                $table->string('billing_cycle')->default('monthly');
                $table->integer('duration_days')->nullable();
                $table->integer('sort_order')->default(0)->index();
                $table->boolean('is_public')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            return;
        }

        Schema::table('subscription_tiers', function (Blueprint $table): void {
            if (! Schema::hasColumn('subscription_tiers', 'code')) {
                $table->string('code')->nullable()->after('id');
            }
            if (! Schema::hasColumn('subscription_tiers', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('subscription_tiers', 'description')) {
                $table->text('description')->nullable()->after('display_name');
            }
            if (! Schema::hasColumn('subscription_tiers', 'billing_cycle')) {
                $table->string('billing_cycle')->default('monthly')->after('monthly_price');
            }
            if (! Schema::hasColumn('subscription_tiers', 'duration_days')) {
                $table->integer('duration_days')->nullable()->after('billing_cycle');
            }
            if (! Schema::hasColumn('subscription_tiers', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('duration_days');
            }
            if (! Schema::hasColumn('subscription_tiers', 'is_public')) {
                $table->boolean('is_public')->default(true)->after('sort_order');
            }
            if (! Schema::hasColumn('subscription_tiers', 'created_at')) {
                $table->timestamps();
            }
        });

        DB::table('subscription_tiers')->orderBy('id')->get(['id', 'name', 'code', 'display_name'])->each(function (object $tier): void {
            DB::table('subscription_tiers')->where('id', $tier->id)->update([
                'code' => $tier->code ?: Str::slug((string) $tier->name, '_'),
                'display_name' => $tier->display_name ?: Str::title(str_replace(['_', '-'], ' ', (string) $tier->name)),
                'billing_cycle' => 'monthly',
                'updated_at' => now(),
                'created_at' => DB::raw('coalesce(created_at, now())'),
            ]);
        });

        DB::statement('create unique index if not exists subscription_tiers_code_unique on subscription_tiers (code)');

        if ($this->supportsAlterColumnStatements()) {
            DB::statement('alter table subscription_tiers alter column code set not null');
            DB::statement('alter table subscription_tiers alter column display_name set not null');
        }
    }

    private function upgradePartnerSubscriptions(): void
    {
        if (! Schema::hasTable('partner_subscriptions')) {
            return;
        }

        Schema::table('partner_subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('partner_subscriptions', 'auto_renew')) {
                $table->boolean('auto_renew')->default(false)->after('status');
            }
            if (! Schema::hasColumn('partner_subscriptions', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('ends_at');
            }
            if (! Schema::hasColumn('partner_subscriptions', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            }
        });

        if ($this->supportsAlterColumnStatements()) {
            DB::statement('alter table partner_subscriptions alter column starts_at drop not null');
            DB::statement('alter table partner_subscriptions alter column ends_at drop not null');
        }
    }

    private function upgradeSubscriptionPayments(): void
    {
        if (! Schema::hasTable('subscription_payments')) {
            return;
        }

        if ($this->supportsAlterColumnStatements()) {
            DB::statement("alter table subscription_payments alter column status type varchar(255)");
        }
    }

    private function supportsAlterColumnStatements(): bool
    {
        return DB::connection()->getDriverName() !== 'sqlite';
    }

    private function createSubscriptionPermissions(): void
    {
        if (Schema::hasTable('subscription_permissions')) {
            return;
        }

        Schema::create('subscription_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('module')->nullable()->index();
            $table->string('value_type')->default('boolean');
            $table->json('default_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function createSubscriptionTierPermissions(): void
    {
        if (Schema::hasTable('subscription_tier_permissions')) {
            return;
        }

        Schema::create('subscription_tier_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tier_id')->constrained('subscription_tiers')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('subscription_permissions')->cascadeOnDelete();
            $table->json('value');
            $table->timestamps();
            $table->unique(['tier_id', 'permission_id']);
        });
    }

    private function createSubscriptionUsageCounters(): void
    {
        if (Schema::hasTable('subscription_usage_counters')) {
            return;
        }

        Schema::create('subscription_usage_counters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_subscription_id')->constrained('partner_subscriptions')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('subscription_permissions')->cascadeOnDelete();
            $table->string('period')->index();
            $table->integer('used_count')->default(0);
            $table->integer('quota_limit');
            $table->timestamp('reset_at')->nullable();
            $table->timestamps();
            $table->unique(['partner_subscription_id', 'permission_id', 'period'], 'subscription_usage_counter_unique');
        });
    }
};

