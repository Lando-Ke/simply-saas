<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('create-organization', 'tenant-registration')
    ->middleware('guest')
    ->name('tenant.register');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'permission:view dashboard'])
    ->name('dashboard');

Route::view('projects', 'projects')
    ->middleware(['auth', 'verified', 'permission:view projects'])
    ->name('projects');

Route::view('billing', 'billing')
    ->middleware(['auth', 'verified', 'permission:view subscriptions,manage billing'])
    ->name('billing');

Route::view('tasks', 'tasks')
    ->middleware(['auth', 'verified', 'permission:view tasks'])
    ->name('tasks');

Route::view('branding', 'branding')
    ->middleware(['auth', 'verified', 'permission:manage branding'])
    ->name('branding');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
