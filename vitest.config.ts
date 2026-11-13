import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

// Eigene Konfiguration für die Vue-Tests, ohne das Symfony-Plugin (das schreibt nach public/build/).
export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'happy-dom',
        include: ['assets/**/*.test.ts'],
    },
})
