<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ChatSession;

trait ResolvesAiSession
{
    /**
     * Resolve a chat session for the current user or return the active session.
     */
    protected function resolveSession($user, ?int $sessionId): ChatSession
    {
        if ($sessionId) {
            $session = ChatSession::where('id', $sessionId)
                ->where('user_id', $user->id)
                ->first();

            if ($session) {
                return $session;
            }
        }

        return $user->activeChatSession();
    }
}
