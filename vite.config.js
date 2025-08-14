import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/empty.js'], // entrada dummy
      refresh: false,
    }),
  ],
});
