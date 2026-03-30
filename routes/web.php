<?php

use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('partners', 'pages::partners.list')
        ->can('viewAny', User::class)
        ->name('partners.list');

    Route::livewire('partners/{partner}', 'pages::partners.show')
        ->can('view', 'partner')
        ->name('partners.show');

    Route::livewire('restaurants/{restaurant}', 'pages::restaurants.show')
        ->can('view', 'restaurant')
        ->name('restaurants.show');

    Route::livewire('restaurants/{restaurant}/orders', 'pages::restaurants.orders')
        ->can('view', 'restaurant')
        ->name('restaurants.orders');
});

Route::get('/app/menu/{table}', function (Table $table) {
    $order = $table->orders()->pending()->firstOrCreate();

    return redirect()->route('app.menu', ['order' => $order]);
})->name('app.start');

Route::livewire('/app/order/{order}', 'pages::app.menu')->name('app.menu');
Route::livewire('/app/order/{order}/pay', 'pages::app.pay')->name('app.pay');

require __DIR__.'/settings.php';
