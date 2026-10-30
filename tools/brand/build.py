"""Builds the dotPulse brand assets (SVG, PNG, ICO) into images/brand/.

Needs Python 3 with fonttools and cairosvg, and ImageMagick for the ICO:
    python3 -m venv /tmp/brand-venv && /tmp/brand-venv/bin/pip install fonttools cairosvg
    /tmp/brand-venv/bin/python tools/brand/build.py
The wordmark font (Space Grotesk, SIL OFL) is downloaded on first run.
"""
import os, subprocess, urllib.request
from fontTools.ttLib import TTFont
from fontTools.varLib.instancer import instantiateVariableFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.pens.boundsPen import BoundsPen
import cairosvg

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.normpath(os.path.join(HERE, '..', '..', 'images', 'brand'))  # ImageMagick rejects '..' paths
FONT = os.path.join(HERE, '.cache', 'SpaceGrotesk.ttf')
FONT_URL = 'https://github.com/google/fonts/raw/main/ofl/spacegrotesk/SpaceGrotesk%5Bwght%5D.ttf'
os.makedirs(OUT, exist_ok=True)

C = {
    'azul': '#1B4F9C', 'azul_prof': '#123A73', 'azul_claro': '#7BA9F7',
    'turq': '#0A8080', 'turq_claro': '#2BC4C4',
    'coral': '#F06449', 'coral_claro': '#FF8A6E', 'coral_texto': '#C93D27',
    'grafito': '#23262B', 'pizarra': '#5A6474', 'linea': '#B8C3D4', 'linea_osc': '#3A4456',
    'bruma': '#E8EDF5', 'noche': '#141A24', 'texto_osc': '#E9EDF3', 'blanco': '#FFFFFF',
}

# --- Mark: five tasks on a 64 grid; the peak is the busiest one, the dip a blocked one.
PTS = [(8, 39), (20, 39), (32, 17), (44, 49), (56, 33)]


def mark(line, dot, peak, dip, x=0, y=0, s=1.0):
    pts = ' '.join('%g,%g' % p for p in PTS)
    g = ['<g transform="translate(%g %g) scale(%g)">' % (x, y, s),
         '<polyline points="%s" fill="none" stroke="%s" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>' % (pts, line)]
    for i, (px, py) in enumerate(PTS):
        r, f = (7, peak) if i == 2 else (4.5, dip if i == 3 else dot)
        g.append('<circle cx="%g" cy="%g" r="%g" fill="%s"/>' % (px, py, r, f))
    g.append('</g>')
    return ''.join(g)


def svg(w, h, body, title='dotPulse'):
    return ('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %g %g" width="%g" height="%g" role="img" aria-label="%s">'
            '<title>%s</title>%s</svg>\n') % (w, h, w, h, title, title, body)


# --- Wordmark: Space Grotesk at weight 600, outlined so the files need no font.
if not os.path.exists(FONT):
    os.makedirs(os.path.dirname(FONT), exist_ok=True)
    urllib.request.urlretrieve(FONT_URL, FONT)
font = TTFont(FONT)
font = instantiateVariableFont(font, {'wght': 600})
gs = font.getGlyphSet()
cmap = font.getBestCmap()
upm = font['head'].unitsPerEm
cap = font['OS/2'].sCapHeight
hmtx = font['hmtx']
TRACK = -10  # font units; Space Grotesk is a little loose at display sizes


def word(text, colors, size, x, baseline):
    """Outlined text; colors[i] colours text[i]. Returns (svg, width)."""
    k = size / upm
    pen_x, parts = 0, []
    for ch, col in zip(text, colors):
        gn = cmap[ord(ch)]
        sp = SVGPathPen(gs)
        gs[gn].draw(TransformPen(sp, (k, 0, 0, -k, x + pen_x * k, baseline)))
        parts.append('<path d="%s" fill="%s"/>' % (sp.getCommands(), col))
        pen_x += hmtx[gn][0] + TRACK
    return ''.join(parts), (pen_x - TRACK) * k


def colors(base, init):
    return [init if c in 'dP' else base for c in 'dotPulse']


def lockup(name, line, dot, peak, dip, base, init, bg=None):
    # Mark is 64 high (visual box y 10..53.5); text cap height is 0.42 of it, centred on the mark.
    size = 64 * 0.38 / (cap / upm)
    centre = 31.75
    baseline = centre + 64 * 0.38 / 2
    body, w = word('dotPulse', colors(base, init), size, 76, baseline)
    W, H = 76 + w + 8, 64
    bgr = '<rect width="%g" height="%g" fill="%s"/>' % (W, H, bg) if bg else ''
    open(os.path.join(OUT, name), 'w').write(svg(W, H, bgr + mark(line, dot, peak, dip) + body))


