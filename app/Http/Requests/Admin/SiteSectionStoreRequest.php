<?php

namespace App\Http\Requests\Admin;

use App\Models\SiteSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteSectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipo = $this->input('tipo');
        $slug = $this->input('slug');
        $expectedType = SiteSection::HOME_TYPES[$slug] ?? null;

        return [
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::in(SiteSection::HOME_SLOTS),
                Rule::unique('site_sections', 'slug'),
            ],
            'titulo' => ['nullable', 'string', 'max:255'],
            'subtitulo' => ['nullable', 'string'],
            'tipo' => [
                'required',
                'string',
                'max:50',
                Rule::when($expectedType, Rule::in([$expectedType])),
            ],
            'conteudo' => ['nullable', 'array'],
            'conteudo.tag' => [Rule::requiredIf($tipo === 'hero_com_resultados'), 'string', 'max:255'],
            'conteudo.botoes' => [Rule::requiredIf(in_array($tipo, ['hero_com_resultados', 'cta_card'], true)), 'array', 'min:1'],
            'conteudo.botoes.*.label' => [Rule::requiredIf(in_array($tipo, ['hero_com_resultados', 'cta_card'], true)), 'string', 'max:255'],
            'conteudo.botoes.*.url' => [Rule::requiredIf(in_array($tipo, ['hero_com_resultados', 'cta_card'], true)), 'string', 'max:255'],
            'conteudo.botoes.*.style' => ['nullable', 'string', 'max:50'],
            'conteudo.cards' => [Rule::requiredIf($tipo === 'cards_grid'), 'array', 'min:1'],
            'conteudo.cards.*.titulo' => [Rule::requiredIf($tipo === 'cards_grid'), 'string', 'max:255'],
            'conteudo.cards.*.texto' => [Rule::requiredIf($tipo === 'cards_grid'), 'string', 'max:255'],
            'conteudo.cards.*.icone' => ['nullable', 'string', 'max:255'],
            'conteudo.resultados_titulo' => [Rule::requiredIf($tipo === 'hero_com_resultados'), 'string', 'max:255'],
            'conteudo.resultados_subtitulo' => [Rule::requiredIf($tipo === 'hero_com_resultados'), 'string', 'max:255'],
            'conteudo.resultados' => [Rule::requiredIf($tipo === 'hero_com_resultados'), 'array', 'min:1'],
            'conteudo.resultados.*.titulo' => [Rule::requiredIf($tipo === 'hero_com_resultados'), 'string', 'max:255'],
            'conteudo.resultados.*.texto' => [Rule::requiredIf($tipo === 'hero_com_resultados'), 'string', 'max:255'],
            'estilo' => ['nullable', 'array'],
            'estilo.background_type' => ['nullable', 'string', 'max:20'],
            'estilo.background_color' => ['nullable', 'string', 'max:20'],
            'estilo.background_image_id' => ['nullable', 'integer'],
            'estilo.overlay_opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'estilo.text_color' => ['nullable', 'string', 'max:20'],
            'estilo.container_width' => ['nullable', 'string', 'max:20'],
            'estilo.padding_top' => ['nullable', 'string', 'max:20'],
            'estilo.padding_bottom' => ['nullable', 'string', 'max:20'],
            'ativo' => ['required', 'boolean'],
            'ordem' => ['nullable', 'integer'],
        ];
    }
}
