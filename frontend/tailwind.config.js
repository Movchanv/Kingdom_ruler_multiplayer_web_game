/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        parchment: {
          50: '#fbf7ee',
          100: '#f3e9d2',
          200: '#e7d3a8',
        },
        royal: {
          500: '#7c3aed',
          600: '#6d28d9',
          700: '#5b21b6',
        },
        iron: {
          700: '#3f3f46',
          800: '#27272a',
          900: '#18181b',
        },
        gold: {
          400: '#f5c542',
          500: '#e0a900',
        },
      },
      fontFamily: {
        heading: ['"Cinzel"', 'serif'],
        body: ['"Inter"', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
