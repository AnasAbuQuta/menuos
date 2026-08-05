<?php

use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
