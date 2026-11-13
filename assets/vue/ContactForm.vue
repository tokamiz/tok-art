<template>
  <div
    v-if="sent"
    class="stack"
  >
    <p role="status">
      Danke für deine Nachricht! Ich melde mich bald.
    </p>
    <button
      type="button"
      class="button button--secondary"
      @click="reset"
    >
      Neue Nachricht schreiben
    </button>
  </div>

  <form
    v-else
    class="form"
    @submit.prevent="submit"
  >
    <!-- Unsichtbares Feld gegen Spam: Menschen sehen es nicht, Bots füllen es aus. -->
    <div
      class="visually-hidden"
      aria-hidden="true"
    >
      <label :for="honeypot">Website</label>
      <input
        :id="honeypot"
        v-model="trap"
        type="text"
        tabindex="-1"
        autocomplete="off"
      >
    </div>

    <div class="field">
      <label for="name">Name</label>
      <input
        id="name"
        v-model="form.name"
        type="text"
        required
        :maxlength="maxName"
        autocomplete="name"
        :aria-invalid="!!errors.name"
        :aria-describedby="errors.name ? 'name-error' : undefined"
        @input="clearError('name')"
      >
      <p
        v-if="errors.name"
        id="name-error"
        class="field__error"
      >
        {{ errors.name }}
      </p>
    </div>

    <div class="field">
      <label for="email">E-Mail</label>
      <input
        id="email"
        v-model="form.email"
        type="email"
        required
        autocomplete="email"
        :aria-invalid="!!errors.email"
        :aria-describedby="errors.email ? 'email-error' : undefined"
        @input="clearError('email')"
      >
      <p
        v-if="errors.email"
        id="email-error"
        class="field__error"
      >
        {{ errors.email }}
      </p>
    </div>

    <div class="field">
      <label for="message">Nachricht</label>
      <textarea
        id="message"
        v-model="form.message"
        rows="6"
        required
        :maxlength="maxMessage"
        :aria-invalid="!!errors.message"
        :aria-describedby="errors.message ? 'message-error' : 'message-count'"
        @input="clearError('message')"
      />
      <p
        id="message-count"
        class="field__hint"
      >
        {{ form.message.length }} / {{ maxMessage }}
      </p>
      <p
        v-if="errors.message"
        id="message-error"
        class="field__error"
      >
        {{ errors.message }}
      </p>
    </div>

    <p class="muted small">
      Deine Angaben werden nur gespeichert, um deine Nachricht zu beantworten, und nicht weitergegeben.
    </p>

    <p
      v-if="failure"
      class="field__error"
      role="alert"
    >
      {{ failure }}
    </p>

    <button
      type="submit"
      class="button"
      :disabled="sending"
    >
      {{ sending ? 'Wird gesendet …' : 'Senden' }}
    </button>
  </form>
</template>

<script setup lang="ts">
/**
 * Kontaktformular, sendet ohne Neuladen an POST /api/contact.
 *
 * Verbindlich prüft der Server (ContactValidator); seine Meldungen erscheinen unter dem jeweiligen Feld
 * und verschwinden, sobald man das Feld ändert. Der Browser prüft vorab (required, type="email", maxlength).
 */
import { reactive, ref } from 'vue'
import type { ContactErrors, ContactField } from './types'

const props = defineProps<{
  endpoint: string
  csrfToken: string
  honeypot: string
  maxName: number
  maxMessage: number
  initial: Record<ContactField, string>
  /** PHP liefert ohne Fehler ein leeres Array statt eines Objekts. */
  initialErrors: ContactErrors | []
}>()

const form = reactive({ ...props.initial })
const errors = ref<ContactErrors>(Array.isArray(props.initialErrors) ? {} : props.initialErrors)
const trap = ref('')
const sending = ref(false)
const sent = ref(false)
const failure = ref('')

const GENERIC_FAILURE = 'Die Nachricht konnte nicht gesendet werden. Bitte versuch es später noch einmal.'

function clearError(field: ContactField) {
  if (errors.value[field]) {
    errors.value = { ...errors.value, [field]: undefined }
  }
}

async function submit() {
  sending.value = true
  failure.value = ''
  try {
    const response = await fetch(props.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ ...form, _token: props.csrfToken, [props.honeypot]: trap.value }),
    })

    if (response.status === 201) {
      sent.value = true
      return
    }

    const data = await response.json() as { errors?: ContactErrors, message?: string }
    if (data.errors) {
      errors.value = data.errors
    }
    else {
      failure.value = data.message ?? GENERIC_FAILURE
    }
  }
  catch {
    failure.value = GENERIC_FAILURE
  }
  finally {
    sending.value = false
  }
}

/** Nach dem Senden: leeres Formular für die nächste Nachricht. */
function reset() {
  Object.assign(form, { name: '', email: '', message: '' })
  errors.value = {}
  failure.value = ''
  sent.value = false
}
</script>
