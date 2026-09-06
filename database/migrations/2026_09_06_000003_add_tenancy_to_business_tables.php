<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tables that get a tenant_id. */
    private const TENANT_TABLES = [
        'partners',
        'product_templates',
        'box_templates',
        'shop_sessions',
        'daily_consignments',
        'box_orders',
        'box_order_items',
        'daily_stats',
    ];

    /** Tables that also get an outlet_id. */
    private const OUTLET_TABLES = [
        'shop_sessions',
        'daily_consignments',
        'box_orders',
        'daily_stats',
    ];

    public function up(): void
    {
        // Order matters: add the columns nullable, backfill, and only then tighten
        // them to NOT NULL. Doing it the other way round fails on a database that
        // already holds rows.
        $this->addColumns();

        [$tenantId, $outletId] = $this->backfill();

        if ($tenantId !== null) {
            $this->tighten();
        }

        $this->swapDailyStatsUniqueKey();
        $this->addIndexes();

        Schema::table('users', function (Blueprint $table) {
            // Nullable on purpose: a platform super admin belongs to no tenant.
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('is_super_admin')->default(false)->after('role');
        });

        if ($tenantId !== null) {
            DB::table('users')->update(['tenant_id' => $tenantId]);
            DB::table('outlet_user')->insertOrIgnore(
                DB::table('users')->pluck('id')->map(fn ($userId) => [
                    'outlet_id' => $outletId,
                    'user_id' => $userId,
                    'is_primary' => true,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'is_super_admin']);
        });

        foreach (self::TENANT_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropIndex($table.'_tenant_id_created_at_index');
                $blueprint->dropIndex($table.'_tenant_id_updated_at_index');
            });
        }

        // Restoring the global unique on `date` only succeeds when at most one
        // tenant has rows. On genuinely multi-tenant data this rollback cannot
        // succeed, and failing loudly is better than silently dropping rows.
        Schema::table('daily_stats', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'outlet_id', 'date']);
        });

        foreach (self::OUTLET_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['outlet_id']);
                $blueprint->dropColumn('outlet_id');
            });
        }

        foreach (self::TENANT_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['tenant_id']);
                $blueprint->dropColumn('tenant_id');
            });
        }

        Schema::table('daily_stats', function (Blueprint $table) {
            $table->unique('date');
        });
    }

    private function addColumns(): void
    {
        foreach (self::TENANT_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();

                if (in_array($table, self::OUTLET_TABLES, true)) {
                    $blueprint->foreignId('outlet_id')->nullable()->after('tenant_id')->constrained()->cascadeOnDelete();
                }
            });
        }
    }

    /**
     * Move any pre-existing single-tenant data onto a default tenant and outlet.
     *
     * Returns [tenantId, outletId], or [null, null] on a fresh install where
     * there is nothing to migrate — a fresh database gets its tenants from the
     * seeder instead of an orphan "default" row.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function backfill(): array
    {
        $hasData = collect(self::TENANT_TABLES)
            ->contains(fn (string $table) => DB::table($table)->exists());

        if (! $hasData) {
            return [null, null];
        }

        $now = now();

        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Toko Utama',
            'slug' => 'default',
            'business_type' => 'coffee_shop',
            'plan' => 'free',
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'locale' => 'id',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $outletId = DB::table('outlets')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Outlet Utama',
            'code' => 'MAIN',
            'geofence_radius_m' => 100,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (self::TENANT_TABLES as $table) {
            $values = ['tenant_id' => $tenantId];

            if (in_array($table, self::OUTLET_TABLES, true)) {
                $values['outlet_id'] = $outletId;
            }

            DB::table($table)->update($values);
        }

        return [$tenantId, $outletId];
    }

    private function tighten(): void
    {
        foreach (self::TENANT_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unsignedBigInteger('tenant_id')->nullable(false)->change();

                if (in_array($table, self::OUTLET_TABLES, true)) {
                    $blueprint->unsignedBigInteger('outlet_id')->nullable(false)->change();
                }
            });
        }
    }

    /**
     * `daily_stats.date` was globally unique, so two tenants could not both have
     * a row for the same day — the nightly aggregation of one would overwrite
     * the other's.
     */
    private function swapDailyStatsUniqueKey(): void
    {
        Schema::table('daily_stats', function (Blueprint $table) {
            $table->dropUnique('daily_stats_date_unique');
        });

        Schema::table('daily_stats', function (Blueprint $table) {
            $table->unique(['tenant_id', 'outlet_id', 'date']);
        });
    }

    private function addIndexes(): void
    {
        foreach (self::TENANT_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->index(['tenant_id', 'created_at']);
                // Composite on updated_at is for the cursor-based sync in P12.
                $blueprint->index(['tenant_id', 'updated_at']);
            });
        }
    }
};
