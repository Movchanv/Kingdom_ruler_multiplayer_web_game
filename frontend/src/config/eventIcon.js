import { iconExists } from '@/config/icons'

/**
 * Les evenements portent leur icone en base de donnees : elle est saisie
 * depuis l'espace d'administration, pas declaree dans le code. Les lignes
 * existantes contiennent des emoji ; cette table les traduit a l'affichage,
 * ce qui evite une migration et garde les anciennes parties lisibles.
 *
 * Les nouvelles saisies attendent directement un nom d'icone.
 */
const PAR_EMOJI = {
  '🏴': 'raid',
  '🪓': 'pillage',
  '🌵': 'secheresse',
  '🔥': 'incendie',
  '⚒': 'forge',
  '🛡': 'defense',
  '⚔': 'soldats',
  '🏰': 'chateau',
  '📜': 'loi',
  '💰': 'or',
  '🌾': 'ble',
}

/** Le selecteur de variante emoji (U+FE0F) est invisible mais casse l'egalite. */
function nu(valeur) {
  return valeur.replace(/\uFE0F/g, '').trim()
}

/**
 * @param {string|null|undefined} valeur  nom d'icone, ou emoji hérité
 * @returns {string|null}  nom d'icone connu, sinon null
 */
export function eventIcon(valeur) {
  if (!valeur) {
    return null
  }

  const propre = nu(valeur)

  if (iconExists(propre)) {
    return propre
  }

  return PAR_EMOJI[propre] ?? null
}
