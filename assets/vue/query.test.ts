import { describe, expect, it } from 'vitest'
import { toQuery, withQuery } from './query'

describe('toQuery', () => {
    it('lässt leere Felder weg', () => {
        expect(toQuery({ reise: 'italien', thema: '', jahr: '', schlagwort: 'kaffee' })).toBe('reise=italien&schlagwort=kaffee')
    })

    it('kodiert Sonderzeichen', () => {
        expect(toQuery({ jahr: 'ohne datum' })).toBe('jahr=ohne+datum')
    })
})

describe('withQuery', () => {
    it('hängt die Auswahl an die Adresse an', () => {
        expect(withQuery('/api/photos', { reise: 'italien' })).toBe('/api/photos?reise=italien')
    })

    it('ohne Auswahl bleibt die Adresse ohne Fragezeichen', () => {
        expect(withQuery('/photos', { reise: '' })).toBe('/photos')
    })
})
