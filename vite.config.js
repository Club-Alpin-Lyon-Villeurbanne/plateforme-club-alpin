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
        modal: "./assets/js/modal/modal.js",
        modal_css: "./assets/styles/modal.css",
        "styles": "./assets/styles/styles.css",
        "base-styles": "./assets/styles/base.css",
        "common-styles": "./assets/styles/common.css",
        "print-styles": "./assets/styles/print.css",
        "fonts": "./assets/fonts/stylesheet.css",
        "admin-styles": "./assets/styles/admin.css",
        "admin-login": "./assets/styles/loginadmin.css",
        participants: './assets/participants.js',
        commission_switch: './assets/commission-switch.js',
        ckeditor: './assets/ckeditor.js',
      },
    },
  },
});
