# -*- coding: utf-8 -*-
"""Genere src/config/icons.js a partir des paquets @iconify-json.

On ne depend d'aucune bibliotheque a l'execution : les paquets @iconify-json
ne servent qu'ici, au moment de la generation.
"""
import io
import json
import os
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
SORTIE = os.path.normpath(os.path.join(HERE, "..", "src", "config", "icons.js"))
SOURCES = os.path.normpath(os.path.join(HERE, "..", "node_modules", "@iconify-json"))

CHOIX = {
    # --- ressources ---------------------------------------------------------
    "or": ("game-icons", "two-coins"),
    "ble": ("game-icons", "wheat"),
    "bois": ("game-icons", "log"),
    "pierre": ("game-icons", "stone-pile"),
    "soldats": ("game-icons", "crossed-swords"),
    "fer": ("game-icons", "metal-bar"),
    "charbon": ("game-icons", "coal-pile"),
    "ressource": ("game-icons", "chest"),
    # --- batiments ----------------------------------------------------------
    "hotel-de-ville": ("game-icons", "greek-temple"),
    "ferme": ("game-icons", "grain-bundle"),
    "foret": ("game-icons", "pine-tree"),
    "mine": ("game-icons", "war-pick"),
    "caserne": ("game-icons", "barracks"),
    "marche": ("game-icons", "trade"),
    "ruine": ("game-icons", "ancient-ruins"),
    # --- monde du jeu -------------------------------------------------------
    "couronne": ("game-icons", "crown"),
    "chateau": ("game-icons", "castle"),
    "village": ("game-icons", "village"),
    "incendie": ("game-icons", "fire"),
    "aventure": ("game-icons", "horse-head"),
    "loi": ("game-icons", "scroll-unfurled"),
    "annonce": ("game-icons", "hunting-horn"),
    "victoire": ("game-icons", "trophy"),
    "defaite": ("game-icons", "shattered-sword"),
    "experience": ("game-icons", "sparkles"),
    "chantier": ("game-icons", "hammer-nails"),
    "sablier": ("game-icons", "sands-of-time"),
    "podium-1": ("game-icons", "podium-winner"),
    "podium-2": ("game-icons", "podium-second"),
    "podium-3": ("game-icons", "podium-third"),
    "fin-de-saison": ("game-icons", "checkered-flag"),
    "carte": ("game-icons", "treasure-map"),
    "celebration": ("game-icons", "party-popper"),
    "hache": ("game-icons", "battle-axe"),
    "plume": ("game-icons", "quill-ink"),
    # --- evenements (valeurs stockees en base) ------------------------------
    "raid": ("game-icons", "black-flag"),
    "pillage": ("game-icons", "wood-axe"),
    "secheresse": ("game-icons", "cactus"),
    "forge": ("game-icons", "anvil-impact"),
    "defense": ("game-icons", "bordered-shield"),
    # --- interface ----------------------------------------------------------
    "fermer": ("lucide", "x"),
    "envoyer": ("lucide", "send"),
    "discussion": ("lucide", "message-circle"),
    "reglages": ("lucide", "settings"),
    "recherche": ("lucide", "microscope"),
    "telecharger": ("lucide", "download"),
    "supervision": ("lucide", "radio-tower"),
    "api": ("lucide", "plug"),
    "anomalie": ("lucide", "bug"),
    "population": ("lucide", "users"),
    "compte": ("lucide", "user"),
    "position": ("lucide", "map-pin"),
    "succes": ("lucide", "circle-check"),
    "echec": ("lucide", "circle-x"),
    "langue": ("lucide", "globe"),
    "operationnel": ("lucide", "circle-dot"),
    "degrade": ("lucide", "triangle-alert"),
    "action": ("lucide", "zap"),
    "loyaute": ("lucide", "heart"),
}

try:
    jeux = {n: json.load(io.open(os.path.join(SOURCES, n, "icons.json"),
                                 encoding="utf-8"))
            for n in ("game-icons", "lucide")}
except FileNotFoundError as e:
    print("Donnees introuvables : %s" % e)
    print("Lancer d'abord : npm install")
    sys.exit(1)

manquants = [(k, j, n) for k, (j, n) in CHOIX.items() if n not in jeux[j]["icons"]]
if manquants:
    for k, j, n in manquants:
        print("  INTROUVABLE  %-16s %s:%s" % (k, j, n))
    sys.exit(1)

lignes = [
    "/**",
    " * Icones du jeu, extraites des jeux `game-icons.net` (CC BY 3.0) et",
    " * `lucide` (ISC) par scripts/gen_icons.py. Fichier genere : ne pas editer",
    " * a la main, modifier la table CHOIX du script puis relancer.",
    " *",
    " * Les aplats du monde medieval viennent de game-icons, les pictogrammes",
    " * d'interface de lucide : chaque corps SVG porte deja son `currentColor`,",
    " * la couleur est donc heritee du texte environnant.",
    " */",
    "export const ICONS = {",
]
for cle in sorted(CHOIX):
    jeu, nom = CHOIX[cle]
    d = jeux[jeu]
    ico = d["icons"][nom]
    w = ico.get("width", d.get("width", 16))
    h = ico.get("height", d.get("height", 16))
    corps = ico["body"].replace("\\", "\\\\").replace("'", "\'")
    lignes.append("  '%s': { source: '%s:%s', box: '0 0 %s %s', body: '%s' }," %
                  (cle, jeu, nom, w, h, corps))
lignes += ["}", "",
           "export function iconExists(nom) {",
           "  return Object.prototype.hasOwnProperty.call(ICONS, nom)",
           "}", ""]

io.open(SORTIE, "w", encoding="utf-8", newline="\n").write("\n".join(lignes))
print("%d icones ecrites (%d Ko) -> %s"
      % (len(CHOIX), os.path.getsize(SORTIE) // 1024, SORTIE))
