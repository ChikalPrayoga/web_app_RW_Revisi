/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#1E5B4F',
                    light: '#E8F0EE',
                    dark: '#17473E',
                },
                secondary: {
                    DEFAULT: '#C97A3D',
                    light: '#F7ECE3',
                },
                background: '#FAF9F6',
                surface: '#FFFFFF',
                border: '#E2DFD8',
                'text-primary': '#1F2624',
                'text-secondary': '#5C6B67',
                success: {
                    DEFAULT: '#2F8A5B',
                    light: '#EAF6EF',
                },
                warning: {
                    DEFAULT: '#D69A2D',
                    light: '#FBF2E1',
                },
                danger: {
                    DEFAULT: '#C0432E',
                    light: '#FBEAE7',
                },
                info: {
                    DEFAULT: '#3D7EA6',
                    light: '#EAF2F7',
                },
            },
            fontFamily: {
                display: ['Fraunces', 'serif'],
                sans: ['Inter', 'sans-serif'],
                body: ['Inter', 'sans-serif'],
                mono: ['"JetBrains Mono"', 'monospace'],
            },
            borderRadius: {
                sm: '8px',
                md: '12px',
                lg: '16px',
            },
            minHeight: {
                'touch': '44px',
            },
            minWidth: {
                'touch': '44px',
            },
        },
    },
    plugins: [],
};
