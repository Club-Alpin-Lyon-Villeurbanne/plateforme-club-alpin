/** @type {import('tailwindcss').Config} */
module.exports = {
  prefix: "tw-", // Important during transition to avoid namespace conflict
  content: ["./assets/**/*.{vue,js,ts,jsx,tsx}"],
  important: true, // Important during transition
  theme: {
    extend: {},
  },
  plugins: [],
  corePlugins: {
    preflight: false,
  },
};
