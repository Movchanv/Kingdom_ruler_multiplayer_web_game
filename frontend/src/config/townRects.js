export const TOWN_RECTS = {
  Paris: {
    town_hall: { x: 24.28, y: 12.99, w: 20.7, h: 21.88 },
    barracks: { x: 8.14, y: 16.02, w: 16.02, h: 20.21 },
    market: { x: 18.88, y: 46.68, w: 12.3, h: 16.89 },
    mine: { x: 64.32, y: 2.93, w: 27.34, h: 26.95 },
    farm: { x: 63.41, y: 39.06, w: 32.88, h: 25.59 },
    forest: { x: 67.38, y: 65.14, w: 30.27, h: 28.12 },
    adventure: { x: 8.07, y: 55.57, w: 12.24, h: 25.98 },
  },
}

export function rectFor(townName, key) {
  return TOWN_RECTS[townName]?.[key] ?? null
}
