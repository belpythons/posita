<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoxTemplate;
use App\Services\AdminDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoxTemplateController extends Controller
{
    public function __construct(
        protected AdminDataService $adminDataService
    ) {
    }

    /**
     * Display a listing of box templates.
     */
    public function index(): Response
    {
        $templates = $this->adminDataService->getBoxTemplates();

        // Separate by type for easier frontend handling
        $heavyMealTemplates = $templates->where('type', 'heavy_meal')->values();
        $snackBoxTemplates = $templates->where('type', 'snack_box')->values();

        return Inertia::render('Admin/BoxTemplates/Index', [
            'templates' => $templates,
            'heavyMealTemplates' => $heavyMealTemplates,
            'snackBoxTemplates' => $snackBoxTemplates,
        ]);
    }

    /**
     * Show the form for creating a new box template.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/BoxTemplates/Create');
    }

    /**
     * Store a newly created box template.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:heavy_meal,snack_box',
            'price' => 'required|numeric|min:0',
            'items_json' => 'required|array',
            'items_json.*' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $this->adminDataService->createBoxTemplate($validated);

        return redirect()
            ->route('admin.box-templates.index')
            ->with('success', 'Template box berhasil ditambahkan.');
    }

    /**
     * Show the form for editing a box template.
     */
    public function edit(BoxTemplate $boxTemplate): Response
    {
        return Inertia::render('Admin/BoxTemplates/Edit', [
            'template' => $boxTemplate,
        ]);
    }

    /**
     * Update the specified box template.
     */
    public function update(Request $request, BoxTemplate $boxTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:heavy_meal,snack_box',
            'price' => 'required|numeric|min:0',
            'items_json' => 'required|array',
            'items_json.*' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $this->adminDataService->updateBoxTemplate($boxTemplate, $validated);

        return redirect()
            ->route('admin.box-templates.index')
            ->with('success', 'Template box berhasil diperbarui.');
    }

    /**
     * Remove the specified box template.
     */
    public function destroy(BoxTemplate $boxTemplate): RedirectResponse
    {
        $this->adminDataService->deleteBoxTemplate($boxTemplate);

        return redirect()
            ->route('admin.box-templates.index')
            ->with('success', 'Template box berhasil dihapus.');
    }
}
