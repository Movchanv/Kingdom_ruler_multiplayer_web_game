"""
Retrouve chaque vignette de bâtiment (découpe) dans l'image globale de la ville
par détection d'image (template matching, OpenCV), puis génère les rectangles
exacts (en % de l'image) dans `src/config/townRects.js`.

Chaque vignette de `public/buildings/*.png` est un extrait de `public/town-*.png` :
le script la localise, gère la transparence (canal alpha comme masque) et cherche
sur une plage d'échelles au cas où l'export n'est pas à la taille d'origine.

Usage :
    py frontend/scripts/locate_buildings.py
"""

from __future__ import annotations

import os
import cv2
import numpy as np

HERE = os.path.dirname(os.path.abspath(__file__))
PUBLIC = os.path.normpath(os.path.join(HERE, "..", "public"))
OUT = os.path.normpath(os.path.join(HERE, "..", "src", "config", "townRects.js"))

# Ville -> image de fond + (clé bâtiment -> fichier de découpe)
TOWNS = {
    "Paris": {
        "background": "town-paris.png",
        "pieces": {
            "town_hall": "buildings/hotel-de-ville.png",
            "caserne": "buildings/caserne.png",
            "market": "buildings/place-du-marche.png",
            "mine": "buildings/mine-de-pierre.png",
            "ferme": "buildings/ferme.png",
            "forest": "buildings/foret.png",
            "adventure": "buildings/aventure.png",
        },
    },
}

# Correction des clés vers celles de l'API (buildings.key).
KEY_ALIASES = {"caserne": "barracks", "ferme": "farm"}

SCALES = np.arange(0.5, 1.61, 0.05)


def load_rgba(path: str):
    img = cv2.imread(path, cv2.IMREAD_UNCHANGED)
    if img is None:
        return None, None
    if img.ndim == 2:
        img = cv2.cvtColor(img, cv2.COLOR_GRAY2BGR)
    if img.shape[2] == 4:
        bgr = img[:, :, :3]
        alpha = img[:, :, 3]
    else:
        bgr = img
        alpha = np.full(img.shape[:2], 255, np.uint8)
    return bgr, alpha


def locate(source, tpl_bgr, tpl_alpha):
    """Meilleure position/échelle de la découpe dans la source. -> (score, x, y, w, h)."""
    best = (-1.0, 0, 0, 0, 0)
    sh, sw = source.shape[:2]

    for scale in SCALES:
        w = int(tpl_bgr.shape[1] * scale)
        h = int(tpl_bgr.shape[0] * scale)
        if w < 12 or h < 12 or w > sw or h > sh:
            continue

        tpl = cv2.resize(tpl_bgr, (w, h), interpolation=cv2.INTER_AREA)
        mask = cv2.resize(tpl_alpha, (w, h), interpolation=cv2.INTER_AREA)

        res = cv2.matchTemplate(source, tpl, cv2.TM_CCORR_NORMED, mask=mask)
        res = np.nan_to_num(res, nan=-1.0, posinf=-1.0, neginf=-1.0)
        _, max_val, _, max_loc = cv2.minMaxLoc(res)

        if max_val > best[0]:
            best = (float(max_val), int(max_loc[0]), int(max_loc[1]), w, h)

    return best


def main() -> None:
    towns_out = {}

    for town, cfg in TOWNS.items():
        bg_path = os.path.join(PUBLIC, cfg["background"])
        source, _ = load_rgba(bg_path)
        if source is None:
            print(f"[!] Fond introuvable : {bg_path} — ville « {town} » ignorée.")
            continue

        sh, sw = source.shape[:2]
        rects = {}
        print(f"\n=== {town} ({sw}x{sh}) ===")

        for key, rel in cfg["pieces"].items():
            path = os.path.join(PUBLIC, rel)
            tpl_bgr, tpl_alpha = load_rgba(path)
            if tpl_bgr is None:
                print(f"  [manquant] {rel}")
                continue

            score, x, y, w, h = locate(source, tpl_bgr, tpl_alpha)
            api_key = KEY_ALIASES.get(key, key)
            flag = "ok " if score >= 0.90 else "?? "
            print(f"  [{flag}] {api_key:<10} score={score:.3f}  @ {x},{y} {w}x{h}")

            rects[api_key] = {
                "x": round(x / sw * 100, 2),
                "y": round(y / sh * 100, 2),
                "w": round(w / sw * 100, 2),
                "h": round(h / sh * 100, 2),
            }

        towns_out[town] = rects

    write_js(towns_out)
    print(f"\n→ Écrit : {OUT}")


def write_js(towns_out: dict) -> None:
    lines = [
        "/**",
        " * Rectangles (en % de l'image de ville) de chaque bâtiment et du départ en",
        " * aventure : coin haut-gauche (x, y) + taille (w, h).",
        " *",
        " * ⚠️ Généré par `scripts/locate_buildings.py` (détection d'image). Ne pas",
        " * éditer à la main : relancer le script après avoir remplacé une image.",
        " */",
        "export const TOWN_RECTS = {",
    ]
    for town, rects in towns_out.items():
        lines.append(f"  {town}: {{")
        for key, r in rects.items():
            lines.append(
                f"    {key}: {{ x: {r['x']}, y: {r['y']}, w: {r['w']}, h: {r['h']} }},"
            )
        lines.append("  },")
    lines += [
        "}",
        "",
        "export function rectFor(townName, key) {",
        "  return TOWN_RECTS[townName]?.[key] ?? null",
        "}",
        "",
    ]
    with open(OUT, "w", encoding="utf-8") as fh:
        fh.write("\n".join(lines))


if __name__ == "__main__":
    main()
