/**
 * Form der Daten von GET /api/photos.
 * Entspricht den @phpstan-type-Angaben in src/Presentation/PhotoGalleryPresenter.php.
 */
export interface FilterOption {
    value: string
    label: string
    available: boolean
}

export interface FilterField {
    /** Name des Parameters in der Adresse, z. B. "reise" */
    name: string
    label: string
    allLabel: string
    value: string
    options: FilterOption[]
}

export interface Photo {
    id: string
    src: string
    alt: string
    place: string | null
    entryUrl: string | null
    trip: { title: string, url: string } | null
    topics: { name: string, url: string }[]
}

export interface Gallery {
    filters: FilterField[]
    active: boolean
    photos: Photo[]
}

/** Antworten von POST /api/contact */
export type ContactField = 'name' | 'email' | 'message'
export type ContactErrors = Partial<Record<ContactField, string>>
