/** @type {import('tailwindcss').Config} */

const defaultTheme = require("tailwindcss/defaultTheme");

module.exports = {
  prefix: "tw-", // Important during transition to avoid namespace conflict
  content: ["./assets/**/*.{vue,js,ts,jsx,tsx}"],
  important: true, // Important during transition
  theme: {
    extend: {
      fontFamily: {
        sans: ["Inter var", ...defaultTheme.fontFamily.sans],
      },
    },
  },
  plugins: [],
  corePlugins: {
    preflight: false,
  },
};
