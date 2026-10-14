#!/usr/bin/env python3
"""Build the static TTF fonts bundled in fonts/.

mPDF cannot use variable fonts, so this downloads the OFL families from the
google/fonts repository, instantiates static Regular/Bold/Italic/BoldItalic
files and subsets them to Latin, Latin Extended, Greek and Cyrillic.

Usage (needs fonttools and brotli):
    python3 -m venv .venv && .venv/bin/pip install fonttools brotli
    .venv/bin/python bin/build-fonts.py
"""

import io
import os
import sys
import urllib.request

from fontTools import subset
from fontTools.ttLib import TTFont
from fontTools.varLib import instancer

BASE = "https://raw.githubusercontent.com/google/fonts/main/ofl/"
OUT = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "fonts")

# key: (folder, upright file, italic file or None, extra axis pins, license file)
FAMILIES = {
    "inter": ("inter", "Inter[opsz,wght].ttf", "Inter-Italic[opsz,wght].ttf", {"opsz": 14}),
    "lora": ("lora", "Lora[wght].ttf", "Lora-Italic[wght].ttf", {}),
    "montserrat": ("montserrat", "Montserrat[wght].ttf", "Montserrat-Italic[wght].ttf", {}),
    "playfair": ("playfairdisplay", "PlayfairDisplay[wght].ttf", "PlayfairDisplay-Italic[wght].ttf", {}),
    "bebas": ("bebasneue", "BebasNeue-Regular.ttf", None, {}),
}

UNICODES = (
    "U+0000-024F,U+0259,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0300-036F,"  # Latin, Latin Extended, combining marks
    "U+0370-03FF,U+1F00-1FFF,"  # Greek
    "U+0400-04FF,U+0500-052F,"  # Cyrillic
    "U+1E00-1EFF,U+2000-206F,U+2070-209F,U+20A0-20CF,U+2100-214F,U+2190-21FF,U+2212,U+2215,U+FEFF,U+FFFD"
)


def fetch(path):
    url = BASE + urllib.request.quote(path)
    with urllib.request.urlopen(url, timeout=60) as resp:
        return resp.read()


def make_static(data, weight, pins):
    font = TTFont(io.BytesIO(data))
    if "fvar" in font:
        axes = {a.axisTag for a in font["fvar"].axes}
        location = {"wght": weight}
        location.update({k: v for k, v in pins.items() if k in axes})
        font = instancer.instantiateVariableFont(font, location, updateFontNames=True)
    options = subset.Options()
    options.layout_features = ["kern", "liga", "clig", "calt", "ccmp", "locl", "mark", "mkmk"]
    options.name_IDs = ["*"]
    options.notdef_outline = True
    sub = subset.Subsetter(options)
    sub.populate(unicodes=subset.parse_unicodes(UNICODES))
    sub.subset(font)
    return font


def main():
    for key, (folder, upright, italic, pins) in FAMILIES.items():
        dest = os.path.join(OUT, key)
        os.makedirs(dest, exist_ok=True)
        up = fetch(folder + "/" + upright)
        it = fetch(folder + "/" + italic) if italic else None
        styles = {"Regular": (up, 400), "Bold": (up, 700)}
        if it:
            styles.update({"Italic": (it, 400), "BoldItalic": (it, 700)})
        for style, (data, weight) in styles.items():
            if "fvar" not in TTFont(io.BytesIO(data)) and style == "Bold":
                continue  # single-weight family such as Bebas Neue
            font = make_static(data, weight, pins)
            path = os.path.join(dest, "%s-%s.ttf" % (key, style))
            font.save(path)
            print("%-40s %7d bytes" % (os.path.relpath(path, OUT), os.path.getsize(path)))
        with open(os.path.join(dest, "OFL.txt"), "wb") as fh:
            fh.write(fetch(folder + "/OFL.txt"))
    return 0


if __name__ == "__main__":
    sys.exit(main())
