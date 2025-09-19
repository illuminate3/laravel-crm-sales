/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./src/Resources/**/*.blade.php", "./src/Resources/**/*.js"],

    theme: {
        container: {
            center: true,

            screens: {
                "4xl": "1920px",
            },

            padding: {
                DEFAULT: "16px",
            },
        },

        screens: {
            sm: "525px",
            md: "768px",
            lg: "1024px",
            xl: "1240px",
            "2xl": "1440px",
            "3xl": "1680px",
            "4xl": "1920px",
        },

        extend: {
            colors: {
                brandColor: "var(--brand-color)",
                
                // Sales specific colors
                'sales-primary': '#0E90D9',
                'sales-success': '#10B981',
                'sales-warning': '#F59E0B',
                'sales-danger': '#EF4444',
                'sales-info': '#8B5CF6',
                
                // Performance colors
                'performance-excellent': '#10B981',
                'performance-good': '#3B82F6',
                'performance-average': '#F59E0B',
                'performance-poor': '#EF4444',
                
                // Achievement colors
                'achievement-gold': '#F59E0B',
                'achievement-silver': '#6B7280',
                'achievement-bronze': '#EA580C',
            },

            fontFamily: {
                inter: ['Inter'],
                icon: ['icomoon']
            },

            animation: {
                'fade-in': 'fadeIn 0.5s ease-in-out',
                'slide-up': 'slideUp 0.3s ease-out',
                'pulse-slow': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'bounce-slow': 'bounce 2s infinite',
            },

            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideUp: {
                    '0%': { transform: 'translateY(20px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
            },

            boxShadow: {
                'sales-card': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
                'sales-card-hover': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            },

            spacing: {
                '18': '4.5rem',
                '88': '22rem',
                '128': '32rem',
            },

            borderRadius: {
                'xl': '0.75rem',
                '2xl': '1rem',
                '3xl': '1.5rem',
            },

            zIndex: {
                '60': '60',
                '70': '70',
                '80': '80',
                '90': '90',
                '100': '100',
            },
        },
    },
    
    darkMode: 'class',

    plugins: [],

    safelist: [
        {
            pattern: /icon-/,
        },
        {
            pattern: /sales-/,
        },
        {
            pattern: /performance-/,
        },
        {
            pattern: /achievement-/,
        },
        // Safelist dynamic classes that might be used in Vue components
        'bg-yellow-100',
        'bg-gray-100', 
        'bg-orange-100',
        'bg-blue-100',
        'bg-green-100',
        'bg-red-100',
        'bg-purple-100',
        'text-yellow-800',
        'text-gray-800',
        'text-orange-800',
        'text-blue-800',
        'text-green-800',
        'text-red-800',
        'text-purple-800',
        'dark:bg-yellow-900',
        'dark:bg-gray-700',
        'dark:bg-orange-900',
        'dark:bg-blue-900',
        'dark:bg-green-900',
        'dark:bg-red-900',
        'dark:bg-purple-900',
        'dark:text-yellow-200',
        'dark:text-gray-200',
        'dark:text-orange-200',
        'dark:text-blue-200',
        'dark:text-green-200',
        'dark:text-red-200',
        'dark:text-purple-200',
    ]
};
