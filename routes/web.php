<?php

use App\Http\Controllers\ConfigurationItemsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IncidentsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestsController;
use App\Http\Controllers\ResolverPanelController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\UsersController;
use App\Http\Middleware\ResolverPanel;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function (){

    Route::resource('/configuration-items', ConfigurationItemsController::class)->only(['edit']);
    Route::resource('/incidents', IncidentsController::class)->only(['index', 'create', 'edit']);
    Route::resource('/requests', RequestsController::class)->only(['index', 'create', 'edit']);
    Route::resource('/tasks', TasksController::class)->only(['edit']);
    Route::resource('/users', UsersController::class)->only(['edit']);

    Route::middleware(ResolverPanel::class)->group(function (){
        Route::get('/resolver/panel/configuration-items', [ResolverPanelController::class, 'configurationItems'])->name('resolver-panel.configuration-items');
        Route::get('/resolver/panel/incidents', [ResolverPanelController::class, 'incidents'])->name('resolver-panel.incidents');
        Route::get('/resolver/panel/requests', [ResolverPanelController::class, 'requests'])->name('resolver-panel.requests');
        Route::get('/resolver/panel/tasks', [ResolverPanelController::class, 'tasks'])->name('resolver-panel.tasks');
        Route::get('/resolver/panel/users', [ResolverPanelController::class, 'users'])->name('resolver-panel.users');
    });
});

require __DIR__.'/auth.php';
