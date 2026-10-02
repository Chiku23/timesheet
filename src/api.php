<?php
/**
 * API Router
 * 
 * Handles all /api/* and /logout endpoints.
 * Returns JSON or file streams and terminates before the HTML layout pipeline.
 */

use Chiku\TimeSheet\Models\User;
use Chiku\TimeSheet\Models\TimeLog;
use Chiku\TimeSheet\Models\Project;
use Chiku\TimeSheet\Models\TimerSession;
use Chiku\TimeSheet\Models\Setting;

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Only handle API routes and /logout
if (!str_starts_with($requestUri, '/api/') && $requestUri !== '/logout') {
    return;
}

/**
 * Format total seconds into a standardized duration string (e.g. "45s", "15m", "1h 30m", "2h").
 */
if (!function_exists('formatDurationSecs')) {
    function formatDurationSecs(int $total): string {
        $total = max(0, $total);
        if ($total === 0) return '0m';
        if ($total < 60) return "{$total}s";
        $hours = intdiv($total, 3600);
        $minutes = intdiv($total % 3600, 60);

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }
}

// ─── GET /logout ───
if ($requestUri === '/logout') {
    session_destroy();
    header('Location: /login');
    exit;
}

// ─── POST /api/login ───
if ($requestUri === '/api/login' && $requestMethod === 'POST') {
    header('Content-Type: application/json');
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }
    
    $user = User::where('email', $email)->first();
    
    if ($user && $user->verifyPassword($password)) {
        $_SESSION['user_id'] = $user->id;
        echo json_encode(['success' => true, 'redirect' => '/']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }
    exit;
}

// ─── POST /api/signup ───
if ($requestUri === '/api/signup' && $requestMethod === 'POST') {
    header('Content-Type: application/json');
    
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || strlen($name) < 2) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid full name.']);
        exit;
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }
    
    if (empty($password) || strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }
    
    if ($password !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }
    
    if (User::where('email', $email)->exists()) {
        echo json_encode(['success' => false, 'message' => 'This email address is already registered.']);
        exit;
    }
    
    $newUser = User::create([
        'name'     => $name,
        'email'    => $email,
        'password' => $password,
        'role'     => 'user',
    ]);
    
    $_SESSION['user_id'] = $newUser->id;
    echo json_encode(['success' => true, 'redirect' => '/']);
    exit;
}

// ─── GET /api/user/profile ───
if ($requestUri === '/api/user/profile' && $requestMethod === 'GET') {
    header('Content-Type: application/json');
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }
    $user = User::find($_SESSION['user_id']);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }
    echo json_encode([
        'success' => true,
        'user' => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ],
    ]);
    exit;
}

// ─── POST /api/user/profile (Update Name, Email, Password) ───
if ($requestUri === '/api/user/profile' && $requestMethod === 'POST') {
    header('Content-Type: application/json');
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }
    
    $user = User::find($_SESSION['user_id']);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $currentPassword = $input['current_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Display name is required.']);
        exit;
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'A valid email address is required.']);
        exit;
    }

    // Check email uniqueness if email changed
    if ($email !== $user->email) {
        $existing = User::where('email', $email)->where('id', '!=', $user->id)->first();
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'This email address is already in use by another account.']);
            exit;
        }
    }

    // Process password change if requested
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            echo json_encode(['success' => false, 'message' => 'Current password is required to set a new password.']);
            exit;
        }
        if (!$user->verifyPassword($currentPassword)) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit;
        }
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long.']);
            exit;
        }
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match.']);
            exit;
        }
        $user->password = $newPassword; // Auto-hashed by model mutator
    }

    $user->name = $name;
    $user->email = $email;
    $user->save();

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully.',
        'user' => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ],
    ]);
    exit;
}

// ─── SERVER-AUTHORITATIVE TIMER ENDPOINTS ───

