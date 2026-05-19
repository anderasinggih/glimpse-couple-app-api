<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Models\User;

Artisan::command('glimpse:prune-history', function () {
    $this->info('🧼 Starting location history pruning...');
    $twentyFourHoursAgo = now()->timestamp - 86400;
    
    User::chunk(100, function ($users) use ($twentyFourHoursAgo) {
        foreach ($users as $user) {
            $history = $user->location_history ?? [];
            if (empty($history)) continue;
            
            $filtered = array_filter($history, function ($entry) use ($twentyFourHoursAgo) {
                return isset($entry['timestamp']) && $entry['timestamp'] >= $twentyFourHoursAgo;
            });
            $filtered = array_values($filtered);
            
            if (count($filtered) !== count($history)) {
                $user->location_history = $filtered;
                $user->save();
            }
        }
    });
    
    $this->info('✨ Location history pruned successfully!');
})->purpose('Prune location history older than 24 hours');

Schedule::command('glimpse:prune-history')->daily();

