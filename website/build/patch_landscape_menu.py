#!/usr/bin/env python3
"""Adds the sideways-phone menu fix to the site's static pages.

On a phone turned sideways (a screen under 500px tall), the open header menu is a single column that runs past
the bottom of the screen. The header is fixed, so the last links can't be scrolled to. The fix shows the menu in
two columns and lets it scroll if it still doesn't fit. Nothing changes on upright phones, tablets or desktop.

Run:  python3 patch_landscape_menu.py <folder of fresh copies>
      The folder holds the live pages as downloaded: home.html, about.html, residential-remodeling.html, ...
Out:  ../public_html/<page>/index.html  (pages already in public_html are patched in place instead,
      so earlier edits to them are kept)
"""
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
PUBLIC = HERE.parent / 'public_html'
MARKER = '/* header menu on phones turned sideways'
PATCH = '''
/* header menu on phones turned sideways: two columns, and it scrolls if it still doesn't fit, so every link stays reachable */
@media (orientation: landscape) and (max-height: 500px) and (max-width: 960px) {
  .nav.open .menu { grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 8px; max-height: calc(100vh - 110px); max-height: calc(100dvh - 110px); overflow-y: auto; overscroll-behavior: contain; }
  .nav.open .menu a { padding: 12px; }
  .nav.open .menu li + li { border-top: 0; }
  .nav.open .menu li:nth-child(n+3) { border-top: 1px solid rgba(243,239,232,.08); }
}
'''
PAGES = {  # fresh-copy name -> folder in public_html ('' is the homepage)
    'home': '', 'residential-remodeling': 'residential-remodeling', 'commercial-renovations': 'commercial-renovations',
    'repairs-maintenance': 'repairs-maintenance', 'projects': 'projects', 'service-areas': 'service-areas',
    'about': 'about', 'contact': 'contact',
}


def patch(html):
    if MARKER in html:
        return html
    at = html.index('.nav.open .menu {')  # add to the end of the stylesheet that holds the menu rules
    end = html.index('</style>', at)
    return html[:end] + PATCH + html[end:]


def main(fresh):
    for name, folder in PAGES.items():
        out = PUBLIC / folder / 'index.html'
        src = out if out.exists() else Path(fresh) / f'{name}.html'
        with open(src, encoding='utf-8', newline='') as f:  # keep the file's own line endings
            html = f.read()
        new = patch(html)
        out.parent.mkdir(parents=True, exist_ok=True)
        with open(out, 'w', encoding='utf-8', newline='') as f:
            f.write(new)
        print(f'{"patched" if new != html else "already patched"}: /{folder + "/" if folder else ""} (from {src.name if src != out else "public_html"})')


if __name__ == '__main__':
    main(sys.argv[1])
