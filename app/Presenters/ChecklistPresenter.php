<?php

namespace App\Presenters;

use Carbon\Carbon;

class ChecklistPresenter
{
    private const CATEGORY_ICONS = [
        'Health' => '❤️',
        'Exercise' => '🏃',
        'Nutrition' => '🍎',
        'Social' => '👥',
        'Hygiene' => '🧼',
        'Mental' => '🧠',
        'Medication' => '💊',
        'Other' => '📋',
    ];

    public static function categoryIcon(string $category): string
    {
        return self::CATEGORY_ICONS[$category] ?? '📋';
    }

    /**
     * Return date-header label and CSS class for a given date.
     */
    public static function dateHeader(string $date): array
    {
        $dateObj = Carbon::parse($date);
        $isToday = $dateObj->isToday();
        $isPast = $dateObj->isPast() && !$isToday;

        $css = $isToday
            ? 'bg-green-500 text-white'
            : ($isPast ? 'bg-gray-400 text-white' : 'bg-blue-500 text-white');

        if ($isToday) {
            $label = '📅 Today - ' . $dateObj->format('M d');
        } elseif ($dateObj->isYesterday()) {
            $label = 'Yesterday - ' . $dateObj->format('M d');
        } elseif ($dateObj->isTomorrow()) {
            $label = 'Tomorrow - ' . $dateObj->format('M d');
        } else {
            $label = $dateObj->format('l, M d');
        }

        return [
            'label'   => $label,
            'css'     => $css,
            'isToday' => $isToday,
            'isPast'  => $isPast,
        ];
    }
}
