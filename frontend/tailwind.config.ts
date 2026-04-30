import type { Config } from 'tailwindcss';

export default {
  darkMode: ['class'],
  content: [
    './app/**/*.{js,ts,jsx,tsx,mdx}',
    './components/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      colors: {
        // Keeps styling consistent across the app
        zinc: {
          950: '#09090b'
        }
      }
    }
  },
  plugins: []
} satisfies Config;

