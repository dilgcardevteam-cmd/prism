<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Communications\Controllers\MessageController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Mobile Communications Endpoints
Route::get('/api/mobile/messages', [MessageController::class, 'mobileIndex'])
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('api.mobile.messages.index');

Route::post('/api/mobile/messages', [MessageController::class, 'store'])
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('api.mobile.messages.store');

Route::post('/api/mobile/messages/typing', function (\Illuminate\Http\Request $request) {
    $authUser = Auth::user();

    if (!$authUser) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    $threadId = (int) ($request->input('thread_id') ?? 0);
    $recipientIds = $request->input('recipient_ids', []);
    if (!is_array($recipientIds)) {
        $recipientIds = [$recipientIds];
    }

    $typing = (bool) $request->input('typing');

    // Only broadcast to provided recipients (server-side will filter invalid ids)
    event(new \App\Modules\Communications\Events\MessageTyping($recipientIds, $threadId, (int) $authUser->idno, $typing));

    return response()->json(['ok' => true]);
})->withoutMiddleware(VerifyCsrfToken::class)->name('api.mobile.messages.typing');

Route::get('/api/mobile/notifications', function (\Illuminate\Http\Request $request) {
    $authUser = Auth::user();
    $userId = $authUser?->idno ?? (int) $request->query('user_id');

    if (!$userId) {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);
    }

    if ($authUser && (int) $authUser->idno !== (int) $userId) {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 403);
    }

    $notifications = \App\Modules\Communications\Support\NotificationCenter::presentMany(DB::table('tbnotifications')
        ->where('user_id', $userId)
        ->orderByDesc('created_at')
        ->orderByDesc('id')
        ->limit(50)
        ->get());

    return response()->json([
        'notifications' => $notifications->values(),
        'queues' => \App\Modules\Communications\Support\NotificationCenter::summarizeQueues($notifications),
        'unread_count' => $notifications->where('is_read', false)->count(),
        'total_count' => $notifications->count(),
    ]);
})->withoutMiddleware(VerifyCsrfToken::class);

// Authenticated Web Communications & Notifications
Route::middleware(['auth'])->group(function () {
    Route::post('/notifications/clear', function () {
        DB::table('tbnotifications')
            ->where('user_id', Auth::id())
            ->whereNotNull('read_at')
            ->where(function ($query) {
                $query->whereNull('document_type')
                    ->orWhere('document_type', '!=', 'bulk-notification');
            })
            ->delete();

        return redirect()->back();
    })->name('notifications.clear');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/{thread}/mark-unread', [MessageController::class, 'markThreadUnread'])->name('messages.mark-unread');
    Route::post('/messages/{thread}/mark-read', [MessageController::class, 'markThreadRead'])->name('messages.mark-read');
    Route::post('/messages/{thread}/delete', [MessageController::class, 'deleteConversation'])->name('messages.delete');
    Route::post('/messages/{thread}/rename-group', [MessageController::class, 'renameGroup'])->name('messages.rename-group');
    Route::get('/messages/poll', [MessageController::class, 'poll'])->name('messages.poll');
    Route::get('/messages/conversation', [MessageController::class, 'conversation'])->name('messages.conversation');
});