// GET /api/timer/active — Retrieve current user active session
if ($requestUri === '/api/timer/active' && $requestMethod === 'GET') {
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }

    $session = TimerSession::where('user_id', $_SESSION['user_id'])->first();

    if (!$session) {
        echo json_encode([
            'success' => true,
            'active'  => false,
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'active'  => true,
        'session' => [
            'id'                  => $session->id,
            'status'              => $session->status,
            'task_description'    => $session->task_description,
            'project_id'          => $session->project_id,
            'accumulated_seconds' => $session->accumulated_seconds,
            'current_seconds'     => $session->current_seconds,
            'session_started_at'  => $session->session_started_at->format('Y-m-d\TH:i:s\Z'),
            'segment_started_at'  => $session->segment_started_at ? $session->segment_started_at->format('Y-m-d\TH:i:s\Z') : null,
        ]
    ]);
    exit;
}

// POST /api/timer/start — Start timer session on server
if ($requestUri === '/api/timer/start' && $requestMethod === 'POST') {
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $taskDesc = trim($input['task_description'] ?? '');
    $projectId = !empty($input['project_id']) ? intval($input['project_id']) : null;
    $now = date('Y-m-d H:i:s');

    // Create or reset existing session
    $session = TimerSession::updateOrCreate(
        ['user_id' => $_SESSION['user_id']],
        [
            'project_id'          => $projectId,
            'task_description'    => $taskDesc,
            'status'              => 'running',
            'session_started_at'  => $now,
            'segment_started_at'  => $now,
            'accumulated_seconds' => 0,
        ]
    );

    echo json_encode([
        'success' => true,
        'status'  => 'running',
        'session' => [
            'task_description'    => $session->task_description,
            'project_id'          => $session->project_id,
            'session_started_at'  => $session->session_started_at->format('Y-m-d H:i:s'),
            'current_seconds'     => 0,
        ]
    ]);
    exit;
}

// POST /api/timer/pause — Pause timer on server (calculate segment elapsed time)
if ($requestUri === '/api/timer/pause' && $requestMethod === 'POST') {
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }

    $session = TimerSession::where('user_id', $_SESSION['user_id'])->first();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'No active timer session.']);
        exit;
    }

    if ($session->status === 'running' && $session->segment_started_at) {
        $now = time();
        $segStart = $session->segment_started_at->getTimestamp();
        $serverMaxSegment = max(0, $now - $segStart);

        $clientDuration = isset($input['client_duration_seconds']) ? intval($input['client_duration_seconds']) : null;
        if ($clientDuration !== null && $clientDuration >= $session->accumulated_seconds) {
            $clientSegment = max(0, $clientDuration - $session->accumulated_seconds);
            // Cap segment by server ceiling (allow 3s buffer for network jitter)
            $segmentSeconds = min($clientSegment, $serverMaxSegment + 3);
        } else {
            $segmentSeconds = $serverMaxSegment;
        }

        $session->accumulated_seconds += $segmentSeconds;
        $session->segment_started_at = null;
        $session->status = 'paused';
        $session->save();
    }

    echo json_encode([
        'success'             => true,
        'status'              => 'paused',
        'accumulated_seconds' => $session->accumulated_seconds,
        'current_seconds'     => $session->accumulated_seconds,
    ]);
    exit;
}

// POST /api/timer/resume — Resume timer on server
if ($requestUri === '/api/timer/resume' && $requestMethod === 'POST') {
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }

    $session = TimerSession::where('user_id', $_SESSION['user_id'])->first();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'No active timer session.']);
        exit;
    }

    if ($session->status === 'paused') {
        $session->status = 'running';
        $session->segment_started_at = date('Y-m-d H:i:s');
        $session->save();
    }

    echo json_encode([
        'success'             => true,
        'status'              => 'running',
        'accumulated_seconds' => $session->accumulated_seconds,
        'current_seconds'     => $session->current_seconds,
    ]);
    exit;
}

