/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/View/Components/**/*.php',
        './storage/framework/views/*.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    50:  '#eef4fc',
                    100: '#d9e6f8',
                    200: '#b5cdf1',
                    300: '#84aae6',
                    400: '#4f80d6',
                    500: '#2f62c4',
                    600: '#1e4fa8',
                    700: '#1a4189',
                    800: '#16356f',
                    900: '#122b59',
                    950: '#0a1833',
                },
            },
            fontFamily: {
                sans: ['"Inter"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                mono: ['"IBM Plex Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};