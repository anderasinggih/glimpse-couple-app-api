<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Couple;
use App\Models\Message;

Route::get('/', function () {
    return view('welcome');
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
    $secretToken = env('ADMIN_TOKEN');

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
