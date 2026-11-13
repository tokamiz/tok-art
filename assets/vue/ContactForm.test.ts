import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import ContactForm from './ContactForm.vue'

const props = {
    endpoint: '/api/contact',
    csrfToken: 'token-123',
    honeypot: 'website',
    maxName: 100,
    maxMessage: 2000,
    initial: { name: '', email: '', message: '' },
    initialErrors: [] as [],
}

function answer(status: number, body: unknown) {
    return vi.fn().mockResolvedValue(new Response(JSON.stringify(body), { status }))
}

async function fillAndSend(wrapper: ReturnType<typeof mount>) {
    await wrapper.get('#name').setValue('Anna')
    await wrapper.get('#email').setValue('anna@example.org')
    await wrapper.get('#message').setValue('Schöne Fotos!')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
}

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('ContactForm', () => {
    it('schickt die Nachricht mit CSRF-Token als JSON und bedankt sich', async () => {
        const fetchMock = answer(201, { ok: true })
        vi.stubGlobal('fetch', fetchMock)
        const wrapper = mount(ContactForm, { props })

        await fillAndSend(wrapper)

        const [url, options] = fetchMock.mock.calls[0] as [string, RequestInit]
        expect(url).toBe('/api/contact')
        expect(JSON.parse(options.body as string)).toEqual({
            name: 'Anna',
            email: 'anna@example.org',
            message: 'Schöne Fotos!',
            _token: 'token-123',
            website: '',
        })
        expect(wrapper.text()).toContain('Danke für deine Nachricht!')
    })

    it('zeigt die Fehler des Servers je Feld und entfernt sie beim Tippen', async () => {
        vi.stubGlobal('fetch', answer(422, { errors: { email: 'Bitte gib eine gültige E-Mail-Adresse an.' } }))
        const wrapper = mount(ContactForm, { props })

        await fillAndSend(wrapper)

        expect(wrapper.get('#email-error').text()).toContain('gültige E-Mail-Adresse')
        expect(wrapper.get('#email').attributes('aria-invalid')).toBe('true')

        await wrapper.get('#email').setValue('anna@example.de')
        expect(wrapper.find('#email-error').exists()).toBe(false)
    })

    it('zeigt eine allgemeine Meldung, wenn der Server nicht erreichbar ist', async () => {
        vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('offline')))
        const wrapper = mount(ContactForm, { props })

        await fillAndSend(wrapper)

        expect(wrapper.get('[role="alert"]').text()).toContain('nicht gesendet werden')
    })

    it('„Neue Nachricht schreiben" zeigt ein leeres Formular', async () => {
        vi.stubGlobal('fetch', answer(201, { ok: true }))
        const wrapper = mount(ContactForm, { props })
        await fillAndSend(wrapper)

        await wrapper.get('button').trigger('click')

        expect((wrapper.get('#name').element as HTMLInputElement).value).toBe('')
        expect((wrapper.get('#message').element as HTMLTextAreaElement).value).toBe('')
    })

    it('übernimmt Eingaben und Fehler einer Seite, die ohne JavaScript abgeschickt wurde', () => {
        const wrapper = mount(ContactForm, {
            props: { ...props, initial: { name: 'Anna', email: 'kaputt', message: '' }, initialErrors: { email: 'Ungültig' } },
        })

        expect((wrapper.get('#name').element as HTMLInputElement).value).toBe('Anna')
        expect(wrapper.get('#email-error').text()).toBe('Ungültig')
    })
})
