/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./web/themes/custom/giv_din_stemme_theme/**/*.{twig,html,js}",
    "./web/modules/custom/**/*.{twig,html,js}",
  ],
  safelist: [
    "col-span-1",
    "col-span-2",
    "gap-x-6",
    "justify-end",
    "-mx-5"
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

