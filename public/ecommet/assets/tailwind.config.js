/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./*.html"],
  theme: {
    container: {
      center: true,
      padding: '1rem',
      screens: {
        '2xl': '1440px',
      },
    },
    extend: {
      colors: {
        primary: 'var(--color-primary)',
        secondary: 'var(--color-secondary)',
        success: 'var(--color-success)',
        purple: 'var(--color-purple)',
        'gray-darkest': 'var(--color-gray-darkest)',
        'gray-dark': 'var(--color-gray-dark)',
        'gray-medium': 'var(--color-gray-medium)',
        'gray-light': 'var(--color-gray-light)',
        'gray-lighter': 'var(--color-gray-lighter)',
        'gray-lightest': 'var(--color-gray-lightest)',
        'sale-light': 'var(--color-sale-light)',
        'yellow-light': 'var(--color-yellow-light)'
      },
      fontFamily: {
        main: 'var(--font-main)'
      }
    },
  },
  plugins: [],
}