// POST /api/timer/stop — Finalize session and write log
if ($requestUri === '/api/timer/stop' && $requestMethod === 'POST') {
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $session = TimerSession::where('user_id', $_SESSION['user_id'])->first();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'No active timer session to stop.']);
        exit;
    }

    // Update metadata if user altered task or project in popup
    $taskDesc = !empty($input['task_description']) ? trim($input['task_description']) : $session->task_description;
    $projectId = array_key_exists('project_id', $input ?? []) ? ($input['project_id'] ? intval($input['project_id']) : null) : $session->project_id;

    // Calculate maximum possible duration according to server clock
    $serverMaxSeconds = $session->current_seconds;

    // Validate client duration within server maximum ceiling
    $clientDuration = isset($input['client_duration_seconds']) ? intval($input['client_duration_seconds']) : null;
    if ($clientDuration !== null && $clientDuration > 0) {
        if ($clientDuration <= $serverMaxSeconds + 3) {
            // Valid client click duration (protects against debugger pause / network latency adding extra minutes)
            $finalSeconds = max((int) $session->accumulated_seconds, $clientDuration);
        } else {
            // Client attempted to inflate duration beyond server clock: clamp strictly to server maximum ceiling
            $finalSeconds = $serverMaxSeconds;
        }
    } else {
        $finalSeconds = $serverMaxSeconds;
    }

    if ($finalSeconds < 1) {
        echo json_encode(['success' => false, 'message' => 'Timer duration is too short to log.']);
        exit;
    }

    $startDateTime = $session->session_started_at;
    $startTimeStr = $startDateTime->format('Y-m-d H:i:s');

    // End time is exactly start_time + finalSeconds (prevents debugger pause from pushing end_time into the future)
    $endDateTime = (clone $startDateTime)->modify("+{$finalSeconds} seconds");
    $endTimeStr = $endDateTime->format('Y-m-d H:i:s');

    // Create verified log
    $log = TimeLog::create([
        'user_id'          => $_SESSION['user_id'],
        'project_id'       => $projectId,
        'task_description' => $taskDesc ?: 'Timer session',
        'start_time'       => $startTimeStr,
        'end_time'         => $endTimeStr,
        'duration_seconds' => $finalSeconds,
        'entry_type'       => 'timer',
    ]);

    // Delete active server session
    $session->delete();

    echo json_encode([
        'success'            => true,
        'log_id'             => $log->id,
        'entry_type'         => 'timer',
        'duration_seconds'   => $finalSeconds,
        'formatted_duration' => $log->formatted_duration,
        'start_time'         => $startDateTime->format('Y-m-d\TH:i:s\Z'),
        'end_time'           => $endDateTime->format('Y-m-d\TH:i:s\Z'),
    ]);
    exit;
}

// POST /api/timer/discard — Discard session without saving
if ($requestUri === '/api/timer/discard' && $requestMethod === 'POST') {
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }

    TimerSession::where('user_id', $_SESSION['user_id'])->delete();

    echo json_encode(['success' => true]);
    exit;
}

// ─── POST /api/logs/save (Manual entry fallback) ───
if ($requestUri === '/api/logs/save' && $requestMethod === 'POST') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $taskDesc = trim($input['task_description'] ?? '');
    $startTime = $input['start_time'] ?? null;
    $endTime = $input['end_time'] ?? null;
    $durationSeconds = intval($input['duration_seconds'] ?? 0);
    $projectId = $input['project_id'] ?? null;
    
    if (empty($taskDesc)) {
        $taskDesc = 'Time entry';
    }
    
    if (empty($startTime) || $durationSeconds <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid data. Duration must be greater than 0.']);
        exit;
    }
    
    // Any client-provided duration or manual payload is logged as 'manual' to prevent tampering
    $log = TimeLog::create([
        'user_id'          => $_SESSION['user_id'],
        'project_id'       => $projectId ?: null,
        'task_description' => $taskDesc,
        'start_time'       => $startTime,
        'end_time'         => $endTime,
        'duration_seconds' => $durationSeconds,
        'entry_type'       => 'manual',
    ]);

    // Clear any lingering server timer session
    TimerSession::where('user_id', $_SESSION['user_id'])->delete();
    
    echo json_encode([
        'success'            => true,
        'log_id'             => $log->id,
        'entry_type'         => 'manual',
        'formatted_duration' => $log->formatted_duration
    ]);
    exit;
}

