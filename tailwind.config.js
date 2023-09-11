/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./system/typemill/author/**/*.{html,js,twig}"],
  theme: {
    extend: {
      screens: {
        'lg': '1024px', // You can adjust this breakpoint if needed
      },
      spacing: {
        // Define the 'half' spacing utility
        'half': '50%', // You can adjust this value as needed
      },
      width: {     
        'half': '48%',
        '54rem': '54rem',
      },
      opacity: {
        '0': '0',
      },
      visibility: ["group-hover"],
    },
  },
  plugins: [],
}