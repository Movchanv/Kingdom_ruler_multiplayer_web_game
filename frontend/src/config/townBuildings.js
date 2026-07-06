/**
 * Habillage front des bâtiments de ville : icône provisoire (remplacée plus
 * tard par des vignettes PNG), action de production associée et libellés.
 * La clé correspond à `buildings.key` côté API.
 */
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

/** Icônes des ressources (barre de la ville, coûts, gains). */
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

/** Noms français des ressources (libellés de bonus). */
const RESOURCE_NAMES = {
  gold: 'or',
  food: 'nourriture',
  wood: 'bois',
  stone: 'pierre',
  soldiers: 'soldats',
  loyalty: 'loyauté',
}

/** Libellé lisible d'un effet de loi/bâtiment ("gold_bonus_pct: 10" → "+10 % de production d'or"). */
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

/** Image de fond de chaque ville (clé = nom de la ville côté API). */
export const TOWN_IMAGES = {
  Paris: '/town-paris.png',
}

export function townImageFor(name) {
  return TOWN_IMAGES[name] ?? null
}

/** Ratio largeur/hauteur des images de ville (town-paris.png : 1536×1024). */
export const TOWN_IMAGE_RATIO = '3 / 2'

/**
 * Emplacement du départ en aventure : les portes de la ville (grille 1000 × 1000,
 * clé = nom de la ville). Sur town-paris.png : la porte principale près du pont.
 */
export const ADVENTURE_SPOTS = {
  Paris: { x: 140, y: 690 },
}

const DEFAULT_ADVENTURE_SPOT = { x: 140, y: 690 }

export function adventureSpotFor(name) {
  return ADVENTURE_SPOTS[name] ?? DEFAULT_ADVENTURE_SPOT
}
