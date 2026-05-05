<?php

namespace App\Http\Controllers;

use App\Events\MessageThreadUpdated;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        if (!$this->hasMessagingSchema()) {
            return view('messages.index', [
                'threads' => new LengthAwarePaginator([], 0, 20),
                'conversation' => collect(),
                'selectedUser' => null,
                'selectedGroupMembers' => collect(),
                'selectedThreadId' => 0,
                'unreadMessages' => 0,
                'availableUsers' => $this->availableUsers(),
            ]);
        }

        $authId = (int) Auth::id();
        $threadId = (int) $request->integer('thread');
        $preserveUnreadThreadId = (int) session('preserve_unread_thread', 0);
        $supportsManualUnread = $this->hasManualUnreadColumn();

        $latestByThread = DB::table('user_messages')
            ->selectRaw('thread_id, MAX(created_at) as latest_created_at, MAX(id) as latest_message_id')
            ->where('recipient_id', $authId)
            ->whereNotNull('thread_id')
            ->groupBy('thread_id');

        $threadRowsQuery = DB::table('message_thread_members as member')
            ->join('message_threads as thread', 'thread.id', '=', 'member.thread_id')
            ->joinSub($latestByThread, 'latest', function ($join) {
                $join->on('latest.thread_id', '=', 'thread.id');
            })
            ->where('member.user_id', $authId)
            ->select([
                'thread.id as thread_id',
                'thread.name as thread_name',
                'thread.is_group',
                'latest.latest_created_at',
                'latest.latest_message_id',
            ]);

        if ($supportsManualUnread) {
            $threadRowsQuery->addSelect('member.manual_unread_at');
        }

        $threadRows = $threadRowsQuery
            ->orderByDesc('latest_created_at')
            ->orderByDesc('latest_message_id')
            ->get();

        $latestIds = $threadRows->pluck('latest_message_id')->filter()->map(fn ($id) => (int) $id)->values();
        $threadIds = $threadRows->pluck('thread_id')->filter()->map(fn ($id) => (int) $id)->values();

        $latestMessages = $latestIds->isEmpty()
            ? collect()
            : DB::table('user_messages')->whereIn('id', $latestIds)->get()->keyBy('id');

        $threadMembersRaw = $threadIds->isEmpty()
            ? collect()
            : DB::table('message_thread_members as member')
                ->join('tbusers as user', 'user.idno', '=', 'member.user_id')
                ->whereIn('member.thread_id', $threadIds)
                ->select([
                    'member.thread_id',
                    'user.idno',
                    'user.fname',
                    'user.lname',
                    'user.position',
                    'user.office',
                    'user.province',
                    'user.region',
                ])
                ->get();

        $membersByThread = $threadMembersRaw->groupBy('thread_id');

        $unreadByThread = DB::table('user_messages')
            ->select('thread_id', DB::raw('COUNT(*) as unread_count'))
            ->where('recipient_id', $authId)
            ->where('sender_id', '!=', $authId)
            ->whereNull('read_at')
            ->whereNotNull('thread_id')
            ->groupBy('thread_id')
            ->pluck('unread_count', 'thread_id');

        $threadsCollection = $threadRows->map(function ($row) use ($authId, $latestMessages, $membersByThread, $unreadByThread, $supportsManualUnread) {
            $latest = $latestMessages->get((int) $row->latest_message_id);
            $threadId = (int) ($row->thread_id ?? 0);
            if (!$latest || $threadId <= 0) {
                return null;
            }

            $members = collect($membersByThread->get($threadId, collect()));
            $counterparts = $members->filter(fn ($member) => (int) ($member->idno ?? 0) !== $authId)->values();

            $isGroup = (bool) ($row->is_group ?? false) || $counterparts->count() > 1;
            $name = trim((string) ($row->thread_name ?? ''));
            $subtitle = '';

            if ($isGroup) {
                $avatarMembers = $counterparts->map(function ($member) {
                    $memberName = trim((string) (($member->fname ?? '') . ' ' . ($member->lname ?? '')));

                    return $memberName !== '' ? $memberName : 'Unknown User';
                })->sortBy(fn ($memberName) => mb_strtolower((string) $memberName))->values();

                if ($name === '') {
                    $names = $avatarMembers;

                    $name = $names->take(2)->implode(', ');
                    $remaining = max(0, $names->count() - 2);
                    if ($remaining > 0) {
                        $name .= ' +' . $remaining;
                    }
                }

                if ($name === '') {
                    $name = 'Group chat';
                }
                $subtitle = max(2, $members->count()) . ' participants';
            } else {
                $counterpart = $counterparts->first();
                if (!$counterpart) {
                    return null;
                }

                $name = trim((string) (($counterpart->fname ?? '') . ' ' . ($counterpart->lname ?? '')));
                $name = $name !== '' ? $name : 'Unknown User';
                $subtitle = trim((string) ($counterpart->position ?: $counterpart->office ?: 'PDMU User'));
            }

            $latestSenderId = (int) ($latest->sender_id ?? 0);
            $latestSender = $members->first(fn ($member) => (int) ($member->idno ?? 0) === $latestSenderId);
            $latestSenderName = '';
            if ($latestSender) {
                $latestSenderName = trim((string) (($latestSender->fname ?? '') . ' ' . ($latestSender->lname ?? '')));
                $latestSenderName = $latestSenderName !== '' ? $latestSenderName : 'Unknown User';
            }

            $isMine = $latestSenderId === $authId;
            $actualUnreadCount = (int) ($unreadByThread[$threadId] ?? 0);
            $manualUnread = $supportsManualUnread && !empty($row->manual_unread_at);
            $threadUnreadCount = $actualUnreadCount > 0 ? $actualUnreadCount : ($manualUnread ? 1 : 0);

            return [
                'thread_id' => $threadId,
                'name' => $name,
                'subtitle' => $subtitle,
                'is_group' => $isGroup,
                'avatar_members' => $isGroup ? ($avatarMembers ?? collect())->values()->all() : [],
                'preview' => $this->messagePreviewText($latest->message ?? '', $latest->image_path ?? null, 72),
                'preview_sender' => $latestSenderName,
                'preview_is_mine' => $isMine,
                'preview_show_unread_label' => !$isMine && $actualUnreadCount > 0,
                'time' => $this->formatMessageTimestamp($latest->created_at ?? null),
                'latest_at' => $latest->created_at,
                'unread' => $threadUnreadCount,
            ];
        })->filter()->values();

        if ($threadId <= 0 && $threadsCollection->isNotEmpty()) {
            $threadId = (int) ($threadsCollection->first()['thread_id'] ?? 0);
        }

        $conversation = collect();
        $selectedUser = null;
        $selectedGroupMembers = collect();
        if ($threadId > 0) {
            $threadExists = DB::table('message_thread_members')
                ->where('thread_id', $threadId)
                ->where('user_id', $authId)
                ->exists();

            if ($threadExists) {
                $thread = DB::table('message_threads')->where('id', $threadId)->first();
                $members = DB::table('message_thread_members as member')
                    ->join('tbusers as user', 'user.idno', '=', 'member.user_id')
                    ->where('member.thread_id', $threadId)
                    ->select(['user.idno', 'user.fname', 'user.lname', 'user.position', 'user.office', 'user.province', 'user.region'])
                    ->get();

                $counterparts = $members->filter(fn ($member) => (int) ($member->idno ?? 0) !== $authId)->values();
                $isGroup = (bool) ($thread->is_group ?? false) || $counterparts->count() > 1;

                if ($isGroup) {
                    $threadName = trim((string) ($thread->name ?? ''));
                    if ($threadName === '') {
                        $threadName = $counterparts
                            ->map(function ($member) {
                                $memberName = trim((string) (($member->fname ?? '') . ' ' . ($member->lname ?? '')));
                                return $memberName !== '' ? $memberName : 'Unknown User';
                            })
                            ->take(3)
                            ->implode(', ');
                    }

                    $selectedGroupMembers = $members
                        ->map(function ($member) use ($authId) {
                            $name = trim((string) (($member->fname ?? '') . ' ' . ($member->lname ?? '')));

                            return (object) [
                                'idno' => (int) ($member->idno ?? 0),
                                'name' => $name !== '' ? $name : 'Unknown User',
                                'position' => trim((string) ($member->position ?? '')),
                                'office' => trim((string) ($member->office ?? '')),
                                'province' => trim((string) ($member->province ?? '')),
                                'region' => trim((string) ($member->region ?? '')),
                                'is_me' => (int) ($member->idno ?? 0) === $authId,
                            ];
                        })
                        ->sortBy(fn ($member) => mb_strtolower((string) $member->name))
                        ->values();

                    $selectedUser = (object) [
                        'idno' => $threadId,
                        'fname' => $threadName !== '' ? $threadName : 'Group chat',
                        'lname' => '',
                        'custom_name' => trim((string) ($thread->name ?? '')),
                        'position' => 'Group chat',
                        'office' => max(2, $members->count()) . ' participants',
                    ];
                } else {
                    $counterpart = $counterparts->first();
                    if ($counterpart) {
                        $selectedUser = (object) [
                            'idno' => $threadId,
                            'fname' => $counterpart->fname,
                            'lname' => $counterpart->lname,
                            'position' => $counterpart->position,
                            'office' => $counterpart->office,
                            'province' => $counterpart->province,
                            'region' => $counterpart->region,
                        ];
                    }
                }

                $conversation = DB::table('user_messages')
                    ->where('thread_id', $threadId)
                    ->where('recipient_id', $authId)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->limit(150)
                    ->get();

                if ($preserveUnreadThreadId !== $threadId) {
                    DB::table('user_messages')
                        ->where('thread_id', $threadId)
                        ->where('recipient_id', $authId)
                        ->where('sender_id', '!=', $authId)
                        ->whereNull('read_at')
                        ->update([
                            'read_at' => now(),
                            'updated_at' => now(),
                        ]);

                    if ($supportsManualUnread) {
                        DB::table('message_thread_members')
                            ->where('thread_id', $threadId)
                            ->where('user_id', $authId)
                            ->whereNotNull('manual_unread_at')
                            ->update([
                                'manual_unread_at' => null,
                                'updated_at' => now(),
                            ]);
                    }

                    $threadsCollection = $threadsCollection->map(function ($threadItem) use ($threadId) {
                        if ((int) ($threadItem['thread_id'] ?? 0) !== $threadId) {
                            return $threadItem;
                        }

                        $threadItem['unread'] = 0;
                        $threadItem['preview_show_unread_label'] = false;

                        return $threadItem;
                    })->values();
                }
            }
        }

        $threads = $this->paginateCollection($threadsCollection, 20, $request);

        return view('messages.index', [
            'threads' => $threads,
            'conversation' => $conversation,
            'conversationGroups' => $this->groupConversationEntries($conversation, $authId),
            'selectedUser' => $selectedUser,
            'selectedGroupMembers' => $selectedGroupMembers,
            'selectedThreadId' => $threadId,
            'latestGlobalMessageId' => (int) (DB::table('user_messages')
                ->where('recipient_id', $authId)
                ->max('id') ?? 0),
            'unreadMessages' => $this->unreadThreadCountForUser($authId),
            'availableUsers' => $this->availableUsers(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if (!$this->hasMessagingSchema()) {
            return redirect()->route('messages.index')
                ->with('error', 'Messaging is not configured yet. Run migrations first.');
        }

        $authUser = Auth::user();
        $authId = (int) ($authUser?->idno ?? 0);
        $wantsJson = $request->expectsJson() || $request->ajax();

        if ($authId <= 0 && $wantsJson) {
            $authId = (int) $request->integer('user_id');
        }

        if ($authUser && $authId > 0 && (int) $authUser->idno !== $authId) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($authId <= 0) {
            if ($wantsJson) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->route('login');
        }

        $supportsMessageImages = $this->hasMessageImageColumns();

        $validated = $request->validate([
            'thread_id' => ['nullable', 'integer'],
            'recipient_ids' => ['nullable', 'array', 'min:1'],
            'recipient_ids.*' => [
                'integer',
                'different:' . $authId,
                Rule::exists('tbusers', 'idno'),
            ],
            'message' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'max:5120'],
        ], [
            'image.image' => 'Only image files can be uploaded.',
            'image.max' => 'Images must be 5 MB or smaller.',
            'images.max' => 'You can upload up to 10 images at a time.',
            'images.*.image' => 'Only image files can be uploaded.',
            'images.*.max' => 'Images must be 5 MB or smaller.',
        ]);

        $uploadedImages = collect();
        if ($request->hasFile('image')) {
            $uploadedImages->push($request->file('image'));
        }
        if ($request->hasFile('images')) {
            $uploadedImages = $uploadedImages
                ->merge(collect($request->file('images')))
                ->filter(fn ($file) => $file instanceof UploadedFile)
                ->values();
        }

        if ($uploadedImages->isNotEmpty() && !$supportsMessageImages) {
            $errorMessage = 'Image uploads are not ready yet. Run the latest migrations first.';

            if ($wantsJson) {
                return response()->json([
                    'message' => $errorMessage,
                    'errors' => [
                        'image' => [$errorMessage],
                        'images' => [$errorMessage],
                    ],
                ], 422);
            }

            return redirect()->route('messages.index')
                ->with('error', $errorMessage);
        }

        $threadId = (int) ($validated['thread_id'] ?? 0);
        $memberIds = collect();

        if ($threadId > 0) {
            $isMember = DB::table('message_thread_members')
                ->where('thread_id', $threadId)
                ->where('user_id', $authId)
                ->exists();

            if (!$isMember) {
                return redirect()->route('messages.index')->with('error', 'You are not allowed to post in this conversation.');
            }

            $memberIds = DB::table('message_thread_members')
                ->where('thread_id', $threadId)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->values();
        } else {
            $recipients = collect($validated['recipient_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0 && $id !== $authId)
                ->unique()
                ->values();

            if ($recipients->isEmpty()) {
                return redirect()->route('messages.index')
                    ->withInput()
                    ->withErrors(['recipient_ids' => 'Please select at least one recipient.']);
            }

            if ($recipients->count() === 1) {
                $threadId = $this->ensureDirectThread($authId, (int) $recipients->first());
            } else {
                $threadId = $this->createGroupThread($authId, $recipients);
            }

            $memberIds = DB::table('message_thread_members')
                ->where('thread_id', $threadId)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->values();
        }

        $body = $this->sanitizeMultilineInput($validated['message'] ?? '', 2000);
        $storedImages = $supportsMessageImages
            ? $uploadedImages
                ->map(fn (UploadedFile $image) => $this->storeMessageImage($image))
                ->values()
            : collect();

        if ($body === '' && $storedImages->isEmpty()) {
            $errorMessage = 'Please type a message or select at least one image to send.';

            if ($wantsJson) {
                return response()->json([
                    'message' => $errorMessage,
                    'errors' => ['message' => [$errorMessage]],
                ], 422);
            }

            return redirect()->route('messages.index', $threadId > 0 ? ['thread' => $threadId] : [])
                ->withInput()
                ->with('error', $errorMessage);
        }

        $messagePayloads = $storedImages->isEmpty()
            ? collect([[
                'message' => $body,
                'image_path' => null,
                'image_original_name' => null,
            ]])
            : $storedImages->values()->map(function (array $storedImage, int $index) use ($body) {
                return [
                    'message' => $index === 0 ? $body : '',
                    'image_path' => $storedImage['path'] ?? null,
                    'image_original_name' => $storedImage['original_name'] ?? null,
                ];
            });

        $now = now();
        $batchUuid = $this->hasMessageBatchColumn() ? (string) Str::uuid() : null;
        $rows = [];

        foreach ($messagePayloads as $payload) {
            foreach ($memberIds as $recipientId) {
                $row = [
                    'thread_id' => $threadId,
                    'sender_id' => $authId,
                    'recipient_id' => (int) $recipientId,
                    'message' => (string) ($payload['message'] ?? ''),
                    'read_at' => (int) $recipientId === $authId ? $now : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($supportsMessageImages) {
                    $row['image_path'] = $payload['image_path'] ?? null;
                    $row['image_original_name'] = $payload['image_original_name'] ?? null;
                }

                if ($batchUuid !== null) {
                    $row['batch_uuid'] = $batchUuid;
                }

                $rows[] = $row;
            }
        }

        DB::table('user_messages')->insert($rows);
        $this->broadcastThreadUpdates($memberIds, $threadId);

        $storedImageCount = $storedImages->count();

        if ($wantsJson) {
            return response()->json([
                'ok' => true,
                'thread_id' => $threadId,
                'has_image' => $storedImageCount > 0,
                'notice' => $storedImageCount > 1
                    ? 'Images sent.'
                    : ($storedImageCount === 1 ? 'Image sent.' : 'Message sent.'),
            ]);
        }

        return redirect()->route('messages.index', ['thread' => $threadId]);
    }

    public function renameGroup(Request $request, int $thread): RedirectResponse
    {
        if (!$this->hasMessagingSchema()) {
            return redirect()->route('messages.index')
                ->with('error', 'Messaging is not configured yet. Run migrations first.');
        }

        $authId = (int) Auth::id();
        $threadId = max(0, $thread);
        if ($threadId <= 0) {
            return redirect()->route('messages.index')->with('error', 'Conversation not found.');
        }

        $isMember = DB::table('message_thread_members')
            ->where('thread_id', $threadId)
            ->where('user_id', $authId)
            ->exists();

        if (!$isMember) {
            return redirect()->route('messages.index')->with('error', 'You are not allowed to rename this conversation.');
        }

        $threadRecord = DB::table('message_threads')
            ->where('id', $threadId)
            ->first(['id', 'is_group']);

        $memberCount = (int) DB::table('message_thread_members')
            ->where('thread_id', $threadId)
            ->count();

        $isGroup = (bool) ($threadRecord->is_group ?? false) || $memberCount > 2;
        if (!$threadRecord || !$isGroup) {
            return redirect()->route('messages.index', ['thread' => $threadId])
                ->with('error', 'Only group conversations can be renamed.');
        }

        $validated = $request->validateWithBag('renameGroup', [
            'group_name' => ['required', 'string', 'max:120'],
            'group_rename_open' => ['nullable', 'in:1'],
        ]);

        $groupName = $this->sanitizeSingleLineInput($validated['group_name'] ?? '', 120);
        if ($groupName === '') {
            return redirect()->route('messages.index', ['thread' => $threadId])
                ->withInput()
                ->withErrors(['group_name' => 'Please enter a group name.'], 'renameGroup');
        }

        DB::table('message_threads')
            ->where('id', $threadId)
            ->update([
                'name' => $groupName,
                'updated_at' => now(),
            ]);

        return redirect()->route('messages.index', ['thread' => $threadId])
            ->with('success', 'Group name updated.');
    }

    public function markThreadUnread(Request $request, int $thread): RedirectResponse
    {
        if (!$this->hasMessagingSchema()) {
            return redirect()->route('messages.index')
                ->with('error', 'Messaging is not configured yet. Run migrations first.');
        }

        $authId = (int) Auth::id();
        $threadId = max(0, $thread);
        $currentThreadId = max(0, (int) $request->input('current_thread'));

        if ($threadId <= 0) {
            return redirect()->route('messages.index')
                ->with('error', 'Conversation not found.');
        }

        $isMember = DB::table('message_thread_members')
            ->where('thread_id', $threadId)
            ->where('user_id', $authId)
            ->exists();

        if (!$isMember) {
            return redirect()->route('messages.index')
                ->with('error', 'You are not allowed to update this conversation.');
        }

        if ($this->hasManualUnreadColumn()) {
            DB::table('message_thread_members')
                ->where('thread_id', $threadId)
                ->where('user_id', $authId)
                ->update([
                    'manual_unread_at' => now(),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('user_messages')
                ->where('thread_id', $threadId)
                ->where('recipient_id', $authId)
                ->where('sender_id', '!=', $authId)
                ->whereNotNull('thread_id')
                ->whereNotNull('read_at')
                ->update([
                    'read_at' => null,
                    'updated_at' => now(),
                ]);
        }

        if ($currentThreadId === $threadId) {
            return redirect()->route('messages.index', ['thread' => $threadId])
                ->with('preserve_unread_thread', $threadId);
        }

        return redirect()->route('messages.index', $this->threadActionRedirectParams($authId, $threadId, $currentThreadId));
    }

    public function markThreadRead(Request $request, int $thread): RedirectResponse
    {
        if (!$this->hasMessagingSchema()) {
            return redirect()->route('messages.index')
                ->with('error', 'Messaging is not configured yet. Run migrations first.');
        }

        $authId = (int) Auth::id();
        $threadId = max(0, $thread);
        $currentThreadId = max(0, (int) $request->input('current_thread'));

        if ($threadId <= 0) {
            return redirect()->route('messages.index')
                ->with('error', 'Conversation not found.');
        }

        $isMember = DB::table('message_thread_members')
            ->where('thread_id', $threadId)
            ->where('user_id', $authId)
            ->exists();

        if (!$isMember) {
            return redirect()->route('messages.index')
                ->with('error', 'You are not allowed to update this conversation.');
        }

        DB::table('user_messages')
            ->where('thread_id', $threadId)
            ->where('recipient_id', $authId)
            ->where('sender_id', '!=', $authId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        if ($this->hasManualUnreadColumn()) {
            DB::table('message_thread_members')
                ->where('thread_id', $threadId)
                ->where('user_id', $authId)
                ->whereNotNull('manual_unread_at')
                ->update([
                    'manual_unread_at' => null,
                    'updated_at' => now(),
                ]);
        }

        if ($currentThreadId === $threadId) {
            return redirect()->route('messages.index', ['thread' => $threadId]);
        }

        return redirect()->route('messages.index', $this->threadActionRedirectParams($authId, $threadId, $currentThreadId));
    }

    public function deleteConversation(Request $request, int $thread): RedirectResponse
    {
        if (!$this->hasMessagingSchema()) {
            return redirect()->route('messages.index')
                ->with('error', 'Messaging is not configured yet. Run migrations first.');
        }

        $authId = (int) Auth::id();
        $threadId = max(0, $thread);
        $currentThreadId = max(0, (int) $request->input('current_thread'));

        if ($threadId <= 0) {
            return redirect()->route('messages.index')
                ->with('error', 'Conversation not found.');
        }

        $isMember = DB::table('message_thread_members')
            ->where('thread_id', $threadId)
            ->where('user_id', $authId)
            ->exists();

        if (!$isMember) {
            return redirect()->route('messages.index')
                ->with('error', 'You are not allowed to delete this conversation.');
        }

        DB::table('user_messages')
            ->where('thread_id', $threadId)
            ->where('recipient_id', $authId)
            ->delete();

        if ($this->hasManualUnreadColumn()) {
            DB::table('message_thread_members')
                ->where('thread_id', $threadId)
                ->where('user_id', $authId)
                ->update([
                    'manual_unread_at' => null,
                    'updated_at' => now(),
                ]);
        }

        return redirect()->route('messages.index', $this->threadActionRedirectParams($authId, $threadId, $currentThreadId));
    }

    public function poll(Request $request): JsonResponse
    {
        if (!$this->hasMessagingSchema()) {
            return response()->json([
                'ok' => true,
                'latest_global_id' => 0,
                'latest_thread_id' => 0,
                'unread_count' => 0,
            ]);
        }

        $authId = (int) Auth::id();
        $threadId = (int) $request->integer('thread');

        $latestGlobalId = (int) (DB::table('user_messages')
            ->where('recipient_id', $authId)
            ->max('id') ?? 0);

        $latestThreadId = 0;
        if ($threadId > 0) {
            $latestThreadId = (int) (DB::table('user_messages')
                ->where('recipient_id', $authId)
                ->where('thread_id', $threadId)
                ->max('id') ?? 0);
        }

        $unreadCount = $this->unreadThreadCountForUser($authId);

        return response()->json([
            'ok' => true,
            'latest_global_id' => $latestGlobalId,
            'latest_thread_id' => $latestThreadId,
            'unread_count' => $unreadCount,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function conversation(Request $request): JsonResponse
    {
        if (!$this->hasMessagingSchema()) {
            return response()->json([
                'ok' => true,
                'messages' => [],
                'latest_thread_id' => 0,
                'unread_count' => 0,
            ]);
        }

        $authId = (int) Auth::id();
        $threadId = (int) $request->integer('thread');
        if ($threadId <= 0) {
            return response()->json([
                'ok' => true,
                'messages' => [],
                'latest_thread_id' => 0,
                'unread_count' => $this->unreadThreadCountForUser($authId),
            ]);
        }

        $isMember = DB::table('message_thread_members')
            ->where('thread_id', $threadId)
            ->where('user_id', $authId)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'ok' => true,
                'messages' => [],
                'latest_thread_id' => 0,
                'unread_count' => $this->unreadThreadCountForUser($authId),
            ]);
        }

        $conversation = DB::table('user_messages')
            ->where('thread_id', $threadId)
            ->where('recipient_id', $authId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit(150)
            ->get();

        DB::table('user_messages')
            ->where('thread_id', $threadId)
            ->where('recipient_id', $authId)
            ->where('sender_id', '!=', $authId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        $messages = $this->groupConversationEntries($conversation, $authId)->values();

        return response()->json([
            'ok' => true,
            'messages' => $messages,
            'latest_thread_id' => (int) ($conversation->max('id') ?? 0),
            'unread_count' => $this->unreadThreadCountForUser($authId),
        ]);
    }

    public function mobileIndex(Request $request): JsonResponse
    {
        if (!$this->hasMessagingSchema()) {
            return response()->json([
                'ok' => true,
                'threads' => [],
                'selected_thread' => null,
                'conversation' => [],
                'available_users' => [],
                'latest_thread_id' => 0,
                'latest_global_id' => 0,
                'unread_count' => 0,
            ]);
        }

        $authUser = Auth::user();
        $authId = (int) ($authUser?->idno ?? $request->integer('user_id'));

        if ($authUser && $authId > 0 && (int) $authUser->idno !== $authId) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($authId <= 0) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $threadId = (int) $request->integer('thread');

        $latestByThread = DB::table('user_messages')
            ->selectRaw('thread_id, MAX(created_at) as latest_created_at, MAX(id) as latest_message_id')
            ->where('recipient_id', $authId)
            ->whereNotNull('thread_id')
            ->groupBy('thread_id');

        $threadRowsQuery = DB::table('message_thread_members as member')
            ->join('message_threads as thread', 'thread.id', '=', 'member.thread_id')
            ->joinSub($latestByThread, 'latest', function ($join) {
                $join->on('latest.thread_id', '=', 'thread.id');
            })
            ->where('member.user_id', $authId)
            ->select([
                'thread.id as thread_id',
                'thread.name as thread_name',
                'thread.is_group',
                'latest.latest_created_at',
                'latest.latest_message_id',
            ]);

        if ($this->hasManualUnreadColumn()) {
            $threadRowsQuery->addSelect('member.manual_unread_at');
        }

        $threadRows = $threadRowsQuery
            ->orderByDesc('latest_created_at')
            ->orderByDesc('latest_message_id')
            ->get();

        $latestIds = $threadRows->pluck('latest_message_id')->filter()->map(fn ($id) => (int) $id)->values();
        $threadIds = $threadRows->pluck('thread_id')->filter()->map(fn ($id) => (int) $id)->values();

        $latestMessages = $latestIds->isEmpty()
            ? collect()
            : DB::table('user_messages')->whereIn('id', $latestIds)->get()->keyBy('id');

        $threadMembersRaw = $threadIds->isEmpty()
            ? collect()
            : DB::table('message_thread_members as member')
                ->join('tbusers as user', 'user.idno', '=', 'member.user_id')
                ->whereIn('member.thread_id', $threadIds)
                ->select([
                    'member.thread_id',
                    'user.idno',
                    'user.fname',
                    'user.lname',
                    'user.position',
                    'user.office',
                    'user.province',
                    'user.region',
                ])
                ->get();

        $membersByThread = $threadMembersRaw->groupBy('thread_id');

        $unreadByThread = DB::table('user_messages')
            ->select('thread_id', DB::raw('COUNT(*) as unread_count'))
            ->where('recipient_id', $authId)
            ->where('sender_id', '!=', $authId)
            ->whereNull('read_at')
            ->whereNotNull('thread_id')
            ->groupBy('thread_id')
            ->pluck('unread_count', 'thread_id');

        $threadsCollection = $threadRows->map(function ($row) use ($authId, $latestMessages, $membersByThread, $unreadByThread) {
            $latest = $latestMessages->get((int) $row->latest_message_id);
            $threadId = (int) ($row->thread_id ?? 0);
            if (!$latest || $threadId <= 0) {
                return null;
            }

            $members = collect($membersByThread->get($threadId, collect()));
            $counterparts = $members->filter(fn ($member) => (int) ($member->idno ?? 0) !== $authId)->values();

            $isGroup = (bool) ($row->is_group ?? false) || $counterparts->count() > 1;
            $name = trim((string) ($row->thread_name ?? ''));
            $subtitle = '';

            if ($isGroup) {
                $avatarMembers = $counterparts->map(function ($member) {
                    $memberName = trim((string) (($member->fname ?? '') . ' ' . ($member->lname ?? '')));

                    return $memberName !== '' ? $memberName : 'Unknown User';
                })->sortBy(fn ($memberName) => mb_strtolower((string) $memberName))->values();

                if ($name === '') {
                    $names = $avatarMembers;
                    $name = $names->take(2)->implode(', ');
                    $remaining = max(0, $names->count() - 2);
                    if ($remaining > 0) {
                        $name .= ' +' . $remaining;
                    }
                }

                if ($name === '') {
                    $name = 'Group chat';
                }

                $subtitle = max(2, $members->count()) . ' participants';
            } else {
                $counterpart = $counterparts->first();
                if (!$counterpart) {
                    return null;
                }

                $name = trim((string) (($counterpart->fname ?? '') . ' ' . ($counterpart->lname ?? '')));
                $name = $name !== '' ? $name : 'Unknown User';
                $subtitle = trim((string) ($counterpart->position ?: $counterpart->office ?: 'PDMU User'));
            }

            $latestSenderId = (int) ($latest->sender_id ?? 0);
            $latestSender = $members->first(fn ($member) => (int) ($member->idno ?? 0) === $latestSenderId);
            $latestSenderName = '';
            if ($latestSender) {
                $latestSenderName = trim((string) (($latestSender->fname ?? '') . ' ' . ($latestSender->lname ?? '')));
                $latestSenderName = $latestSenderName !== '' ? $latestSenderName : 'Unknown User';
            }

            $isMine = $latestSenderId === $authId;
            $actualUnreadCount = (int) ($unreadByThread[$threadId] ?? 0);
            $manualUnread = $this->hasManualUnreadColumn() && !empty($row->manual_unread_at);
            $threadUnreadCount = $actualUnreadCount > 0 ? $actualUnreadCount : ($manualUnread ? 1 : 0);

            return [
                'thread_id' => $threadId,
                'name' => $name,
                'subtitle' => $subtitle,
                'is_group' => $isGroup,
                'avatar_members' => $isGroup ? ($avatarMembers ?? collect())->values()->all() : [],
                'preview' => $this->messagePreviewText($latest->message ?? '', $latest->image_path ?? null, 72),
                'preview_sender' => $latestSenderName,
                'preview_is_mine' => $isMine,
                'preview_show_unread_label' => !$isMine && $actualUnreadCount > 0,
                'time' => $this->formatMessageTimestamp($latest->created_at ?? null),
                'latest_at' => $latest->created_at,
                'unread' => $threadUnreadCount,
            ];
        })->filter()->values();

        if ($threadId <= 0 && $threadsCollection->isNotEmpty()) {
            $threadId = (int) ($threadsCollection->first()['thread_id'] ?? 0);
        }

        $selectedThread = null;
        $conversation = collect();
        if ($threadId > 0) {
            $context = $this->mobileThreadContext($threadId, $authId);
            $selectedThread = $context['selected_thread'];
            $conversation = $context['conversation'];
        }

        return response()->json([
            'ok' => true,
            'threads' => $threadsCollection->values(),
            'selected_thread_id' => $threadId,
            'selected_thread' => $selectedThread,
            'conversation' => $conversation,
            'available_users' => $this->availableUsers()->map(function ($user) {
                $fullName = trim((string) (($user->fname ?? '') . ' ' . ($user->lname ?? '')));

                return [
                    'id' => (int) ($user->idno ?? 0),
                    'name' => $fullName !== '' ? $fullName : 'Unknown User',
                    'position' => trim((string) ($user->position ?? '')),
                    'office' => trim((string) ($user->office ?? '')),
                    'search' => mb_strtolower(trim((string) (($user->fname ?? '') . ' ' . ($user->lname ?? '') . ' ' . ($user->position ?? '') . ' ' . ($user->office ?? '')))),
                ];
            })->values(),
            'latest_thread_id' => (int) ($conversation->max('id') ?? 0),
            'latest_global_id' => (int) (DB::table('user_messages')
                ->where('recipient_id', $authId)
                ->max('id') ?? 0),
            'unread_count' => $this->unreadThreadCountForUser($authId),
        ]);
    }

    private function availableUsers(): Collection
    {
        $authId = (int) Auth::id();

        return User::query()
            ->where('idno', '!=', $authId)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(TRIM(status)) in (?, ?)', ['active', 'approved']);
            })
            ->orderBy('fname')
            ->orderBy('lname')
            ->select(['idno', 'fname', 'lname', 'position', 'office'])
            ->limit(300)
            ->get();
    }

    private function mobileThreadContext(int $threadId, int $authId): array
    {
        $threadExists = DB::table('message_thread_members')
            ->where('thread_id', $threadId)
            ->where('user_id', $authId)
            ->exists();

        if (!$threadExists) {
            return [
                'selected_thread' => null,
                'selected_group_members' => collect(),
                'conversation' => collect(),
            ];
        }

        $thread = DB::table('message_threads')->where('id', $threadId)->first();
        $members = DB::table('message_thread_members as member')
            ->join('tbusers as user', 'user.idno', '=', 'member.user_id')
            ->where('member.thread_id', $threadId)
            ->select(['user.idno', 'user.fname', 'user.lname', 'user.position', 'user.office', 'user.province', 'user.region'])
            ->get();

        $counterparts = $members->filter(fn ($member) => (int) ($member->idno ?? 0) !== $authId)->values();
        $isGroup = (bool) ($thread->is_group ?? false) || $counterparts->count() > 1;

        $selectedUser = null;
        $selectedGroupMembers = collect();

        if ($isGroup) {
            $threadName = trim((string) ($thread->name ?? ''));
            if ($threadName === '') {
                $threadName = $counterparts
                    ->map(function ($member) {
                        $memberName = trim((string) (($member->fname ?? '') . ' ' . ($member->lname ?? '')));
                        return $memberName !== '' ? $memberName : 'Unknown User';
                    })
                    ->take(3)
                    ->implode(', ');
            }

            $selectedGroupMembers = $members
                ->map(function ($member) use ($authId) {
                    $name = trim((string) (($member->fname ?? '') . ' ' . ($member->lname ?? '')));

                    return (object) [
                        'idno' => (int) ($member->idno ?? 0),
                        'name' => $name !== '' ? $name : 'Unknown User',
                        'position' => trim((string) ($member->position ?? '')),
                        'office' => trim((string) ($member->office ?? '')),
                        'province' => trim((string) ($member->province ?? '')),
                        'region' => trim((string) ($member->region ?? '')),
                        'is_me' => (int) ($member->idno ?? 0) === $authId,
                    ];
                })
                ->sortBy(fn ($member) => mb_strtolower((string) $member->name))
                ->values();

            $selectedUser = (object) [
                'idno' => $threadId,
                'fname' => $threadName !== '' ? $threadName : 'Group chat',
                'lname' => '',
                'custom_name' => trim((string) ($thread->name ?? '')),
                'position' => 'Group chat',
                'office' => max(2, $members->count()) . ' participants',
            ];
        } else {
            $counterpart = $counterparts->first();
            if ($counterpart) {
                $selectedUser = (object) [
                    'idno' => $threadId,
                    'fname' => $counterpart->fname,
                    'lname' => $counterpart->lname,
                    'position' => $counterpart->position,
                    'office' => $counterpart->office,
                    'province' => $counterpart->province,
                    'region' => $counterpart->region,
                ];
            }
        }

        $conversation = DB::table('user_messages')
            ->where('thread_id', $threadId)
            ->where('recipient_id', $authId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit(150)
            ->get();

        DB::table('user_messages')
            ->where('thread_id', $threadId)
            ->where('recipient_id', $authId)
            ->where('sender_id', '!=', $authId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        if ($this->hasManualUnreadColumn()) {
            DB::table('message_thread_members')
                ->where('thread_id', $threadId)
                ->where('user_id', $authId)
                ->whereNotNull('manual_unread_at')
                ->update([
                    'manual_unread_at' => null,
                    'updated_at' => now(),
                ]);
        }

        $subtitle = '';
        if ($selectedUser) {
            $position = trim((string) ($selectedUser->position ?? ''));
            $office = trim((string) ($selectedUser->office ?? ''));
            if ($position !== '' && $office !== '') {
                $subtitle = $position . ' · ' . $office;
            } elseif ($position !== '') {
                $subtitle = $position;
            } elseif ($office !== '') {
                $subtitle = $office;
            }
        }

        return [
            'selected_thread' => $selectedUser ? [
                'thread_id' => $threadId,
                'name' => trim((string) (($selectedUser->fname ?? '') . ' ' . ($selectedUser->lname ?? ''))) !== ''
                    ? trim((string) (($selectedUser->fname ?? '') . ' ' . ($selectedUser->lname ?? '')))
                    : 'Unknown User',
                'subtitle' => $subtitle,
                'is_group' => $isGroup,
                'custom_name' => trim((string) ($selectedUser->custom_name ?? '')),
                'members' => $selectedGroupMembers->map(function ($member) {
                    return [
                        'idno' => (int) ($member->idno ?? 0),
                        'name' => (string) ($member->name ?? ''),
                        'position' => trim((string) ($member->position ?? '')),
                        'office' => trim((string) ($member->office ?? '')),
                        'province' => trim((string) ($member->province ?? '')),
                        'region' => trim((string) ($member->region ?? '')),
                        'is_me' => (bool) ($member->is_me ?? false),
                    ];
                })->values()->all(),
            ] : null,
            'selected_group_members' => $selectedGroupMembers,
            'conversation' => $this->groupConversationEntries($conversation, $authId)->values(),
        ];
    }

    private function paginateCollection(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));
        $total = $items->count();
        $results = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return (new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]))->withQueryString();
    }

    private function formatMessageTimestamp($timestamp): string
    {
        if (empty($timestamp)) {
            return '';
        }

        return Carbon::parse($timestamp)->format('M j, Y g:i A');
    }

    private function formatConversationTimestamp($timestamp): string
    {
        if (empty($timestamp)) {
            return '';
        }

        return Carbon::parse($timestamp)->format('M j, Y g:i A');
    }

    private function messagePreviewText($message, ?string $imagePath = null, int $limit = 72): string
    {
        $text = trim((string) $message);

        if ($text !== '') {
            return Str::limit($text, $limit);
        }

        return !empty($imagePath) ? 'Sent a photo' : '';
    }

    private function hasMessagingSchema(): bool
    {
        return Schema::hasTable('user_messages')
            && Schema::hasTable('message_threads')
            && Schema::hasTable('message_thread_members')
            && Schema::hasColumn('user_messages', 'thread_id');
    }

    private function hasManualUnreadColumn(): bool
    {
        return Schema::hasTable('message_thread_members')
            && Schema::hasColumn('message_thread_members', 'manual_unread_at');
    }

    private function hasMessageImageColumns(): bool
    {
        return Schema::hasTable('user_messages')
            && Schema::hasColumn('user_messages', 'image_path')
            && Schema::hasColumn('user_messages', 'image_original_name');
    }

    private function hasMessageBatchColumn(): bool
    {
        return Schema::hasTable('user_messages')
            && Schema::hasColumn('user_messages', 'batch_uuid');
    }

    private function storeMessageImage(UploadedFile $image): array
    {
        $folder = 'uploads/message-images/' . now()->format('Y/m');
        $directory = public_path($folder);
        File::ensureDirectoryExists($directory);

        $extension = strtolower((string) ($image->getClientOriginalExtension() ?: $image->extension() ?: 'jpg'));
        $filename = now()->format('YmdHis') . '-' . Str::lower(Str::random(20)) . '.' . $extension;

        $image->move($directory, $filename);

        return [
            'path' => $folder . '/' . $filename,
            'original_name' => $this->sanitizeUploadOriginalName($image->getClientOriginalName()),
        ];
    }

    private function messageImageUrl(?string $imagePath): string
    {
        $path = trim((string) $imagePath);
        if ($path === '') {
            return '';
        }

        return asset(ltrim(str_replace('\\', '/', $path), '/'));
    }

    private function unreadThreadCountForUser(int $authId): int
    {
        $visibleThreadIds = DB::table('user_messages')
            ->where('recipient_id', $authId)
            ->whereNotNull('thread_id')
            ->distinct()
            ->pluck('thread_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($visibleThreadIds->isEmpty()) {
            return 0;
        }

        $actualUnreadThreadIds = DB::table('user_messages')
            ->where('recipient_id', $authId)
            ->where('sender_id', '!=', $authId)
            ->whereNull('read_at')
            ->whereIn('thread_id', $visibleThreadIds)
            ->distinct()
            ->pluck('thread_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $manualUnreadThreadIds = collect();
        if ($this->hasManualUnreadColumn()) {
            $manualUnreadThreadIds = DB::table('message_thread_members')
                ->where('user_id', $authId)
                ->whereNotNull('manual_unread_at')
                ->whereIn('thread_id', $visibleThreadIds)
                ->pluck('thread_id')
                ->map(fn ($id) => (int) $id)
                ->values();
        }

        return $actualUnreadThreadIds
            ->merge($manualUnreadThreadIds)
            ->unique()
            ->count();
    }

    private function broadcastThreadUpdates(Collection $memberIds, int $threadId): void
    {
        if ($threadId <= 0) {
            return;
        }

        $recipientIds = $memberIds
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($recipientIds === []) {
            return;
        }

        app()->terminating(function () use ($recipientIds, $threadId) {
            try {
                broadcast(new MessageThreadUpdated($recipientIds, $threadId))->toOthers();
            } catch (\Throwable $exception) {
                Log::warning('Failed to broadcast realtime message update.', [
                    'thread_id' => $threadId,
                    'recipient_ids' => $recipientIds,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }

    private function groupConversationEntries(Collection $conversation, int $authId): Collection
    {
        $groups = [];

        foreach ($conversation as $entry) {
            $senderId = (int) ($entry->sender_id ?? 0);
            $createdAtRaw = (string) ($entry->created_at ?? '');
            $batchUuid = trim((string) data_get($entry, 'batch_uuid', ''));
            $imageUrl = $this->messageImageUrl($entry->image_path ?? null);
            $hasImage = $imageUrl !== '';
            $messageText = (string) ($entry->message ?? '');
            $imageName = trim((string) ($entry->image_original_name ?? '')) ?: 'Shared image';

            $shouldAppendToPrevious = false;
            if (!empty($groups)) {
                $lastIndex = array_key_last($groups);
                $lastGroup = $groups[$lastIndex];
                $sameSender = (int) ($lastGroup['sender_id'] ?? 0) === $senderId;
                $sameCreatedAt = (string) ($lastGroup['created_at_raw'] ?? '') === $createdAtRaw;
                $sameBatch = $batchUuid !== '' && (string) ($lastGroup['batch_uuid'] ?? '') === $batchUuid;
                $heuristicMultiImageBatch = $batchUuid === ''
                    && (string) ($lastGroup['batch_uuid'] ?? '') === ''
                    && $sameSender
                    && $sameCreatedAt
                    && !empty($lastGroup['images'])
                    && $hasImage;

                $shouldAppendToPrevious = $sameSender && $sameCreatedAt && ($sameBatch || $heuristicMultiImageBatch);
            }

            if (!$shouldAppendToPrevious) {
                $groups[] = [
                    'id' => (int) ($entry->id ?? 0),
                    'sender_id' => $senderId,
                    'batch_uuid' => $batchUuid,
                    'created_at_raw' => $createdAtRaw,
                    'is_mine' => $senderId === $authId,
                    'message' => trim($messageText),
                    'images' => [],
                    'time' => $this->formatConversationTimestamp($entry->created_at ?? null),
                ];
            }

            $groupIndex = array_key_last($groups);
            if ($groupIndex === null) {
                continue;
            }

            if (trim((string) ($groups[$groupIndex]['message'] ?? '')) === '' && trim($messageText) !== '') {
                $groups[$groupIndex]['message'] = trim($messageText);
            }

            if ($hasImage) {
                $groups[$groupIndex]['images'][] = [
                    'url' => $imageUrl,
                    'name' => $imageName,
                ];
            }

            $groups[$groupIndex]['id'] = max(
                (int) ($groups[$groupIndex]['id'] ?? 0),
                (int) ($entry->id ?? 0)
            );
        }

        return collect($groups)->values();
    }

    private function threadActionRedirectParams(int $authId, int $actedThreadId, int $currentThreadId = 0): array
    {
        if ($currentThreadId > 0 && $currentThreadId !== $actedThreadId && $this->userCanSeeThread($authId, $currentThreadId)) {
            return ['thread' => $currentThreadId];
        }

        if ($currentThreadId === $actedThreadId) {
            $alternateThreadId = $this->latestVisibleThreadIdForUser($authId, $actedThreadId);
            if ($alternateThreadId > 0) {
                return ['thread' => $alternateThreadId];
            }
        }

        return [];
    }

    private function userCanSeeThread(int $authId, int $threadId): bool
    {
        if ($threadId <= 0) {
            return false;
        }

        return DB::table('user_messages')
            ->where('thread_id', $threadId)
            ->where('recipient_id', $authId)
            ->exists();
    }

    private function latestVisibleThreadIdForUser(int $authId, int $exceptThreadId = 0): int
    {
        $query = DB::table('user_messages')
            ->selectRaw('thread_id, MAX(created_at) as latest_created_at, MAX(id) as latest_message_id')
            ->where('recipient_id', $authId)
            ->whereNotNull('thread_id');

        if ($exceptThreadId > 0) {
            $query->where('thread_id', '!=', $exceptThreadId);
        }

        return (int) ($query
            ->groupBy('thread_id')
            ->orderByDesc('latest_created_at')
            ->orderByDesc('latest_message_id')
            ->value('thread_id') ?? 0);
    }

    private function ensureDirectThread(int $authId, int $recipientId): int
    {
        $candidateIds = DB::table('message_thread_members')
            ->whereIn('user_id', [$authId, $recipientId])
            ->groupBy('thread_id')
            ->havingRaw('COUNT(DISTINCT user_id) = 2')
            ->pluck('thread_id');

        if ($candidateIds->isNotEmpty()) {
            $threadId = (int) (DB::table('message_threads as thread')
                ->where('thread.is_group', false)
                ->whereIn('thread.id', $candidateIds)
                ->whereRaw('(SELECT COUNT(*) FROM message_thread_members member WHERE member.thread_id = thread.id) = 2')
                ->value('thread.id') ?? 0);

            if ($threadId > 0) {
                return $threadId;
            }
        }

        $threadId = DB::table('message_threads')->insertGetId([
            'is_group' => false,
            'name' => null,
            'created_by' => $authId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('message_thread_members')->insert([
            [
                'thread_id' => $threadId,
                'user_id' => $authId,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'thread_id' => $threadId,
                'user_id' => $recipientId,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return $threadId;
    }

    private function sanitizeMultilineInput($value, int $maxLength = 0): string
    {
        return $this->sanitizeTextInput($value, true, $maxLength);
    }

    private function sanitizeSingleLineInput($value, int $maxLength = 0): string
    {
        return $this->sanitizeTextInput($value, false, $maxLength);
    }

    private function sanitizeTextInput($value, bool $allowNewLines = false, int $maxLength = 0): string
    {
        $text = (string) $value;
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = strip_tags($text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $text) ?? $text;
        $text = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]+/u', '', $text) ?? $text;

        if ($allowNewLines) {
            $text = preg_replace('/[ \t]+\n/u', "\n", $text) ?? $text;
            $text = preg_replace('/\n[ \t]+/u', "\n", $text) ?? $text;
            $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
            $text = preg_replace('/[ \t]{2,}/u', ' ', $text) ?? $text;
        } else {
            $text = str_replace(["\n", "\t"], ' ', $text);
            $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        }

        $text = trim($text);

        if ($maxLength > 0) {
            $text = mb_substr($text, 0, $maxLength);
        }

        return $text;
    }

    private function sanitizeUploadOriginalName(?string $value): string
    {
        $name = basename((string) $value);
        $name = preg_replace('/[^A-Za-z0-9._ -]+/u', '_', $name) ?? $name;
        $name = trim($name, " \t\n\r\0\x0B.");

        if ($name === '') {
            $name = 'shared-image';
        }

        return mb_substr($name, 0, 180);
    }

    private function createGroupThread(int $authId, Collection $recipients): int
    {
        $memberIds = $recipients
            ->push($authId)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->sort()
            ->values();

        $threadId = DB::table('message_threads')->insertGetId([
            'is_group' => true,
            'name' => null,
            'created_by' => $authId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rows = $memberIds->map(function ($memberId) use ($threadId) {
            return [
                'thread_id' => $threadId,
                'user_id' => (int) $memberId,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        DB::table('message_thread_members')->insert($rows);

        return $threadId;
    }
}
