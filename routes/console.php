<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use App\Models\Message;

Schedule::call(function () {
    $expiredMessages = Message::where('is_audio', true)
        ->where('audio_expired', false)
        ->where('created_at', '<', now()->subHours(24))
        ->get();

    foreach ($expiredMessages as $msg) {
        if ($msg->audio_path && Storage::disk('public')->exists($msg->audio_path)) {
            Storage::disk('public')->delete($msg->audio_path);
        }
        $msg->update([
            'audio_expired' => true,
            'audio_path' => null
        ]);
        
        try {
            broadcast(new \App\Events\MessageSent($msg))->toOthers();
        } catch (\Exception $e) {
            \Log::warning("Websocket broadcast failed in cleanup schedule: " . $e->getMessage());
        }
    }
})->hourly();
