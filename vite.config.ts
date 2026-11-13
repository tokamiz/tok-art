import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'
import symfonyPlugin from 'vite-plugin-symfony'

export default defineConfig({
    plugins: [
        vue(),
        // Schreibt die Einstiegspunkte nach public/build/; Twig bindet sie über vite_entry_*_tags() ein.
        symfonyPlugin(),
    ],
    build: {
        rollupOptions: {
            input: {
                app: './assets/app.ts',
            },
        },
    },
    server: {
        // Im Docker-Container von außen erreichbar; der Browser lädt die Dateien über localhost:5180.
        host: '0.0.0.0',
        port: 5180,
        strictPort: true,
        origin: 'http://localhost:5180',
        cors: { origin: 'http://localhost:8000' },
    },
})
