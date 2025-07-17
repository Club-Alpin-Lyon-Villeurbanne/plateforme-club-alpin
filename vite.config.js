import { fileURLToPath, URL } from "node:url";
import vue from "@vitejs/plugin-vue";
import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";
import vueDevTools from "vite-plugin-vue-devtools";

// For resolving CKEditor 5 SVG icons
// import { createRequire } from 'node:module';
// const require = createRequire(import.meta.url);
// const ckeditor5 = require.resolve('@ckeditor/ckeditor5-build-classic');
// const ckeditor5 = require.resolve('@ckeditor/ckeditor5-dev-utils');
// const ckeditor5Dir = ckeditor5.substring(0, ckeditor5.indexOf('node_modules')) + 'node_modules/@ckeditor';

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
        ckeditor: './assets/ckeditor-init.js',
        autocomplete_communes: './assets/autocomplete-communes.js',
      },
      output: {
        manualChunks: undefined,
      },
    },
  },
  // resolve: {
  //   alias: {
  //     '@ckeditor/ckeditor5-build-classic': ckeditor5,
  //   }
  // },
  // optimizeDeps: {
  //   include: ['@ckeditor/ckeditor5-build-classic'],
  // },
  // // Handle CKEditor's SVG icons
  // server: {
  //   fs: {
  //     allow: ['.', ckeditor5Dir]
  //   }
  // }
  // resolve: {
  //   alias: {
  //     '@': fileURLToPath(new URL('./assets', import.meta.url)),
  //   }
  // },
  // optimizeDeps: {
  //   include: ['ckeditor5'],
  // },
  // // Handle CKEditor's SVG icons
  // server: {
  //   fs: {
  //     allow: ['.', ckeditor5Dir]
  //   }
  // }
});