// ─── POST /api/logs/add (Manual log entry) ───
if ($requestUri === '/api/logs/add' && $requestMethod === 'POST') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $taskDesc = trim($input['task_description'] ?? '');
    $startTime = $input['start_time'] ?? null;
    $endTime = $input['end_time'] ?? null;
    $projectId = $input['project_id'] ?? null;
    $durationInput = isset($input['duration_seconds']) ? intval($input['duration_seconds']) : null;
    
    if (empty($taskDesc) || empty($startTime) || empty($endTime)) {
        echo json_encode(['success' => false, 'message' => 'Task description, start time, and end time are required.']);
        exit;
    }
    
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);
    
    if ($end <= $start) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time.']);
        exit;
    }
    
    $durationSeconds = $durationInput !== null && $durationInput > 0 
        ? $durationInput 
        : max(0, $end->getTimestamp() - $start->getTimestamp());
    
    $log = TimeLog::create([
        'user_id'          => $_SESSION['user_id'],
        'project_id'       => $projectId ?: null,
        'task_description' => $taskDesc,
        'start_time'       => $start->format('Y-m-d H:i:s'),
        'end_time'         => $end->format('Y-m-d H:i:s'),
        'duration_seconds' => $durationSeconds,
        'entry_type'       => 'manual',
    ]);
    
    echo json_encode([
        'success'            => true,
        'log_id'             => $log->id,
        'entry_type'         => 'manual',
        'formatted_duration' => $log->formatted_duration
    ]);
    exit;
}

// ─── POST /api/logs/update ───
if ($requestUri === '/api/logs/update' && $requestMethod === 'POST') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $logId = intval($input['id'] ?? 0);
    
    $log = TimeLog::find($logId);
    
    if (!$log) {
        echo json_encode(['success' => false, 'message' => 'Log not found.']);
        exit;
    }
    
    if ($log->user_id !== (int) $_SESSION['user_id'] && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden.']);
        exit;
    }
    
    $taskDesc = trim($input['task_description'] ?? $log->task_description);
    $startTime = $input['start_time'] ?? $log->start_time;
    $endTime = $input['end_time'] ?? $log->end_time;
    $projectId = array_key_exists('project_id', $input) ? $input['project_id'] : $log->project_id;
    
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);
    $durationSeconds = max(0, $end->getTimestamp() - $start->getTimestamp());
    
    $log->update([
        'task_description' => $taskDesc,
        'project_id'       => $projectId ?: null,
        'start_time'       => $start->format('Y-m-d H:i:s'),
        'end_time'         => $end->format('Y-m-d H:i:s'),
        'duration_seconds' => $durationSeconds,
        'is_edited'        => true,
    ]);
    
    echo json_encode(['success' => true, 'formatted_duration' => $log->formatted_duration]);
    exit;
}

// ─── POST /api/logs/delete ───
if ($requestUri === '/api/logs/delete' && $requestMethod === 'POST') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $logId = intval($input['id'] ?? 0);
    
    $log = TimeLog::find($logId);
    
    if (!$log) {
        echo json_encode(['success' => false, 'message' => 'Log not found.']);
        exit;
    }
    
    // Protect wage and work integrity: only the employee who performed the work can delete their own log
    if ($log->user_id !== (int) $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Action restricted: Only the original worker can remove their own time log to protect wage, payroll, and audit integrity.'
        ]);
        exit;
    }
    
    // Soft delete via Eloquent — records deleted_at timestamp without permanently dropping the audit trail
    $log->delete();
    echo json_encode(['success' => true]);
    exit;
}

