<?php

use App\Services\ConfiguracaoService;

if (! function_exists('config_db')) {
    function config_db(string $chave, mixed $default = null): mixed
    {
        return app(ConfiguracaoService::class)->get($chave, $default);
    }
}
