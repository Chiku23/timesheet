<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'port'      => '3306',
    'database'  => 'timesheet',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

// Make this Capsule instance available globally via static methods
$capsule->setAsGlobal();

// Boot Eloquent ORM
$capsule->bootEloquent();

// Auto-migrate schema updates safely
try {
    if ($capsule->schema()->hasTable('time_logs') && !$capsule->schema()->hasColumn('time_logs', 'entry_type')) {
        $capsule->schema()->table('time_logs', function ($table) {
            $table->string('entry_type', 20)->default('manual')->after('duration_seconds');
        });
    }

    // Active server-side timer sessions table
    if (!$capsule->schema()->hasTable('timer_sessions')) {
        $capsule->schema()->create('timer_sessions', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('task_description', 255)->default('');
            $table->enum('status', ['running', 'paused'])->default('running');
            $table->dateTime('session_started_at');
            $table->dateTime('segment_started_at')->nullable();
            $table->unsignedInteger('accumulated_seconds')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
        });
    }

    // System configuration and admin settings table
    if (!$capsule->schema()->hasTable('settings')) {
        $capsule->schema()->create('settings', function ($table) {
            $table->string('key', 50)->primary();
            $table->text('value');
            $table->timestamp('updated_at')->useCurrent();
        });

        // Insert default settings
        $capsule->table('settings')->insertOrIgnore([
            ['key' => 'weekly_goal_hours', 'value' => '40'],
            ['key' => 'company_name', 'value' => 'TimeSheet Workspace'],
        ]);
    }
} catch (\Throwable $e) {
    // In case DB tables are not created yet
}
