/**
 * Baut aus der Filterauswahl den Teil der Adresse nach dem Fragezeichen.
 * Leere Felder („Alle …") fallen weg, damit die Adresse kurz bleibt.
 *
 * toQuery({ reise: 'italien', thema: '' }) → "reise=italien"
 */
export function toQuery(selection: Record<string, string>): string {
    const params = new URLSearchParams()
    for (const [name, value] of Object.entries(selection)) {
        if (value !== '') {
            params.set(name, value)
        }
    }
    return params.toString()
}

/** Hängt die Auswahl an eine Adresse an: withQuery('/photos', …) → "/photos?reise=italien" */
export function withQuery(path: string, selection: Record<string, string>): string {
    const query = toQuery(selection)
    return query === '' ? path : `${path}?${query}`
}
