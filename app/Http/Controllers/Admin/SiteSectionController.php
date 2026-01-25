<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteSectionStoreRequest;
use App\Http\Requests\Admin\SiteSectionUpdateRequest;
use App\Models\MediaAsset;
use App\Models\SiteSection;
use App\Services\SiteSectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteSectionController extends Controller
{
    public function __construct(private readonly SiteSectionService $siteSectionService)
    {
        $this->authorizeResource(SiteSection::class, 'section');
    }

    public function index(): View
    {
        $sections = SiteSection::query()
            ->orderBy('ordem')
            ->get();

        return view('admin.site.sections.index', compact('sections'));
    }

    public function create(): View
    {
        $assets = MediaAsset::query()->latest()->get();

        return view('admin.site.sections.form', [
            'section' => new SiteSection([
                'ativo' => true,
                'conteudo' => [],
                'estilo' => $this->siteSectionService->normalizeStyle([]),
            ]),
            'assets' => $assets,
        ]);
    }

    public function store(SiteSectionStoreRequest $request): RedirectResponse
    {
        $section = $this->siteSectionService->create($request->validated());

        return redirect()
            ->route('admin.site.sections.edit', $section)
            ->with('status', 'Section criada com sucesso.');
    }

    public function edit(SiteSection $section): View
    {
        $assets = MediaAsset::query()->latest()->get();
        $section->estilo = $this->siteSectionService->normalizeStyle($section->estilo ?? []);

        return view('admin.site.sections.form', compact('section', 'assets'));
    }

    public function update(SiteSectionUpdateRequest $request, SiteSection $section): RedirectResponse
    {
        $data = $request->validated();
        if (in_array($section->slug, SiteSection::HOME_SLOTS, true)) {
            $data['slug'] = $section->slug;
            $data['tipo'] = $section->tipo;
        }

        $this->siteSectionService->update($section, $data);

        return redirect()
            ->route('admin.site.sections.edit', $section)
            ->with('status', 'Section atualizada com sucesso.');
    }

    public function destroy(SiteSection $section): RedirectResponse
    {
        if (in_array($section->slug, SiteSection::HOME_SLOTS, true)) {
            return redirect()
                ->route('admin.site.sections.index')
                ->with('status', 'Esta section faz parte da home e não pode ser removida.');
        }

        $this->siteSectionService->delete($section);

        return redirect()
            ->route('admin.site.sections.index')
            ->with('status', 'Section removida com sucesso.');
    }

    public function duplicate(SiteSection $section): RedirectResponse
    {
        $this->authorize('create', SiteSection::class);
        if (in_array($section->slug, SiteSection::HOME_SLOTS, true)) {
            return redirect()
                ->route('admin.site.sections.index')
                ->with('status', 'Esta section faz parte da home e não pode ser duplicada.');
        }

        $copy = $this->siteSectionService->duplicate($section);

        return redirect()
            ->route('admin.site.sections.edit', $copy)
            ->with('status', 'Section duplicada com sucesso.');
    }

    public function toggle(SiteSection $section): RedirectResponse
    {
        $this->authorize('update', $section);
        $this->siteSectionService->update($section, [
            'ativo' => ! $section->ativo,
        ]);

        return redirect()
            ->route('admin.site.sections.index')
            ->with('status', 'Status atualizado.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', SiteSection::class);

        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            return redirect()
                ->route('admin.site.sections.index')
                ->with('status', 'Nenhuma alteração aplicada.');
        }

        $this->siteSectionService->reorder(array_values($ids));

        return redirect()
            ->route('admin.site.sections.index')
            ->with('status', 'Ordem atualizada.');
    }
}
