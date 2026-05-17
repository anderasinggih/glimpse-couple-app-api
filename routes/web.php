<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Couple;
use App\Models\Message;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/privacy', function () {
    return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Glimpse</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0b071e;
            color: #ffffff;
            font-family: "Outfit", sans-serif;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #a855f7 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 40px;
            font-size: 1.1rem;
        }
        h2 {
            font-size: 1.4rem;
            color: #06b6d4;
            margin-top: 30px;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 8px;
        }
        p, li {
            font-size: 1rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.85);
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin-bottom: 8px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Glimpse Privacy Policy</h1>
        <p class="subtitle">Effective Date: May 17, 2026</p>
        
        <p>Welcome to Glimpse, an intimate companion application designed to keep couples connected. Your privacy is of paramount importance to us. This Privacy Policy describes how Glimpse collects, uses, processes, and protects your information.</p>
        
        <h2>1. Information We Collect</h2>
        <p>To provide a seamless real-time connection experience, Glimpse collects the following categories of information:</p>
        <ul>
            <li><strong>Account Data:</strong> Your name, email address, password, and profile photo when you sign up.</li>
            <li><strong>Intimate Connection State:</strong> Location coordinates (latitude and longitude), battery status, charging state, status notes, and photos you capture for your Flash memory feed.</li>
            <li><strong>Direct Messaging:</strong> Chat messages exchanged between you and your linked partner. Messages are transmitted securely to deliver them in real-time.</li>
        </ul>
        
        <h2>2. How We Use Your Information</h2>
        <p>Glimpse uses the collected information strictly for the following purposes:</p>
        <ul>
            <li>To display your real-time presence (location, battery status, and status note) to your linked partner.</li>
            <li>To share direct chat messages and Flash photos exclusively with your connected partner.</li>
            <li>To host and build your shared memory timeline.</li>
        </ul>
        
        <h2>3. Data Sharing & Security</h2>
        <p><strong>We do not sell, trade, or share your personal data with any third-party advertisers or corporations.</strong> Your data is strictly shared with the individual you choose to pair with using your unique connection invite code.</p>
        
        <h2>4. Data Retention & Control</h2>
        <p>You have full control over your shared intimacy data. You can disconnect from your partner, delete past messages, or request account deletion directly from the settings menu within the application at any time.</p>
        
        <div class="footer">
            &copy; 2026 Glimpse App. Crafted with love.
        </div>
    </div>
</body>
</html>
';
});

Route::get('/debug-storage', function () {
    $avatarsDir = storage_path('app/public/avatars');
    $files = file_exists($avatarsDir) ? scandir($avatarsDir) : [];
    
    $users = \App\Models\User::select('id', 'name', 'profile_photo_url')->get()->toArray();
    
    return response()->json([
        'avatars_directory_exists' => file_exists($avatarsDir),
        'avatars_directory_path' => $avatarsDir,
        'files_in_avatars' => $files,
        'users_in_database' => $users,
        'symlink_public_storage_exists' => file_exists(public_path('storage')),
    ]);
});

Route::post('/admin/api', function (Request $request) {
    // 1. Verify access token
    $token = $request->header('X-Admin-Token') ?: $request->input('token');
    $secretToken = config('app.admin_token') ?: env('ADMIN_TOKEN');

    if (!$secretToken || !$token || $token !== $secretToken) {
        return response()->json(['error' => 'Unauthorized. Invalid Admin Token.'], 401);
    }

    $action = $request->input('action');

    switch ($action) {
        case 'get_data':
            $users = User::all();
            $couples = Couple::with('users')->get();
            $messagesCount = Message::count();
            
            // Format nice couple listings
            $couplesFormatted = $couples->map(function ($couple) {
                return [
                    'id' => $couple->id,
                    'anniversary_start_date' => $couple->anniversary_start_date,
                    'is_active' => (bool)$couple->is_active,
                    'users' => $couple->users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
                ];
            });

            return response()->json([
                'stats' => [
                    'total_users' => $users->count(),
                    'total_couples' => $couples->count(),
                    'total_messages' => $messagesCount,
                    'active_sessions' => User::whereNotNull('updated_at')->count(),
                ],
                'users' => $users,
                'couples' => $couplesFormatted,
            ]);

        case 'update_battery':
            $userId = $request->input('user_id');
            $battery = (int)$request->input('battery_level');
            $user = User::findOrFail($userId);
            $user->battery_level = $battery;
            $user->save();

            // Broadcast state update event
            event(new \App\Events\PartnerStateUpdated($user->couple_id, $user->id, 'battery_update'));

            return response()->json(['success' => true, 'message' => "Battery updated successfully for {$user->name}."]);

        case 'update_location':
            $userId = $request->input('user_id');
            $lat = (double)$request->input('latitude');
            $lon = (double)$request->input('longitude');
            $locName = $request->input('location_name');

            $user = User::findOrFail($userId);
            $user->latitude = $lat;
            $user->longitude = $lon;
            $user->location_name = $locName;
            $user->save();

            // Broadcast state update event
            event(new \App\Events\PartnerStateUpdated($user->couple_id, $user->id, 'location_update'));

            return response()->json(['success' => true, 'message' => "Location updated successfully for {$user->name}."]);

        case 'clear_chat':
            $coupleId = $request->input('couple_id');
            Message::where('couple_id', $coupleId)->delete();
            return response()->json(['success' => true, 'message' => 'Chat history cleared successfully.']);

        case 'delete_user':
            $userId = $request->input('user_id');
            $user = User::findOrFail($userId);
            $user->delete();
            return response()->json(['success' => true, 'message' => "User deleted successfully."]);

        default:
            return response()->json(['error' => 'Unknown action.'], 400);
    }
});

// Fallback route to serve storage files directly, bypassing cPanel symbolic link block restrictions
Route::get('/storage/{directory}/{filename}', function ($directory, $filename) {
    $path = storage_path('app/public/' . $directory . '/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return response($file, 200)->header("Content-Type", $type);
});

// Diagnostic route to read laravel logs
Route::get('/view-logs', function () {
    $logPath = storage_path('logs/laravel.log');
    if (!file_exists($logPath)) {
        return response()->json(['message' => 'No logs found.']);
    }
    $lines = file($logPath);
    $lastLines = array_slice($lines, -100);
    return response(implode("", $lastLines), 200)->header('Content-Type', 'text/plain');
});