// ─── GET /api/logs ───
if ($requestUri === '/api/logs' && $requestMethod === 'GET') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }
    
    $query = TimeLog::with(['project', 'user']);
    
    if (isAdmin() && !empty($_GET['user_id'])) {
        $query->where('user_id', intval($_GET['user_id']));
    } elseif (isAdmin() && (!empty($_GET['all']) || ($_GET['scope'] ?? '') === 'all')) {
        // Admin workspace view (e.g. /admin audit list)
    } else {
        $query->where('user_id', $_SESSION['user_id']);
    }
    
    if (!empty($_GET['date_from'])) {
        $query->where('start_time', '>=', $_GET['date_from'] . ' 00:00:00');
    }
    if (!empty($_GET['date_to'])) {
        $query->where('start_time', '<=', $_GET['date_to'] . ' 23:59:59');
    }
    
    if (!empty($_GET['project_id'])) {
        $query->where('project_id', intval($_GET['project_id']));
    }

    if (!empty($_GET['entry_type']) && in_array($_GET['entry_type'], ['timer', 'manual'])) {
        $query->where('entry_type', $_GET['entry_type']);
    }
    
    $logs = $query->orderBy('start_time', 'desc')->get();
    
    $result = $logs->map(function ($log) {
        return [
            'id'                 => $log->id,
            'user_id'            => $log->user_id,
            'user_name'          => $log->user ? $log->user->name : 'Unknown User',
            'project_id'         => $log->project_id,
            'project_name'       => $log->project ? $log->project->name : null,
            'project_color'      => $log->project ? $log->project->color_hex : null,
            'task_description'   => $log->task_description,
            'start_time'         => $log->start_time->format('Y-m-d\TH:i:s\Z'),
            'end_time'           => $log->end_time ? $log->end_time->format('Y-m-d\TH:i:s\Z') : null,
            'duration_seconds'   => $log->duration_seconds,
            'formatted_duration' => $log->formatted_duration,
            'entry_type'         => $log->entry_type ?? 'manual',
            'is_edited'          => (bool) ($log->is_edited ?? false),
            'date'               => $log->start_time->format('Y-m-d'),
        ];
    });
    
    echo json_encode(['success' => true, 'logs' => $result->values()]);
    exit;
}

// ─── GET /api/projects ───
if ($requestUri === '/api/projects' && $requestMethod === 'GET') {
    header('Content-Type: application/json');
    
    $projects = Project::orderBy('name')->get(['id', 'name', 'color_hex'])->map(function ($p) {
        $secs = TimeLog::where('project_id', $p->id)->sum('duration_seconds') ?? 0;
        return [
            'id'                 => $p->id,
            'name'               => $p->name,
            'color_hex'          => $p->color_hex,
            'total_seconds'      => $secs,
            'formatted_duration' => formatDurationSecs($secs),
        ];
    });

    echo json_encode(['success' => true, 'projects' => $projects]);
    exit;
}

// ─── POST /api/projects/add ───
if ($requestUri === '/api/projects/add' && $requestMethod === 'POST') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn() || !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin privileges required.']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $color = trim($input['color_hex'] ?? '#4f46e5');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Project name is required.']);
        exit;
    }
    
    $project = Project::create([
        'name'      => $name,
        'color_hex' => $color ?: '#4f46e5',
    ]);
    
    echo json_encode([
        'success' => true,
        'project' => [
            'id'                 => $project->id,
            'name'               => $project->name,
            'color_hex'          => $project->color_hex,
            'total_seconds'      => 0,
            'formatted_duration' => '0m',
        ]
    ]);
    exit;
}

// ─── POST /api/projects/update ───
if ($requestUri === '/api/projects/update' && $requestMethod === 'POST') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn() || !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin privileges required.']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $color = trim($input['color_hex'] ?? '#4f46e5');

    $project = Project::find($id);
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found.']);
        exit;
    }

    if (!empty($name)) $project->name = $name;
    if (!empty($color)) $project->color_hex = $color;
    $project->save();

    echo json_encode(['success' => true, 'project' => $project]);
    exit;
}

// ─── POST /api/projects/delete ───
if ($requestUri === '/api/projects/delete' && $requestMethod === 'POST') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn() || !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin privileges required.']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);

    $project = Project::find($id);
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found.']);
        exit;
    }

    $project->delete();
    echo json_encode(['success' => true]);
    exit;
}

// ─── GET /api/settings ───
if ($requestUri === '/api/settings' && $requestMethod === 'GET') {
    header('Content-Type: application/json');

    $weeklyGoal = intval(Setting::get('weekly_goal_hours', 40));
    $companyName = Setting::get('company_name', 'TimeSheet Workspace');

    echo json_encode([
        'success'           => true,
        'weekly_goal_hours' => $weeklyGoal,
        'company_name'      => $companyName,
    ]);
    exit;
}

