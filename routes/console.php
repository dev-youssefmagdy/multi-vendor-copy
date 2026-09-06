<?php

use App\Console\Commands\ArchiveOutOfStockProductsCommand;
use App\Console\Commands\CleanupAiGeneratedLogosCommand;
use App\Console\Commands\TranslateNewProductsCommand;
use App\Console\Commands\UpdateCurrencyRatesCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('currencies:update-rates', function () {
    $this->call(UpdateCurrencyRatesCommand::class);
})->daily();

Artisan::command('logos:cleanup-ai-generated', function () {
    $this->call(CleanupAiGeneratedLogosCommand::class);
})->daily();

Artisan::command('products:archive-out-of-stock', function () {
    $this->call(ArchiveOutOfStockProductsCommand::class);
})->daily();

Artisan::command('products:translate-new-schedule', function () {
    $this->call(TranslateNewProductsCommand::class);
})->weekly()->description('Auto-translate new catalog products into tenant AI-translation languages (Neozena pays)');

Artisan::command('queue:retry-all', function () {
    Artisan::call('queue:retry', ['id' => 'all']);
})->describe('Retry all failed jobs')->everyFiveMinutes();