def stacked(name, line, dot, peak, dip, base, init):
    # Mark at 1.5x with its empty top trimmed; caps 22 high, 18 below the mark.
    capH = 22
    size = capH / (cap / upm)
    _, w = word('dotPulse', colors(base, init), size, 0, 0)
    W = max(w, 96) + 8
    top, bottom = 10 * 1.5, 53.5 * 1.5
    baseline = bottom - top + 4 + 18 + capH
    H = baseline + 8
    body, _ = word('dotPulse', colors(base, init), size, (W - w) / 2, baseline)
    m = mark(line, dot, peak, dip, (W - 96) / 2, 4 - top, 1.5)
    open(os.path.join(OUT, name), 'w').write(svg(W, H, m + body))


# --- App icon / favicons: white tasks on the blue tile, light coral peak (3.45:1 on the blue).
def tile64():
    # The mark at 0.8, centred on the tile (its visual box is centred on 32, 31.75).
    pos = [(32 + (px - 32) * 0.8, 32 + (py - 31.75) * 0.8) for px, py in PTS]
    pts = ' '.join('%g,%g' % q for q in pos)
    b = ['<rect width="64" height="64" rx="14" fill="%s"/>' % C['azul'],
         '<polyline points="%s" fill="none" stroke="#fff" stroke-opacity=".45" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>' % pts]
    for i, (cx, cy) in enumerate(pos):
        r, f = (5.75, C['coral_claro']) if i == 2 else (3.75, '#fff')
        b.append('<circle cx="%g" cy="%g" r="%g" fill="%s"/>' % (cx, cy, r, f))
    return svg(64, 64, ''.join(b))


def tile32():
    # Four tasks: the dip-and-recover is what still reads at 32 px.
    p = [(7, 20), (14, 9), (21, 23.5), (26.5, 16)]
    b = ['<rect width="32" height="32" rx="7" fill="%s"/>' % C['azul'],
         '<polyline points="%s" fill="none" stroke="#fff" stroke-opacity=".5" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>' % ' '.join('%g,%g' % q for q in p)]
    for i, (x, y) in enumerate(p):
        b.append('<circle cx="%g" cy="%g" r="%g" fill="%s"/>' % (x, y, 3.5 if i == 1 else 2.5, C['coral_claro'] if i == 1 else '#fff'))
    return svg(32, 32, ''.join(b))


def tile16():
    # Three tasks centred on pixel centres so the dots stay crisp; a faint 1 px line joins them.
    p = [(3.5, 10.5), (7.5, 5.5), (12.5, 9.5)]
    b = ['<rect width="16" height="16" rx="3.5" fill="%s"/>' % C['azul'],
         '<polyline points="%s" fill="none" stroke="#fff" stroke-opacity=".45" stroke-width="1"/>' % ' '.join('%g,%g' % q for q in p)]
    for i, (x, y) in enumerate(p):
        b.append('<circle cx="%g" cy="%g" r="%g" fill="%s"/>' % (x, y, 2.5 if i == 1 else 1.5, C['coral_claro'] if i == 1 else '#fff'))
    return svg(16, 16, ''.join(b))


def write(name, text):
    open(os.path.join(OUT, name), 'w').write(text)


def png(svgname, pngname, size):
    cairosvg.svg2png(url=os.path.join(OUT, svgname), write_to=os.path.join(OUT, pngname),
                     output_width=size, output_height=size)


light = (C['linea'], C['azul'], C['coral'], C['turq'])
dark = (C['linea_osc'], C['azul_claro'], C['coral_claro'], C['turq_claro'])

write('mark.svg', svg(64, 64, mark(*light)))
write('mark-dark.svg', svg(64, 64, mark(*dark)))
write('mark-mono.svg', svg(64, 64, mark('#23262B', '#23262B', '#23262B', '#23262B').replace('stroke-width="3"', 'stroke-width="3" stroke-opacity=".4"')))
lockup('logo.svg', *light, C['grafito'], C['azul'])
lockup('logo-dark.svg', *dark, C['texto_osc'], C['azul_claro'])
lockup('logo-mono.svg', '#23262B', '#23262B', '#23262B', '#23262B', '#23262B', '#23262B')
stacked('logo-stacked.svg', *light, C['grafito'], C['azul'])
write('favicon.svg', tile32())
write('favicon-16.svg', tile16())
write('icon.svg', tile64())
png('favicon-16.svg', 'favicon-16.png', 16)
png('favicon.svg', 'favicon-32.png', 32)
png('favicon.svg', 'favicon-48.png', 48)
png('icon.svg', 'apple-touch-icon.png', 180)
png('icon.svg', 'icon-512.png', 512)
pngs = [os.path.join(OUT, 'favicon-%d.png' % n) for n in (16, 32, 48)]
subprocess.run(['magick'] + pngs + [os.path.join(OUT, 'favicon.ico')], check=True)
for f in pngs[1:]:
    os.remove(f)  # only needed inside the ICO; favicon-16.png stays for old browsers
print('\n'.join(sorted(os.listdir(OUT))))
