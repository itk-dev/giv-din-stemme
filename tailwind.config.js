/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./web/themes/custom/giv_din_stemme_theme/**/*.{twig,html,js}"],
  safelist: [
  ],
  theme: {
    extend: {
      colors: {
        orange: {
          600: '#EF4123'
        },
      },
    }
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}

