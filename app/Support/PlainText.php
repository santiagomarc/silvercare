<?php

namespace App\Support;

class PlainText
{
    /**
     * Strip emoji from system-generated text before it is shown in the web UI.
     *
     * Alert and notification titles are written with an emoji prefix
     * ("🚨 Critical Blood Pressure …") because email subjects and lock-screen
     * push notifications have no other way to signal severity. The web UI
     * does: every alert card already carries a severity icon and a word, and
     * the design system bans emoji outright (FRONTEND_DESIGN_SYSTEM.md §6).
     * So the stored text keeps its emoji for those channels, and the web UI
     * reads it through here.
     *
     * Only use this on text the system wrote. Text a person typed — a chat
     * message, a note — is theirs, emoji included.
     */
    public static function title(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        // Pictographs, plus the Miscellaneous Symbols and Dingbats blocks — ✓
        // (U+2713) and ✗ are not Extended_Pictographic in Unicode, but they
        // render as emoji and the notification titles use them — plus the
        // variation selector and zero-width joiner that glue multi-codepoint
        // emoji together (⚠️ is U+26A0 U+FE0F).
        $stripped = preg_replace('/[\p{Extended_Pictographic}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $text);

        return trim(preg_replace('/\s{2,}/u', ' ', $stripped ?? $text));
    }
}
