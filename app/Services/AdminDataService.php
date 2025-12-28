<?php

namespace App\Services;

use App\Models\BoxTemplate;
use App\Models\Partner;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AdminDataService
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    // =====================
    // USER MANAGEMENT
    // =====================

    /**
     * Get all users (optionally filtered by role).
     */
    public function getUsers(?string $role = null): Collection
    {
        $query = User::query();

        if ($role) {
            $query->where('role', $role);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'employee',
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'role' => $data['role'] ?? $user->role,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ];

        // Only update password if provided
        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return $user->fresh();
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user): bool
    {
        return $user->delete();
    }

    // =====================
    // PARTNER MANAGEMENT (with caching)
    // =====================

    /**
     * Get all partners with caching.
     */
    public function getPartners(?bool $activeOnly = null): Collection
    {
        $cacheKey = 'partners:' . ($activeOnly === null ? 'all' : ($activeOnly ? 'active' : 'inactive'));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($activeOnly) {
            $query = Partner::query();

            if ($activeOnly !== null) {
                $query->where('is_active', $activeOnly);
            }

            return $query->with('productTemplates')->orderBy('name')->get();
        });
    }

    /**
     * Create a new partner.
     */
    public function createPartner(array $data): Partner
    {
        $partner = Partner::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->clearPartnerCache();

        return $partner;
    }

    /**
     * Update an existing partner.
     */
    public function updatePartner(Partner $partner, array $data): Partner
    {
        $partner->update([
            'name' => $data['name'] ?? $partner->name,
            'phone' => $data['phone'] ?? $partner->phone,
            'address' => $data['address'] ?? $partner->address,
            'is_active' => $data['is_active'] ?? $partner->is_active,
        ]);

        $this->clearPartnerCache();

        return $partner->fresh();
    }

    /**
     * Delete a partner.
     */
    public function deletePartner(Partner $partner): bool
    {
        $result = $partner->delete();
        $this->clearPartnerCache();
        return $result;
    }

    /**
     * Clear partner cache.
     */
    private function clearPartnerCache(): void
    {
        Cache::forget('partners:all');
        Cache::forget('partners:active');
        Cache::forget('partners:inactive');
    }

    // =====================
    // PRODUCT TEMPLATE MANAGEMENT (with caching)
    // =====================

    /**
     * Get all product templates with caching.
     */
    public function getProductTemplates(?int $partnerId = null): Collection
    {
        $cacheKey = 'product_templates:' . ($partnerId ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($partnerId) {
            $query = ProductTemplate::with('partner');

            if ($partnerId) {
                $query->where('partner_id', $partnerId);
            }

            return $query->orderBy('name')->get();
        });
    }

    /**
     * Create a new product template.
     */
    public function createProductTemplate(array $data): ProductTemplate
    {
        $template = ProductTemplate::create([
            'partner_id' => $data['partner_id'],
            'name' => $data['name'],
            'base_price' => $data['base_price'],
            'default_selling_price' => $data['default_selling_price'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->clearProductTemplateCache($data['partner_id']);

        return $template;
    }

    /**
     * Update an existing product template.
     */
    public function updateProductTemplate(ProductTemplate $template, array $data): ProductTemplate
    {
        $oldPartnerId = $template->partner_id;

        $template->update([
            'partner_id' => $data['partner_id'] ?? $template->partner_id,
            'name' => $data['name'] ?? $template->name,
            'base_price' => $data['base_price'] ?? $template->base_price,
            'default_selling_price' => $data['default_selling_price'] ?? $template->default_selling_price,
            'is_active' => $data['is_active'] ?? $template->is_active,
        ]);

        $this->clearProductTemplateCache($oldPartnerId);
        if (isset($data['partner_id']) && $data['partner_id'] !== $oldPartnerId) {
            $this->clearProductTemplateCache($data['partner_id']);
        }

        return $template->fresh();
    }

    /**
     * Clear product template cache.
     */
    private function clearProductTemplateCache(?int $partnerId = null): void
    {
        Cache::forget('product_templates:all');
        if ($partnerId) {
            Cache::forget("product_templates:{$partnerId}");
        }
    }

    // =====================
    // BOX TEMPLATE MANAGEMENT (with caching)
    // =====================

    /**
     * Get all box templates with caching.
     */
    public function getBoxTemplates(?string $type = null): Collection
    {
        $cacheKey = 'box_templates:' . ($type ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type) {
            $query = BoxTemplate::query();

            if ($type) {
                $query->where('type', $type);
            }

            return $query->orderBy('name')->get();
        });
    }

    /**
     * Create a new box template.
     */
    public function createBoxTemplate(array $data): BoxTemplate
    {
        $template = BoxTemplate::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'price' => $data['price'],
            'items_json' => $data['items_json'] ?? [],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->clearBoxTemplateCache();

        return $template;
    }

    /**
     * Update an existing box template.
     */
    public function updateBoxTemplate(BoxTemplate $template, array $data): BoxTemplate
    {
        $template->update([
            'name' => $data['name'] ?? $template->name,
            'type' => $data['type'] ?? $template->type,
            'price' => $data['price'] ?? $template->price,
            'items_json' => $data['items_json'] ?? $template->items_json,
            'is_active' => $data['is_active'] ?? $template->is_active,
        ]);

        $this->clearBoxTemplateCache();

        return $template->fresh();
    }

    /**
     * Delete a box template.
     */
    public function deleteBoxTemplate(BoxTemplate $template): bool
    {
        $result = $template->delete();
        $this->clearBoxTemplateCache();
        return $result;
    }

    /**
     * Clear box template cache.
     */
    private function clearBoxTemplateCache(): void
    {
        Cache::forget('box_templates:all');
        Cache::forget('box_templates:heavy_meal');
        Cache::forget('box_templates:snack_box');
    }
}
