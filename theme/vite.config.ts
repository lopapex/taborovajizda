import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { v4wp } from '@kucrut/vite-for-wp';

export default defineConfig({
  base: './',
  publicDir: false,
  plugins: [
    tailwindcss(),
    v4wp({
      input: 'resources/ts/app.ts',
      outDir: 'public/build',
    }),
  ],
  build: {
    manifest: true,
    sourcemap: false,
    emptyOutDir: true,
  },
});
