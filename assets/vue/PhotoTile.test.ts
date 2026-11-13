import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import PhotoTile from './PhotoTile.vue'
import type { Photo } from './types'

const photo: Photo = {
    id: 'athen',
    src: '/media/athen.svg',
    alt: 'Athen, Griechenland',
    place: 'Athen, Griechenland',
    entryUrl: '/entries/athen',
    trip: { title: 'Griechenland', url: '/trips/griechenland' },
    topics: [{ name: 'Kultur', url: '/topics/kultur' }, { name: 'Städte', url: '/topics/staedte' }],
}

describe('PhotoTile', () => {
    it('verlinkt das Foto mit seinem Eintrag', () => {
        const wrapper = mount(PhotoTile, { props: { photo } })

        expect(wrapper.find('a').attributes('href')).toBe('/entries/athen')
    })

    it('zeigt ein Foto ohne Eintrag ohne Link', () => {
        const wrapper = mount(PhotoTile, { props: { photo: { ...photo, entryUrl: null } } })

        expect(wrapper.find('a').exists()).toBe(false)
        expect(wrapper.find('img').exists()).toBe(true)
    })

    it('öffnet und schließt das Info-Fenster', async () => {
        const wrapper = mount(PhotoTile, { props: { photo } })
        const button = wrapper.get('button')

        expect(wrapper.find('.tile-info__panel').exists()).toBe(false)

        await button.trigger('click')
        const panel = wrapper.get('.tile-info__panel')
        expect(button.attributes('aria-expanded')).toBe('true')
        expect(panel.text()).toContain('Athen, Griechenland')
        expect(panel.text()).toContain('Griechenland')
        expect(panel.findAll('li')).toHaveLength(2)

        await button.trigger('click')
        expect(wrapper.find('.tile-info__panel').exists()).toBe(false)
    })

    it('lässt Zeilen ohne Angabe weg', async () => {
        const wrapper = mount(PhotoTile, { props: { photo: { ...photo, place: null, trip: null, topics: [] } } })

        await wrapper.get('button').trigger('click')

        expect(wrapper.get('.tile-info__panel').text()).toBe('')
    })
})
