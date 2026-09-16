/**
 * Préchargement des images de carte.
 *
 * Sans cela, le navigateur peint les grandes images au fil de leur
 * téléchargement : la carte apparaît par bandes, du haut vers le bas. On les
 * télécharge et on les décode d'abord, on ne les affiche qu'ensuite.
 */

/** Délai au-delà duquel on affiche la carte même si tout n'est pas arrivé. */
const DELAI_MAX = 15000

function precharger(src) {
  return new Promise((resolve) => {
    const img = new Image()
    const fini = (ok) => resolve({ src, ok })

    img.onload = () => {
      // onload dit « reçue », decode() dit « prête à être peinte » : c'est
      // cette seconde garantie qui supprime l'affichage progressif.
      if (typeof img.decode !== 'function') {
        fini(true)
        return
      }

      img.decode().then(
        () => fini(true),
        () => fini(true), // décodage refusé : on affichera quand même
      )
    }
    img.onerror = () => fini(false)
    img.src = src
  })
}

/**
 * @param {Array<string|null|undefined>} sources  images à précharger
 * @param {{ onProgress?: (ratio: number) => void, timeout?: number }} options
 * @returns {Promise<Array<{ src: string, ok: boolean }>>}
 */
export function preloadImages(sources, { onProgress, timeout = DELAI_MAX } = {}) {
  const liste = [...new Set(sources.filter(Boolean))]

  if (!liste.length) {
    onProgress?.(1)

    return Promise.resolve([])
  }

  let faits = 0
  onProgress?.(0)

  const tout = Promise.all(
    liste.map((src) =>
      precharger(src).then((resultat) => {
        faits += 1
        onProgress?.(faits / liste.length)

        return resultat
      }),
    ),
  )

  // Une image qui ne répond jamais ne doit pas bloquer le joueur sur le voile.
  const garde = new Promise((resolve) => {
    setTimeout(() => resolve([]), timeout)
  })

  return Promise.race([tout, garde])
}
