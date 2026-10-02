<?php
/**
 * Database Seeder
 * 
 * Run once to create tables and seed default accounts.
 * Usage: php src/config/seed.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/database.php';

use Illuminate\Database\Capsule\Manager as DB;
use Chiku\TimeSheet\Models\User;
use Chiku\TimeSheet\Models\Project;

echo "=== TimeSheet Database Seeder ===\n\n";

// ─── 1. Run the SQL schema ───
echo "[1/3] Creating tables...\n";
$sql = file_get_contents(__DIR__ . '/schema.sql');

// Split by semicolons to execute each statement individually
$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $statement) {
    if (!empty($statement)) {
        DB::connection()->statement($statement);
    }
}
echo "  ✓ Tables created successfully.\n\n";

// ─── 2. Seed default users ───
echo "[2/3] Seeding users...\n";

// Admin account
$admin = User::firstOrCreate(
    ['email' => 'admin@timesheet.local'],
    [
        'name'     => 'Admin User',
        'password' => 'admin123',   // Will be auto-hashed by the model mutator
        'role'     => 'admin',
    ]
);
echo "  ✓ Admin: {$admin->email} (password: admin123)\n";

// Standard user account
$user = User::firstOrCreate(
    ['email' => 'user@timesheet.local'],
    [
        'name'     => 'John Doe',
        'password' => 'user123',    // Will be auto-hashed by the model mutator
        'role'     => 'user',
    ]
);
echo "  ✓ User:  {$user->email} (password: user123)\n\n";

// ─── 3. Seed default projects ───
echo "[3/3] Seeding projects...\n";

$projects = [
    ['name' => 'Internal Work',   'color_hex' => '#6366f1'],
    ['name' => 'Client Project',  'color_hex' => '#f59e0b'],
    ['name' => 'Meeting',         'color_hex' => '#10b981'],
    ['name' => 'Training',        'color_hex' => '#ef4444'],
    ['name' => 'Code Review',     'color_hex' => '#8b5cf6'],
];

foreach ($projects as $proj) {
    $p = Project::firstOrCreate(['name' => $proj['name']], $proj);
    echo "  ✓ Project: {$p->name} ({$p->color_hex})\n";
}

echo "\n=== Seeding Complete ===\n";