// ─── POST /api/settings/update ───
if ($requestUri === '/api/settings/update' && $requestMethod === 'POST') {
    header('Content-Type: application/json');

    if (!isLoggedIn() || !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin privileges required.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['weekly_goal_hours'])) {
        $goal = max(1, min(168, intval($input['weekly_goal_hours'])));
        Setting::set('weekly_goal_hours', $goal);
    }

    if (isset($input['company_name'])) {
        $company = trim($input['company_name']);
        if (!empty($company)) Setting::set('company_name', $company);
    }

    echo json_encode([
        'success'           => true,
        'weekly_goal_hours' => intval(Setting::get('weekly_goal_hours', 40)),
        'company_name'      => Setting::get('company_name', 'TimeSheet Workspace'),
    ]);
    exit;
}

// ─── GET /api/admin/stats ───
if ($requestUri === '/api/admin/stats' && $requestMethod === 'GET') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn() || !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden.']);
        exit;
    }
    
    $totalUsers = User::count();
    $totalProjects = Project::count();
    $totalLogs = TimeLog::count();
    $totalSeconds = TimeLog::sum('duration_seconds') ?? 0;
    
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekSeconds = TimeLog::where('start_time', '>=', $weekStart . ' 00:00:00')->sum('duration_seconds') ?? 0;
    
    $activeUsers = TimeLog::where('start_time', '>=', $weekStart . ' 00:00:00')
        ->distinct('user_id')
        ->count('user_id');
    
    $timerCount = TimeLog::where('entry_type', 'timer')->count();
    $manualCount = TimeLog::where('entry_type', 'manual')->count();
    
    $users = User::all()->map(function ($u) {
        $totalSecs = TimeLog::where('user_id', $u->id)->sum('duration_seconds') ?? 0;
        $logCount = TimeLog::where('user_id', $u->id)->count();
        return [
            'id'                    => $u->id,
            'name'                  => $u->name,
            'email'                 => $u->email,
            'role'                  => $u->role,
            'total_seconds'         => $totalSecs,
            'total_hours'           => round($totalSecs / 3600, 1),
            'formatted_total_hours' => formatDurationSecs($totalSecs),
            'log_count'             => $logCount,
        ];
    });
    
    echo json_encode([
        'success'               => true,
        'total_users'           => $totalUsers,
        'total_projects'        => $totalProjects,
        'total_logs'            => $totalLogs,
        'total_seconds'         => $totalSeconds,
        'total_hours'           => round($totalSeconds / 3600, 1),
        'formatted_total_hours' => formatDurationSecs($totalSeconds),
        'week_seconds'          => $weekSeconds,
        'week_hours'            => round($weekSeconds / 3600, 1),
        'formatted_week_hours'  => formatDurationSecs($weekSeconds),
        'active_users'          => $activeUsers,
        'timer_count'           => $timerCount,
        'manual_count'          => $manualCount,
        'users'                 => $users,
    ]);
    exit;
}

