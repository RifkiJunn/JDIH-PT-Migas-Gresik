<?php

namespace App\Providers;

use App\Services\Ai\GeminiMetadataExtractor;
use App\Services\Ai\MetadataExtractor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Ganti binding ini untuk memakai penyedia AI lain.
        $this->app->bind(MetadataExtractor::class, fn () => new GeminiMetadataExtractor(
            config('ai.gemini.key'),
            config('ai.gemini.model'),
        ));
    }

    public function boot(): void
    {
        //
    }
}
