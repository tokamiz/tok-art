<template>
  <div class="stack">
    <form
      class="filters"
      @submit.prevent
    >
      <select
        v-for="field in gallery.filters"
        :key="field.name"
        v-model="selection[field.name]"
        :name="field.name"
        :aria-label="field.label"
        @change="load()"
      >
        <option value="">
          {{ field.allLabel }}
        </option>
        <option
          v-for="option in field.options"
          :key="option.value"
          :value="option.value"
          :disabled="!option.available"
        >
          {{ option.label }}
        </option>
      </select>
    </form>

    <button
      v-if="gallery.active"
      type="button"
      class="text-link"
      @click="reset"
    >
      Filter zurücksetzen
    </button>

    <p
      v-if="failed"
      class="field__error"
      role="alert"
    >
      Die Fotos konnten nicht geladen werden. Bitte versuch es noch einmal.
    </p>

    <div
      v-if="gallery.photos.length > 0"
      class="photo-grid"
      :class="{ 'is-loading': loading }"
      :aria-busy="loading"
    >
      <PhotoTile
        v-for="photo in gallery.photos"
        :key="photo.id"
        :photo="photo"
      />
    </div>
    <p
      v-else
      class="muted"
    >
      Keine Fotos gefunden.
    </p>
  </div>
</template>

<script setup lang="ts">
/**
 * Fotogalerie mit vier facettierten Filtern, ohne Neuladen der Seite.
 *
 * Die Logik bleibt auf dem Server: Bei jeder Änderung fragt die Komponente GET /api/photos
 * und bekommt sichtbare Fotos und wählbare Optionen zurück. Die Adresse im Browser wird
 * mitgeführt: Die Auswahl bleibt beim Neuladen und Teilen erhalten, „Zurück" nimmt die letzte Änderung zurück.
 */
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import PhotoTile from './PhotoTile.vue'
import { withQuery } from './query'
import type { Gallery } from './types'

const props = defineProps<{
  initial: Gallery
  endpoint: string
}>()

const gallery = ref<Gallery>(props.initial)
const selection = reactive<Record<string, string>>(selectionOf(props.initial))
const loading = ref(false)
const failed = ref(false)

function selectionOf(data: Gallery): Record<string, string> {
  return Object.fromEntries(data.filters.map(field => [field.name, field.value]))
}

/** Zählt mit, damit eine langsame ältere Antwort keine neuere überschreibt. */
let request = 0

/**
 * Lädt Fotos und Filter für die aktuelle Auswahl.
 * push: neuer Eintrag im Browserverlauf, damit „Zurück" die letzte Filteränderung rückgängig macht.
 */
async function load(push = true) {
  const current = ++request
  loading.value = true
  failed.value = false
  try {
    const response = await fetch(withQuery(props.endpoint, selection), { headers: { Accept: 'application/json' } })
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }
    const data = await response.json() as Gallery
    if (current !== request) return

    gallery.value = data
    Object.assign(selection, selectionOf(data))
    if (push) {
      history.pushState(null, '', withQuery(location.pathname, selection))
    }
  }
  catch {
    if (current === request) failed.value = true
  }
  finally {
    if (current === request) loading.value = false
  }
}

function reset() {
  for (const name of Object.keys(selection)) {
    selection[name] = ''
  }
  load()
}

// „Zurück" im Browser: Auswahl aus der Adresse übernehmen und neu laden
function onPopState() {
  const params = new URLSearchParams(location.search)
  for (const name of Object.keys(selection)) {
    selection[name] = params.get(name) ?? ''
  }
  load(false)
}

onMounted(() => window.addEventListener('popstate', onPopState))
onBeforeUnmount(() => window.removeEventListener('popstate', onPopState))
</script>
