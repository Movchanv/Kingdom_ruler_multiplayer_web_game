/**
 * Positions des pays sur la carte du monde, en % de l'image (world-map.png).
 * Clé = slug du pays côté API. À terme, chaque pays aura aussi son image de
 * surbrillance (superposée au survol) : on la déclarera ici (`overlay`).
 */
export const WORLD_MAP_IMAGE = '/world-map.png'

export const COUNTRY_POSITIONS = {
  'france-medievale': { x: 22, y: 58 },
}

/** Position de repli pour un pays sans coordonnées connues. */
export const DEFAULT_POSITION = { x: 50, y: 50 }

export function positionFor(slug) {
  return COUNTRY_POSITIONS[slug] ?? DEFAULT_POSITION
}

/** Image de la carte de chaque pays (villes positionnées dessus). */
export const COUNTRY_MAPS = {
  'france-medievale': '/map-france.png',
}

export function countryMapFor(slug) {
  return COUNTRY_MAPS[slug] ?? null
}

/**
 * Calque plein cadre de chaque pays, superposé sur la carte du monde et aligné
 * pixel-perfect (même dimensions que world-map.png, 1402×1122). Chaque calque
 * ne dessine que sa région ; le reste est transparent.
 */
export const COUNTRY_OVERLAYS = {
  'france-medievale': '/overlays/france.png',
}

export function countryOverlayFor(slug) {
  return COUNTRY_OVERLAYS[slug] ?? null
}

/** Ratios largeur/hauteur des cartes (pour un alignement exact des calques). */
export const WORLD_MAP_RATIO = '1402 / 1122'
export const COUNTRY_MAP_RATIO = '3 / 2'

/**
 * Les coordonnées des villes/POI côté API sont exprimées sur une grille de
 * 1000 × 1000 ; on les convertit en % de l'image.
 */
export const MAP_UNITS = 1000

export function toPercent(units) {
  return (units / MAP_UNITS) * 100
}
