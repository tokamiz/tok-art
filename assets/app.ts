/*
 * Einstieg für CSS und Vue (gebaut mit Vite, eingebunden in base.html.twig).
 *
 * Vue-Inseln: Twig rendert jede Seite vollständig. Elemente mit data-vue-component bekommen danach
 * eine Vue-Komponente, die den Inhalt ersetzt. Die Startdaten stehen als JSON in data-props.
 * Ohne JavaScript bleibt das HTML von Twig – die Seite funktioniert trotzdem.
 */
import './styles/app.css'
import { createApp, type Component } from 'vue'

/** Neue Inseln hier eintragen. Jede Komponente wird erst geladen, wenn die Seite sie braucht. */
const islands: Record<string, () => Promise<{ default: Component }>> = {
    PhotoGallery: () => import('./vue/PhotoGallery.vue'),
    ContactForm: () => import('./vue/ContactForm.vue'),
}

for (const element of document.querySelectorAll<HTMLElement>('[data-vue-component]')) {
    const load = islands[element.dataset.vueComponent ?? '']
    if (!load) {
        console.warn(`Unbekannte Vue-Komponente: ${element.dataset.vueComponent}`)
        continue
    }
    const props = JSON.parse(element.dataset.props ?? '{}') as Record<string, unknown>

    load().then(({ default: component }) => {
        createApp(component, props).mount(element)
    })
}
