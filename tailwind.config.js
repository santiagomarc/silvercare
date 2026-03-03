import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    /**
     * Safelist: dynamic Tailwind classes used in JS template literals
     * (vital modals, status colors) that would otherwise be purged.
     */
    safelist: [
        // Vital card dynamic colors (used in openVitalModal JS)
        ...['red', 'blue', 'orange', 'rose'].flatMap(c => [
            `bg-${c}-50`, `bg-${c}-100`, `bg-${c}-200`, `bg-${c}-500`, `bg-${c}-600`,
            `text-${c}-400`, `text-${c}-500`, `text-${c}-600`, `text-${c}-700`,
            `border-${c}-400`,
            `focus:border-${c}-400`, `focus:ring-${c}-100`,
        ]),
        // Status & interaction colors
        ...['green', 'amber', 'gray', 'emerald', 'teal', 'indigo', 'purple', 'pink'].flatMap(c => [
            `bg-${c}-50`, `bg-${c}-100`, `bg-${c}-200`, `bg-${c}-300`, `bg-${c}-500`, `bg-${c}-600`,
            `text-${c}-400`, `text-${c}-500`, `text-${c}-600`, `text-${c}-700`, `text-${c}-800`,
            `border-${c}-200`, `border-${c}-300`,
        ]),
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Montserrat', ...defaultTheme.fontFamily.sans],
            },

            /* ── Brand & semantic colors ─────────────────── */
            colors: {
                navy: {
                    DEFAULT: '#000080',
                    50:  '#e6e6f2',
                    100: '#b3b3d9',
                    200: '#8080bf',
                    300: '#4d4da6',
                    400: '#26268f',
                    500: '#000080',
                    600: '#000070',
                    700: '#000060',
                    800: '#000050',
                    900: '#000040',
                },
                silver: {
                    bg:      '#EBEBEB',
                    'bg-alt': '#DEDEDE',
                    card:    '#FFFFFF',
                },
            },

            /* ── Accessible font sizing (18px base for elderly) ── */
            fontSize: {
                'xs':   ['0.875rem',  { lineHeight: '1.25rem'  }],  // 14px — ONLY for badges
                'sm':   ['1rem',      { lineHeight: '1.5rem'   }],  // 16px — smallest readable
                'base': ['1.125rem',  { lineHeight: '1.75rem'  }],  // 18px — body default
                'lg':   ['1.25rem',   { lineHeight: '1.75rem'  }],  // 20px
                'xl':   ['1.5rem',    { lineHeight: '2rem'     }],  // 24px
                '2xl':  ['1.875rem',  { lineHeight: '2.25rem'  }],  // 30px
                '3xl':  ['2.25rem',   { lineHeight: '2.5rem'   }],  // 36px
            },

            /* ── Standardized border radius ─────────────── */
            borderRadius: {
                'card':  '1.5rem',    // 24px — replaces inconsistent rounded-[24px]
                'card-lg': '2rem',    // 32px — hero cards
            },

            /* ── Custom animations ───────────────────────── */
            keyframes: {
                'fade-in': {
                    '0%':   { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'slide-up': {
                    '0%':   { opacity: '0', transform: 'translateY(16px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'scale-in': {
                    '0%':   { opacity: '0', transform: 'scale(0.95)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                'pulse-gentle': {
                    '0%, 100%': { opacity: '1' },
                    '50%':     { opacity: '0.7' },
                },
            },
            animation: {
                'fade-in':      'fade-in 0.3s ease-out forwards',
                'slide-up':     'slide-up 0.4s ease-out forwards',
                'scale-in':     'scale-in 0.2s ease-out forwards',
                'pulse-gentle': 'pulse-gentle 2s ease-in-out infinite',
            },

            /* ── Minimum touch-target sizing ─────────────── */
            minWidth: {
                'touch': '48px',
            },
            minHeight: {
                'touch': '48px',
            },
        },
    },

    plugins: [forms],
};