<?php

namespace App\Services;

use App\Models\BoxTemplate;
use App\Models\Partner;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class AdminDataService
{
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
    // PARTNER MANAGEMENT
    // =====================

    /**
     * Get all partners.
     */
    public function getPartners(?bool $activeOnly = null): Collection
    {
        $query = Partner::query();

        if ($activeOnly !== null) {
            $query->where('is_active', $activeOnly);
        }

        return $query->with('productTemplates')->orderBy('name')->get();
    }

    /**
     * Create a new partner.
     */
    public function createPartner(array $data): Partner
    {
        return Partner::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
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

        return $partner->fresh();
    }

    /**
     * Delete a partner.
     */
    public function deletePartner(Partner $partner): bool
    {
        return $partner->delete();
    }

    // =====================
    // PRODUCT TEMPLATE MANAGEMENT
    // =====================

    /**
     * Get all product templates.
     */
    public function getProductTemplates(?int $partnerId = null): Collection
    {
        $query = ProductTemplate::with('partner');

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Create a new product template.
     */
    public function createProductTemplate(array $data): ProductTemplate
    {
        return ProductTemplate::create([
            'partner_id' => $data['partner_id'],
            'name' => $data['name'],
            'base_price' => $data['base_price'],
            'default_selling_price' => $data['default_selling_price'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Update an existing product template.
     */
    public function updateProductTemplate(ProductTemplate $template, array $data): ProductTemplate
    {
        $template->update([
            'partner_id' => $data['partner_id'] ?? $template->partner_id,
            'name' => $data['name'] ?? $template->name,
            'base_price' => $data['base_price'] ?? $template->base_price,
            'default_selling_price' => $data['default_selling_price'] ?? $template->default_selling_price,
            'is_active' => $data['is_active'] ?? $template->is_active,
        ]);

        return $template->fresh();
    }

    // =====================
    // BOX TEMPLATE MANAGEMENT
    // =====================

    /**
     * Get all box templates.
     */
    public function getBoxTemplates(?string $type = null): Collection
    {
        $query = BoxTemplate::query();

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Create a new box template.
     */
    public function createBoxTemplate(array $data): BoxTemplate
    {
        return BoxTemplate::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'price' => $data['price'],
            'items_json' => $data['items_json'] ?? [],
            'is_active' => $data['is_active'] ?? true,
        ]);
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

        return $template->fresh();
    }

    /**
     * Delete a box template.
     */
    public function deleteBoxTemplate(BoxTemplate $template): bool
    {
        return $template->delete();
    }
}
