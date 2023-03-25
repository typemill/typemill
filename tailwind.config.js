/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./system/typemill/author/**/*.{html,js,twig}"],
  theme: {
    extend: {
      width: {
        'half': '48%',
      },
      opacity: {
        '0': '0',
      },
      visibility: ["group-hover"],
    },
  },
  plugins: [],
}