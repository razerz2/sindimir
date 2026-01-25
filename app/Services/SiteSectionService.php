<?php

namespace App\Services;

use App\Models\SiteSection;
use App\Models\MediaAsset;
use App\Services\ConfiguracaoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSectionService
{
    public const CACHE_KEY = 'public.institucional';

    public function create(array $data): SiteSection
    {
        $data['conteudo'] = $data['conteudo'] ?? [];
        $data['estilo'] = $this->normalizeStyle($data['estilo'] ?? []);
        $data['ordem'] = $data['ordem'] ?? ($this->nextOrder());

        $section = SiteSection::create($data);

        $this->flushCache();

        return $section;
    }

    public function update(SiteSection $section, array $data): SiteSection
    {
        if (! array_key_exists('conteudo', $data)) {
            $data['conteudo'] = $section->conteudo ?? [];
        }
        if (array_key_exists('estilo', $data)) {
            $data['estilo'] = $this->normalizeStyle($data['estilo'] ?? []);
        }

        $section->update($data);

        $this->flushCache();

        return $section;
    }

    public function delete(SiteSection $section): void
    {
        $section->delete();
        $this->flushCache();
    }

    public function duplicate(SiteSection $section): SiteSection
    {
        $copy = $section->replicate();
        $copy->slug = $this->uniqueSlug($section->slug);
        $copy->ordem = $this->nextOrder();
        $copy->ativo = false;
        $copy->save();

        $this->flushCache();

        return $copy;
    }

    /**
     * @param array<int, int> $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $normalized = collect($orderedIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($normalized) {
            $existing = SiteSection::query()
                ->orderBy('ordem')
                ->pluck('id');

            $remaining = $existing->diff($normalized)->values();
            $finalOrder = $normalized->merge($remaining)->values();

            foreach ($finalOrder as $index => $id) {
                SiteSection::query()
                    ->whereKey($id)
                    ->update(['ordem' => $index + 1]);
            }
        });

        $this->flushCache();
    }

    /**
     * @param array<string, mixed> $style
     * @return array<string, mixed>
     */
    public function normalizeStyle(array $style): array
    {
        return array_merge([
            'background_type' => 'color',
            'background_color' => '#ffffff',
            'background_image_id' => null,
            'overlay_opacity' => 0.3,
            'text_color' => '#111827',
            'container_width' => 'default',
            'padding_top' => 'py-16',
            'padding_bottom' => 'py-16',
        ], $style);
    }

    /**
     * @param array<int, MediaAsset> $mediaAssets
     * @return array<string, string|null>
     */
    public function resolveStyle(SiteSection $section, array $mediaAssets = []): array
    {
        $style = $this->normalizeStyle($section->estilo ?? []);
        $backgroundStyle = '';

        if ($style['background_type'] === 'image' && $style['background_image_id']) {
            $asset = $mediaAssets[$style['background_image_id']] ?? null;
            if ($asset) {
                $url = Storage::disk('public')->url($asset->path);
                $backgroundStyle = "background-image: url('{$url}'); background-size: cover; background-position: center;";
            }
        }

        if ($style['background_type'] === 'gradient') {
            $backgroundStyle = "background-image: {$style['background_color']};";
        }

        if ($style['background_type'] === 'color' && $style['background_color']) {
            $backgroundStyle = "background-color: {$style['background_color']};";
        }

        $hasOverlay = ($style['background_type'] ?? 'color') !== 'color' && (float) ($style['overlay_opacity'] ?? 0) > 0;

        return [
            'background_style' => $backgroundStyle,
            'text_color' => $style['text_color'] ?? '#111827',
            'container_style' => $this->resolveContainerStyle($style['container_width'] ?? 'default'),
            'padding_top' => $this->spacingToRem($style['padding_top'] ?? 'py-16'),
            'padding_bottom' => $this->spacingToRem($style['padding_bottom'] ?? 'py-16'),
            'overlay_opacity' => (string) ($style['overlay_opacity'] ?? 0.3),
            'background_type' => $style['background_type'] ?? 'color',
            'has_overlay' => $hasOverlay,
        ];
    }

    /**
     * @param array<int, MediaAsset> $mediaAssets
     * @return array<string, mixed>
     */
    public function resolveContent(SiteSection $section, array $mediaAssets = []): array
    {
        $content = $section->conteudo ?? [];

        if ($section->tipo === 'parceiros') {
            $logos = collect($content['logos'] ?? [])
                ->map(function (array $logo) use ($mediaAssets) {
                    $asset = $mediaAssets[$logo['media_asset_id'] ?? null] ?? null;
                    return [
                        'media_asset_id' => $logo['media_asset_id'] ?? null,
                        'link' => $logo['link'] ?? null,
                        'url' => $asset ? Storage::disk('public')->url($asset->path) : null,
                        'alt' => $asset?->alt_text,
                    ];
                })
                ->all();

            $content['logos'] = $logos;
        }

        return $content;
    }

    public function getHomeRenderData(ConfiguracaoService $configuracaoService): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () use ($configuracaoService) {
            $sections = SiteSection::query()
                ->ativos()
                ->ordenados()
                ->get();

            $hero = $sections->firstWhere('tipo', 'hero_com_resultados') ?? new SiteSection([
                'titulo' => '',
                'subtitulo' => '',
                'conteudo' => [
                    'tag' => '',
                    'botoes' => [],
                    'resultados_titulo' => '',
                    'resultados_subtitulo' => '',
                    'resultados' => [],
                ],
            ]);
            $cta = $sections->firstWhere('tipo', 'cta_card') ?? new SiteSection([
                'titulo' => '',
                'subtitulo' => '',
                'conteudo' => [
                    'botoes' => [],
                ],
            ]);
            $cardsSections = $sections->where('tipo', 'cards_grid')->values();

            return [
                'sections' => $cardsSections,
                'hero' => $hero,
                'cta' => $cta,
                'footer' => [
                    'titulo' => $configuracaoService->get('site.footer.titulo', 'Sindimir'),
                    'descricao' => $configuracaoService->get(
                        'site.footer.descricao',
                        'Solucoes digitais para capacitacao, eventos e desenvolvimento do setor metal mecanico.'
                    ),
                    'contato_titulo' => $configuracaoService->get('site.footer.contato_titulo', 'Contato'),
                    'contato_email' => $configuracaoService->get('site.footer.contato_email', 'contato@sindimir.org'),
                    'contato_telefone' => $configuracaoService->get('site.footer.contato_telefone', '(00) 0000-0000'),
                    'endereco_titulo' => $configuracaoService->get('site.footer.endereco_titulo', 'Endereco'),
                    'endereco_linha1' => $configuracaoService->get('site.footer.endereco_linha1', 'Rua da Industria, 1000'),
                    'endereco_linha2' => $configuracaoService->get('site.footer.endereco_linha2', 'Distrito Industrial'),
                ],
                'metaTitle' => $configuracaoService->get('site.meta_title', 'Sindimir'),
                'metaDescription' => $configuracaoService->get('site.meta_description', ''),
            ];
        });
    }

    private function resolveContainerStyle(string $width): string
    {
        return match ($width) {
            'wide' => 'max-width: 1280px;',
            'full' => 'max-width: 100%;',
            default => '',
        };
    }

    private function spacingToRem(string $value): string
    {
        if (preg_match('/\d+/', $value, $matches) !== 1) {
            return '4rem';
        }

        $number = (int) $matches[0];
        $rem = $number * 0.25;

        return "{$rem}rem";
    }

    private function nextOrder(): int
    {
        return (int) SiteSection::query()->max('ordem') + 1;
    }

    private function uniqueSlug(string $slug): string
    {
        $base = $slug;
        $suffix = 2;

        while (SiteSection::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