// ─── GET /api/reports ───
if ($requestUri === '/api/reports' && $requestMethod === 'GET') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }
    
    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    
    $query = TimeLog::with(['project', 'user'])
        ->where('start_time', '>=', $dateFrom . ' 00:00:00')
        ->where('start_time', '<=', $dateTo . ' 23:59:59');
    
    if (!isAdmin()) {
        $query->where('user_id', $_SESSION['user_id']);
    } elseif (!empty($_GET['user_id'])) {
        $query->where('user_id', intval($_GET['user_id']));
    }
    
    if (!empty($_GET['entry_type']) && in_array($_GET['entry_type'], ['timer', 'manual'])) {
        $query->where('entry_type', $_GET['entry_type']);
    }
    
    $logs = $query->orderBy('start_time', 'asc')->get();
    
    $dailyTotals = [];
    foreach ($logs as $log) {
        $day = $log->start_time->format('Y-m-d');
        if (!isset($dailyTotals[$day])) {
            $dailyTotals[$day] = ['seconds' => 0, 'formatted' => '0m'];
        }
        $dailyTotals[$day]['seconds'] += $log->duration_seconds;
        $dailyTotals[$day]['formatted'] = formatDurationSecs($dailyTotals[$day]['seconds']);
    }
    
    $projectTotals = [];
    foreach ($logs as $log) {
        $pName = $log->project ? $log->project->name : 'No Project';
        $pColor = $log->project ? $log->project->color_hex : '#6b7280';
        if (!isset($projectTotals[$pName])) {
            $projectTotals[$pName] = ['seconds' => 0, 'color' => $pColor, 'formatted' => '0m'];
        }
        $projectTotals[$pName]['seconds'] += $log->duration_seconds;
        $projectTotals[$pName]['formatted'] = formatDurationSecs($projectTotals[$pName]['seconds']);
    }
    
    $totalSeconds = $logs->sum('duration_seconds') ?? 0;
    $timerSeconds = $logs->where('entry_type', 'timer')->sum('duration_seconds') ?? 0;
    $manualSeconds = $logs->where('entry_type', 'manual')->sum('duration_seconds') ?? 0;
    
    $logRows = $logs->map(function ($log) {
        return [
            'id'                 => $log->id,
            'date'               => $log->start_time->format('Y-m-d'),
            'start_time'         => $log->start_time->format('Y-m-d\TH:i:s\Z'),
            'end_time'           => $log->end_time ? $log->end_time->format('Y-m-d\TH:i:s\Z') : null,
            'user_name'          => $log->user ? $log->user->name : 'Unknown User',
            'task_description'   => $log->task_description,
            'project_name'       => $log->project ? $log->project->name : 'No Project',
            'project_color'      => $log->project ? $log->project->color_hex : '#6b7280',
            'entry_type'         => $log->entry_type ?? 'manual',
            'is_edited'          => (bool) ($log->is_edited ?? false),
            'duration_seconds'   => $log->duration_seconds,
            'formatted_duration' => $log->formatted_duration,
        ];
    });
    
    echo json_encode([
        'success'               => true,
        'date_from'             => $dateFrom,
        'date_to'               => $dateTo,
        'total_seconds'         => $totalSeconds,
        'total_hours'           => round($totalSeconds / 3600, 2),
        'formatted_total_hours' => formatDurationSecs($totalSeconds),
        'timer_seconds'         => $timerSeconds,
        'manual_seconds'        => $manualSeconds,
        'formatted_timer'       => formatDurationSecs($timerSeconds),
        'formatted_manual'      => formatDurationSecs($manualSeconds),
        'daily_totals'          => $dailyTotals,
        'project_totals'        => $projectTotals,
        'logs'                  => $logRows->values(),
    ]);
    exit;
}

// ─── GET /api/export/csv ───
if ($requestUri === '/api/export/csv' && $requestMethod === 'GET') {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo 'Not authenticated.';
        exit;
    }
    
    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    
    $query = TimeLog::with(['project', 'user'])
        ->where('start_time', '>=', $dateFrom . ' 00:00:00')
        ->where('start_time', '<=', $dateTo . ' 23:59:59');
    
    if (!isAdmin()) {
        $query->where('user_id', $_SESSION['user_id']);
    } elseif (!empty($_GET['user_id'])) {
        $query->where('user_id', intval($_GET['user_id']));
    }
    
    if (!empty($_GET['entry_type']) && in_array($_GET['entry_type'], ['timer', 'manual'])) {
        $query->where('entry_type', $_GET['entry_type']);
    }
    
    $logs = $query->orderBy('start_time', 'asc')->get();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="timesheet_' . $dateFrom . '_to_' . $dateTo . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Date', 'User', 'Project', 'Task', 'Type', 'Start Time', 'End Time', 'Duration', 'Duration (Seconds)']);
    
    foreach ($logs as $log) {
        fputcsv($output, [
            $log->start_time->format('Y-m-d'),
            $log->user ? $log->user->name : 'Unknown',
            $log->project ? $log->project->name : 'No Project',
            $log->task_description,
            ucfirst($log->entry_type ?? 'manual'),
            $log->start_time->format('H:i:s'),
            $log->end_time ? $log->end_time->format('H:i:s') : '-',
            $log->formatted_duration,
            $log->duration_seconds,
        ]);
    }
    
    fclose($output);
    exit;
}
