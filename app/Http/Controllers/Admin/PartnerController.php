<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\ProductTemplate;
use App\Services\AdminDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function __construct(
        protected AdminDataService $adminDataService
    ) {
    }

    /**
     * Display a listing of partners.
     */
    public function index(): Response
    {
        $partners = Partner::with('productTemplates')->orderBy('name')->get();

        return Inertia::render('Admin/Partners/Index', [
            'partners' => $partners,
        ]);
    }

    /**
     * Show the form for creating a new partner.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Partners/Create');
    }

    /**
     * Store a newly created partner with product templates.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
            'product_templates' => 'nullable|array',
            'product_templates.*.name' => 'required|string|max:255',
            'product_templates.*.base_price' => 'required|numeric|min:0',
            'product_templates.*.default_selling_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $partner = Partner::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if (!empty($validated['product_templates'])) {
                foreach ($validated['product_templates'] as $template) {
                    $partner->productTemplates()->create([
                        'name' => $template['name'],
                        'base_price' => $template['base_price'],
                        'default_selling_price' => $template['default_selling_price'],
                        'is_active' => true,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.partners.index')
            ->with('success', 'Partner berhasil ditambahkan.');
    }

    /**
     * Show the form for editing a partner.
     */
    public function edit(Partner $partner): Response
    {
        $partner->load('productTemplates');

        return Inertia::render('Admin/Partners/Edit', [
            'partner' => $partner,
        ]);
    }

    /**
     * Update the specified partner with product templates.
     */
    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
            'product_templates' => 'nullable|array',
            'product_templates.*.id' => 'nullable|exists:product_templates,id',
            'product_templates.*.name' => 'required|string|max:255',
            'product_templates.*.base_price' => 'required|numeric|min:0',
            'product_templates.*.default_selling_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $partner) {
            $partner->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Get IDs of templates in the request
            $templateIds = collect($validated['product_templates'] ?? [])
                ->pluck('id')
                ->filter()
                ->toArray();

            // Delete templates not in the request
            $partner->productTemplates()->whereNotIn('id', $templateIds)->delete();

            // Update or create templates
            foreach ($validated['product_templates'] ?? [] as $templateData) {
                if (!empty($templateData['id'])) {
                    ProductTemplate::where('id', $templateData['id'])
                        ->where('partner_id', $partner->id)
                        ->update([
                            'name' => $templateData['name'],
                            'base_price' => $templateData['base_price'],
                            'default_selling_price' => $templateData['default_selling_price'],
                        ]);
                } else {
                    $partner->productTemplates()->create([
                        'name' => $templateData['name'],
                        'base_price' => $templateData['base_price'],
                        'default_selling_price' => $templateData['default_selling_price'],
                        'is_active' => true,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.partners.index')
            ->with('success', 'Partner berhasil diperbarui.');
    }

    /**
     * Remove the specified partner.
     */
    public function destroy(Partner $partner): RedirectResponse
    {
        $this->adminDataService->deletePartner($partner);

        return redirect()
            ->route('admin.partners.index')
            ->with('success', 'Partner berhasil dihapus.');
    }
}
