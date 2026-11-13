<template>
  <div class="tile">
    <a
      v-if="photo.entryUrl"
      :href="photo.entryUrl"
    >
      <img
        :src="photo.src"
        :alt="photo.alt"
        class="tile-image"
      >
    </a>
    <img
      v-else
      :src="photo.src"
      :alt="photo.alt"
      class="tile-image"
    >

    <div
      ref="info"
      class="tile-info"
    >
      <button
        type="button"
        class="tile-info__button"
        :aria-expanded="open"
        aria-label="Infos zum Foto"
        @click="open = !open"
      >
        i
      </button>

      <div
        v-if="open"
        class="tile-info__panel"
      >
        <p v-if="photo.place">
          <span class="muted">Ort:</span>{{ photo.place }}
        </p>
        <p v-if="photo.trip">
          <span class="muted">Reise:</span>
          <a
            :href="photo.trip.url"
            class="text-link"
          >{{ photo.trip.title }}</a>
        </p>
        <div v-if="photo.topics.length > 0">
          <span class="muted">Themen:</span>
          <ul class="inline-list">
            <li
              v-for="topic in photo.topics"
              :key="topic.url"
            >
              <a
                :href="topic.url"
                class="text-link"
              >{{ topic.name }}</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Eine Kachel der Fotogalerie mit Info-Fenster (wie PhotoTile.vue im Nuxt-Projekt).
 * Jede Kachel hat ihren eigenen Zustand `open`.
 */
import { onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue'
import type { Photo } from './types'

defineProps<{ photo: Photo }>()

const open = ref(false)
const info = useTemplateRef<HTMLElement>('info')

// Schließen bei Klick daneben oder mit Escape
function onClick(event: MouseEvent) {
  if (open.value && !info.value?.contains(event.target as Node)) {
    open.value = false
  }
}
function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', onClick)
  document.addEventListener('keydown', onKeydown)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onClick)
  document.removeEventListener('keydown', onKeydown)
})
</script>
