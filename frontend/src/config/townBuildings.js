export const BUILDING_META = {
  town_hall: {
    icon: '🏛️',
    special: 'laws',
    description: "Le cœur du royaume : on y vote les lois qui renforcent la ville.",
  },
  farm: {
    icon: '🌾',
    action: 'harvest_food',
    actionLabel: 'Récolter du blé',
    produces: 'food',
    description: 'Les champs nourrissent la population et les soldats.',
  },
  forest: {
    icon: '🌲',
    action: 'harvest_wood',
    actionLabel: 'Couper du bois',
    produces: 'wood',
    description: 'Le bois alimente les chantiers de la ville.',
  },
  mine: {
    icon: '⛏️',
    action: 'harvest_stone',
    actionLabel: 'Extraire de la pierre',
    produces: 'stone',
    description: 'La pierre des montagnes bâtit les murailles.',
  },
  barracks: {
    icon: '⚔️',
    action: 'recruit_soldiers',
    actionLabel: 'Recruter des soldats',
    produces: 'soldiers',
    description: 'Les troupes défendent la ville… mais réclament leur solde.',
  },
  market: {
    icon: '🪙',
    action: 'mine_gold',
    actionLabel: "Percevoir l'or du marché",
    produces: 'gold',
    description: "Le commerce remplit les coffres communs.",
  },
}

export function buildingMeta(key) {
  return BUILDING_META[key] ?? { icon: '🏚️', description: '' }
}

export const BUILDING_IMAGES = {
  town_hall: '/buildings/hotel-de-ville.png',
  farm: '/buildings/ferme.png',
  forest: '/buildings/foret.png',
  mine: '/buildings/mine-de-pierre.png',
  barracks: '/buildings/caserne.png',
  market: '/buildings/place-du-marche.png',
}

export function buildingImageFor(key) {
  return BUILDING_IMAGES[key] ?? null
}

export const ADVENTURE_IMAGE = '/buildings/aventure.png'

export const TOWN_EMBLEMS = {
  Paris: '/towns/paris.png',
}

export function townEmblemFor(name) {
  return TOWN_EMBLEMS[name] ?? null
}

export const RESOURCE_ICONS = {
  gold: '🪙',
  food: '🍞',
  wood: '🪵',
  stone: '🪨',
  soldiers: '⚔️',
  iron: '⛓️',
  coal: '🧱',
}

export function resourceIcon(key) {
  return RESOURCE_ICONS[key] ?? '📦'
}

const RESOURCE_NAMES = {
  gold: 'or',
  food: 'nourriture',
  wood: 'bois',
  stone: 'pierre',
  soldiers: 'soldats',
  iron: 'fer',
  coal: 'charbon',
  loyalty: 'loyauté',
  free_action: 'action offerte',
}

export function resourceLabel(key) {
  const name = RESOURCE_NAMES[key]

  return name ? name.charAt(0).toUpperCase() + name.slice(1) : key
}

export function bonusLabel(key, value) {
  if (key.endsWith('_bonus_pct')) {
    const resource = key.slice(0, -'_bonus_pct'.length)

    return `+${value} % de production de ${RESOURCE_NAMES[resource] ?? resource}`
  }

  if (key.endsWith('_per_day')) {
    const resource = key.slice(0, -'_per_day'.length)

    return `+${value} ${RESOURCE_NAMES[resource] ?? resource}/jour`
  }

  return `${key} : ${value}`
}

export const TOWN_IMAGES = {
  Paris: '/town-paris.png',
}

export function townImageFor(name) {
  return TOWN_IMAGES[name] ?? null
}


export const TOWN_IMAGE_RATIO = '3 / 2'


export const ADVENTURE_SPOTS = {
  Paris: { x: 140, y: 690 },
}

const DEFAULT_ADVENTURE_SPOT = { x: 140, y: 690 }

export function adventureSpotFor(name) {
  return ADVENTURE_SPOTS[name] ?? DEFAULT_ADVENTURE_SPOT
}
