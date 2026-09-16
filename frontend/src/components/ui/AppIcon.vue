<script setup>
import { computed } from 'vue'
import { ICONS } from '@/config/icons'

const props = defineProps({
  name: { type: String, required: true },
  /**
   * Intitulé lu par les lecteurs d'écran. Laisser vide quand l'icône ne fait
   * que doubler un texte voisin : elle est alors purement décorative et doit
   * être ignorée, sinon l'information est annoncée deux fois.
   */
  label: { type: String, default: '' },
})

const icone = computed(() => ICONS[props.name] ?? null)
</script>

<template>
  <!--
    v-html est ici sans risque : le corps SVG provient de src/config/icons.js,
    genere au build a partir de paquets figes. `name` ne sert qu'a choisir une
    cle de cette table ; une valeur inconnue renvoie undefined et le v-if
    empeche tout rendu. Aucune saisie utilisateur n'atteint ce point.
  -->
  <!-- eslint-disable vue/no-v-html -->
  <svg
    v-if="icone"
    class="app-icon"
    :viewBox="icone.box"
    :role="label ? 'img' : undefined"
    :aria-label="label || undefined"
    :aria-hidden="label ? undefined : 'true'"
    focusable="false"
    v-html="icone.body"
  />
</template>

<style scoped>
.app-icon {
  /* Dimensionnée par la taille du texte : une icône suit son intitulé sans
     qu'on ait à la régler à chaque emploi. Chaque tracé porte déjà son propre
     `currentColor`, on ne force donc ni fill ni stroke ici : game-icons est en
     aplats, lucide en traits, et les écraser casserait l'un ou l'autre. */
  width: 1em;
  height: 1em;
  display: inline-block;
  flex: none;
  vertical-align: -0.125em;
}
</style>
