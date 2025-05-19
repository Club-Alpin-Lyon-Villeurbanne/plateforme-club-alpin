import { fileURLToPath, URL } from "node:url";
import vue from "@vitejs/plugin-vue";
import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";
import vueDevTools from "vite-plugin-vue-devtools";

export default defineConfig({
  plugins: [
    vue({
      script: {
        defineModel: true,
      },
      template: {
        compilerOptions: {
          isCustomElement: (tag) => tag.startsWith("sl-"),
        },
      },
    }),
    symfonyPlugin(),
    vueDevTools(),
  ],
  build: {
    rollupOptions: {
      input: {
        "expense-report-form": "./assets/expense-report-form/main.js",
        tailwind: "./assets/tailwind.js",
        participants: './assets/participants.js',
        commission_switch: './assets/commission-switch.js',
        autocomplete_address: './assets/autocomplete-address.js',
      },
    },
  },
});
