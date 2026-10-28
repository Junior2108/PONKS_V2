// =====================================================================
// Compilation des composants React du site.
//
//   npm run build  -> génère ../js/react/islands.js et islands.css
//   npm run dev    -> pareil, mais recompile à chaque modification
//
// Les fichiers générés ont toujours le même nom : les pages PHP les
// chargent directement, sans configuration en plus.
// =====================================================================

import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: '../js/react',
    emptyOutDir: true,
    rollupOptions: {
      input: 'src/main.jsx',
      output: {
        entryFileNames: 'islands.js',
        chunkFileNames: 'islands-[name].js',
        assetFileNames: 'islands.[ext]',
      },
    },
  },
})
