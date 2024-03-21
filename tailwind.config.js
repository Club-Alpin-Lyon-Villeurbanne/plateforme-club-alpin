/** @type {import('tailwindcss').Config} */
module.exports = {
  prefix: "tw-", // Important during transition to avoid namespace conflict
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
    "./legacy/**/*.php",
  ],
  important: true, // Important during transition
  theme: {
    extend: {},
  },
  plugins: [],
  corePlugins: {
    preflight: false,
  },
};
