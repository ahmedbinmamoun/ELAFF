<?php

declare(strict_types=1);

use Elaf\Content\Presentation\Http\Controllers\ArticleController;
use Elaf\Content\Presentation\Http\Controllers\AuthorController;
use Elaf\Content\Presentation\Http\Controllers\CategoryController;
use Elaf\Content\Presentation\Http\Controllers\FavoritesController;
use Elaf\Content\Presentation\Http\Controllers\FeedController;
use Elaf\Content\Presentation\Http\Controllers\SeriesController;
use Elaf\Content\Presentation\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

/*
 * Public site (PRD §9). Loaded with the "web" middleware group.
 */
Route::get('/article/{id}/{slug?}', [ArticleController::class, 'show'])->whereNumber('id')->name('article.show');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');
Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
Route::get('/author/{slug}', [AuthorController::class, 'show'])->name('author.show');
Route::get('/series/{slug}', [SeriesController::class, 'show'])->name('series.show');
Route::get('/tag/{slug}', [TagController::class, 'show'])->name('tag.show');
Route::get('/feed/{category?}', FeedController::class)->name('feed');
Route::get('/favorites', [FavoritesController::class, 'index'])->name('favorites');
Route::get('/favorites/cards', [FavoritesController::class, 'cards'])->middleware('throttle:60,1')->name('favorites.cards');
