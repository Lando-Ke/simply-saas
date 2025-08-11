<?php

/**
 * Script to create a new App Admin user
 * Run with: php create_app_admin.php
 */

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Get user input
echo "=== Create New App Admin User ===\n";
echo "Name: ";
$name = trim(fgets(STDIN));

echo "Email: ";
$email = trim(fgets(STDIN));

echo "Password (leave empty for 'password'): ";
$password = trim(fgets(STDIN));
if (empty($password)) {
    $password = 'password';
}

// Create the user
try {
    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
        'email_verified_at' => now(),
        'hourly_rate' => 100.00,
    ]);

    // Assign app-admin role
    $user->assignRole('app-admin');

    echo "\n✅ App Admin user created successfully!\n";
    echo "Email: {$user->email}\n";
    echo "Password: {$password}\n";
    echo "Role: app-admin\n";
    echo "\nThey can now access the Filament dashboard at /admin\n";

} catch (Exception $e) {
    echo "\n❌ Error creating user: " . $e->getMessage() . "\n";
}
