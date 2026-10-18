/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './app/**/*.php',
        './public/js/**/*.js',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
            colors: {
                sidebar: { DEFAULT: '#0f172a', 900: '#0b1222' },
                accent: {
                    50: '#eef2ff', 100: '#e0e7ff', 400: '#818cf8', 500: '#6366f1',
                    600: '#4f46e5', 700: '#4338ca',
                },
            },
        },
    },
};
