"""Generate simple flat product illustrations for the demo catalogue.

Usage: python3 tools/generate_product_images.py
Reads sample-data/products.json, writes sample-data/images/<SKU>.png (800x800).
Placeholder art only – replace with real product photos in production.
"""
import json
from pathlib import Path

from PIL import Image, ImageDraw

ROOT = Path(__file__).resolve().parent.parent
SIZE = 800
BG = (245, 247, 249)


def hex_rgb(h):
    h = h.lstrip("#")
    return tuple(int(h[i:i + 2], 16) for i in (0, 2, 4))


def shade(rgb, f):
    return tuple(max(0, min(255, int(c * f))) for c in rgb)


def shadow(d, cx, y, w):
    d.ellipse([cx - w // 2, y - 14, cx + w // 2, y + 14], fill=(226, 230, 235))


def wheels(d, x0, x1, y, count=4):
    pos = [x0 + 30, x1 - 30] if count >= 2 else []
    for x in pos:
        d.rounded_rectangle([x - 22, y, x + 22, y + 34], 12, fill=(40, 44, 52))
        if count == 4:
            d.rounded_rectangle([x - 22 + (14 if x < SIZE / 2 else -14), y + 4, x + 22 + (14 if x < SIZE / 2 else -14), y + 34], 12, fill=(55, 60, 70))


def suitcase(d, c, w=330, h=470, cx=400, top=170, ridges=True, n_wheels=4):
    x0, x1, y1 = cx - w // 2, cx + w // 2, top + h
    shadow(d, cx, y1 + 40, w + 60)
    # handle
    d.rounded_rectangle([cx - 60, top - 120, cx + 60, top + 10], 14, outline=(70, 75, 85), width=14)
    d.rectangle([cx - 52, top - 40, cx + 52, top + 10], fill=BG)
    d.rectangle([cx - 66, top - 30, cx - 52, top + 10], fill=(70, 75, 85))
    d.rectangle([cx + 52, top - 30, cx + 66, top + 10], fill=(70, 75, 85))
    wheels(d, x0, x1, y1 - 6, n_wheels)
    d.rounded_rectangle([x0, top, x1, y1], 38, fill=c)
    d.rounded_rectangle([x0, top, x1, y1], 38, outline=shade(c, 0.75), width=6)
    if ridges:
        for i in range(1, 4):
            x = x0 + i * w // 4
            d.line([x, top + 40, x, y1 - 40], fill=shade(c, 0.8), width=8)
    # side handle + zip line
    d.line([x0 + 8, top + h // 2, x1 - 8, top + h // 2], fill=shade(c, 0.7), width=4)
    d.rounded_rectangle([cx - 50, top + 22, cx + 50, top + 40], 8, fill=shade(c, 0.65))


def softcase(d, c):
    suitcase(d, c, w=350, h=460, ridges=False, n_wheels=2)
    d.rounded_rectangle([290, 330, 510, 560], 26, fill=shade(c, 0.85), outline=shade(c, 0.7), width=5)
    d.line([300, 350, 500, 350], fill=(230, 230, 230), width=4)


def underseat(d, c):
    shadow(d, 400, 600, 480)
    d.rounded_rectangle([170, 250, 630, 590], 60, fill=c)
    d.rounded_rectangle([210, 320, 590, 560], 40, fill=shade(c, 1.25) if sum(c) < 200 else shade(c, 0.85))
    d.line([210, 300, 590, 300], fill=(200, 200, 200), width=5)
    d.arc([300, 150, 500, 330], 180, 360, fill=shade(c, 0.6), width=18)


def backpack(d, c):
    shadow(d, 400, 650, 360)
    d.arc([320, 120, 480, 240], 180, 360, fill=shade(c, 0.6), width=16)
    d.rounded_rectangle([230, 180, 570, 640], 90, fill=c)
    d.rounded_rectangle([280, 400, 520, 600], 40, fill=shade(c, 0.82))
    d.line([290, 420, 510, 420], fill=(235, 235, 235), width=4)
    d.rounded_rectangle([200, 380, 240, 560], 16, fill=shade(c, 0.75))
    d.rounded_rectangle([560, 380, 600, 560], 16, fill=shade(c, 0.75))


def duffel(d, c):
    shadow(d, 400, 590, 540)
    d.arc([280, 200, 520, 400], 180, 360, fill=shade(c, 0.6), width=18)
    d.rounded_rectangle([140, 300, 660, 580], 120, fill=c)
    d.line([200, 320, 600, 320], fill=(230, 230, 230), width=5)
    d.ellipse([110, 340, 230, 540], fill=shade(c, 0.8))
    d.ellipse([570, 340, 690, 540], fill=shade(c, 0.8))


def wallet(d, c):
    shadow(d, 400, 580, 480)
    d.rounded_rectangle([170, 260, 630, 560], 34, fill=c)
    d.rounded_rectangle([170, 260, 630, 400], 34, fill=shade(c, 1.15))
    d.line([190, 400, 610, 400], fill=shade(c, 0.7), width=4)
    for i in range(3):
        d.rounded_rectangle([210 + i * 20, 200 - i * 10, 450 + i * 20, 300 - i * 10], 12,
                            fill=[(37, 99, 235), (22, 163, 74), (234, 179, 8)][i])
    d.rounded_rectangle([170, 260, 630, 400], 34, fill=shade(c, 1.15))


def cubes(d, c):
    shadow(d, 400, 620, 520)
    colors = [c, shade(c, 0.8), shade(c, 1.2), shade(c, 0.65)]
    boxes = [(150, 380, 470, 600), (430, 420, 660, 600), (210, 230, 450, 380), (450, 300, 610, 420)]
    for (x0, y0, x1, y1), col in zip(boxes, colors):
        d.rounded_rectangle([x0, y0, x1, y1], 22, fill=col, outline=shade(col, 0.7), width=4)
        d.rounded_rectangle([x0 + 20, y0 + 18, x1 - 20, y0 + 50], 10, fill=(255, 255, 255, 120), outline=shade(col, 0.7))


def toiletry(d, c):
    shadow(d, 400, 600, 400)
    d.rounded_rectangle([210, 280, 590, 590], 40, fill=(220, 230, 240), outline=c, width=8)
    d.line([230, 320, 570, 320], fill=c, width=8)
    for x, h, col in [(270, 160, (59, 130, 246)), (350, 200, (236, 72, 153)), (440, 140, (34, 197, 94)), (510, 180, (250, 204, 21))]:
        d.rounded_rectangle([x, 560 - h, x + 55, 560], 14, fill=col)


def adapter(d, c):
    shadow(d, 400, 600, 340)
    d.rounded_rectangle([250, 250, 550, 570], 50, fill=(255, 255, 255), outline=(203, 213, 225), width=6)
    d.rectangle([300, 190, 320, 260], fill=(148, 163, 184))
    d.rectangle([480, 190, 500, 260], fill=(148, 163, 184))
    for x in (310, 390, 470):
        d.rounded_rectangle([x, 470, x + 40, 500], 6, fill=(30, 41, 59))
    d.ellipse([360, 330, 440, 410], outline=(37, 99, 235), width=8)


def strap(d, c):
    shadow(d, 400, 620, 520)
    d.rounded_rectangle([120, 360, 680, 440], 20, fill=c)
    d.rounded_rectangle([360, 340, 440, 460], 12, fill=(30, 41, 59))
    d.rounded_rectangle([470, 470, 640, 600], 18, fill=(30, 41, 59))
    d.rounded_rectangle([490, 490, 620, 580], 10, fill=(255, 255, 255))


def set_(d, c):
    suitcase(d, shade(c, 0.85), w=300, h=430, cx=300, top=200)
    suitcase(d, c, w=220, h=320, cx=560, top=310)


DRAW = {
    "suitcase": suitcase, "softcase": softcase, "underseat": underseat, "backpack": backpack,
    "duffel": duffel, "wallet": wallet, "cubes": cubes, "toiletry": toiletry,
    "adapter": adapter, "strap": strap, "set": set_,
}


def main():
    data = json.loads((ROOT / "sample-data" / "products.json").read_text(encoding="utf-8"))
    out = ROOT / "sample-data" / "images"
    out.mkdir(parents=True, exist_ok=True)
    for p in data["products"]:
        img = Image.new("RGB", (SIZE, SIZE), BG)
        d = ImageDraw.Draw(img, "RGBA")
        DRAW[p["image"]["type"]](d, hex_rgb(p["image"]["color"]))
        img.save(out / f"{p['sku']}.png", optimize=True)
        print("wrote", p["sku"])


if __name__ == "__main__":
    main()
