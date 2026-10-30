#!/usr/bin/env python3
"""Packages the Fox & Fern Additions WordPress plugin (../wordpress/fox-fern-additions/).

The site's pages are served by the Fox & Fern Site plugin, not by files in public_html. This add-on plugin puts
the changes in public_html/ live without touching that plugin:

- routes.json: new URLs (the Trappe page, its Markdown copy and share image, and /thank-you/) and the file each serves.
- edits.json:  exact text edits to the site plugin's output, worked out by comparing a fresh download of each live
               file with the edited copy in public_html/, plus the sideways-phone menu CSS for every page. Edits that
               add a link to the Trappe page are skipped on a page that already mentions it.

Every edit is checked here: applying it to the live text must reproduce the edited copy exactly.

Run:  python3 build_additions_plugin.py <folder of fresh live copies> <output folder>
      Fresh copies are the Fox & Fern Site plugin's own output (before this plugin's edits), named like the URL path
      with "/" as "_": residential-remodeling.html, sitemap.xml, llm_topics_services.md, home.html (for /), ...
Out:  <output folder>/fox-fern-additions/ and <output folder>/fox-fern-additions.zip
"""
import difflib
import json
import shutil
import sys
import time
import zipfile
from pathlib import Path

from patch_landscape_menu import MARKER, PATCH

HERE = Path(__file__).resolve().parent
PUBLIC = HERE.parent / 'public_html'
SOURCE = HERE.parent / 'wordpress' / 'fox-fern-additions'
SLUG = 'bathroom-remodeling-trappe-pa'

# new URLs -> (file in public_html, file name in the plugin, content type)
ROUTES = {
    f'/{SLUG}/': (f'{SLUG}/index.html', f'{SLUG}.html', 'text/html; charset=UTF-8'),
    f'/llm/pages/{SLUG}.md': (f'llm/pages/{SLUG}.md', f'{SLUG}.md', 'text/markdown; charset=UTF-8'),
    '/images/projects/bathroom-before-after-king-of-prussia-og.jpg': (
        'images/projects/bathroom-before-after-king-of-prussia-og.jpg', 'bathroom-before-after-king-of-prussia-og.jpg', 'image/jpeg'),
    '/thank-you/': ('thank-you/index.html', 'thank-you.html', 'text/html; charset=UTF-8'),
}
REDIRECTS = {f'/{SLUG}': f'/{SLUG}/', f'/{SLUG}/index.html': f'/{SLUG}/', '/thank-you': '/thank-you/', '/thank-you/index.html': '/thank-you/'}

# existing URLs that get edits -> edited copy in public_html
EDITED = {
    '/': 'index.html',
    '/residential-remodeling/': 'residential-remodeling/index.html',
    '/service-areas/': 'service-areas/index.html',
    '/about/': 'about/index.html',
    '/contact/': 'contact/index.html',
    '/blog/cost-to-paint-a-house-philadelphia-suburbs/': 'blog/cost-to-paint-a-house-philadelphia-suburbs/index.html',
    '/sitemap.xml': 'sitemap.xml',
    '/image-sitemap.xml': 'image-sitemap.xml',
    '/sitemap.md': 'sitemap.md',
    '/llm/topics/services.md': 'llm/topics/services.md',
    '/llm/pages/home.md': 'llm/pages/home.md',
    '/llm/pages/residential-remodeling.md': 'llm/pages/residential-remodeling.md',
    '/llm/pages/about.md': 'llm/pages/about.md',
    '/llm/pages/contact.md': 'llm/pages/contact.md',
    '/llm/pages/cost-to-paint-a-house-philadelphia-suburbs.md': 'llm/pages/cost-to-paint-a-house-philadelphia-suburbs.md',
}
# pages that only get the menu CSS (checked, not listed in edits.json)
MENU_ONLY = {'/commercial-renovations/': 'commercial-renovations/index.html', '/repairs-maintenance/': 'repairs-maintenance/index.html',
             '/projects/': 'projects/index.html'}
MENU = {'marker': MARKER, 'anchor': '.nav.open .menu {', 'css': PATCH}


def fresh_name(url):
    return 'home.html' if url == '/' else url.strip('/').replace('/', '_') + ('' if '.' in url.rsplit('/', 1)[-1] else '.html')


def read(path):
    with open(path, encoding='utf-8', newline='') as f:
        return f.read()


def unique_pair(live_lines, start, end, new_lines):
    """Smallest [find, replace, skip_if_found] around live_lines[start:end] whose find text occurs once in the live
    file. A pure insertion keeps its find text, so it is skipped when the page already has its longest inserted line
    that wasn't in the live file;
    any other edit that adds a link to the Trappe page is skipped on a page that already mentions it."""
    text = ''.join(live_lines)
    before = 1
    while True:
        lo = max(0, start - before)
        find = ''.join(live_lines[lo:end])
        if find and text.count(find) == 1:
            replace = ''.join(live_lines[lo:start]) + ''.join(new_lines)
            inserted = replace[len(find):] if replace.startswith(find) else ''
            new_lines_only = [line.strip() for line in inserted.splitlines() if line.strip() and line.strip() not in text]
            if new_lines_only:
                skip_if_found = max(new_lines_only, key=len)
            else:
                skip_if_found = SLUG if SLUG in replace and SLUG not in find else None
            return [find, replace, skip_if_found]
        if lo == 0:
            raise SystemExit(f'no unique anchor for lines {start}-{end}')
        before += 1


def make_edits(live, edited):
    a, b = live.splitlines(keepends=True), edited.splitlines(keepends=True)
    pairs = []
    for op, i1, i2, j1, j2 in difflib.SequenceMatcher(None, a, b, autojunk=False).get_opcodes():
        if op != 'equal':
            pairs.append(unique_pair(a, i1, i2, b[j1:j2]))
    return pairs


def apply(url, body, edits):  # same logic as ffa_apply_edits() in the plugin
    original = body
    for find, rep, skip_if_found in edits['pages'].get(url, []):
        if skip_if_found and skip_if_found in original:
            continue
        if body.count(find) == 1:
            body = body.replace(find, rep)
    m = edits['menu_css']
    if m['marker'] not in body and m['anchor'] in body:
        end = body.index('</style>', body.index(m['anchor']))
        body = body[:end] + m['css'] + body[end:]
    return body


def main(fresh, out):
    fresh, out = Path(fresh), Path(out)
    edits = {'pages': {}, 'menu_css': MENU}
    for url, rel in EDITED.items():
        edits['pages'][url] = make_edits(read(fresh / fresh_name(url)), read(PUBLIC / rel))

    for url, rel in {**EDITED, **MENU_ONLY}.items():
        live, want = read(fresh / fresh_name(url)), read(PUBLIC / rel)
        got = apply(url, live, edits)
        assert got == want, f'{url}: edits do not reproduce public_html/{rel}'
        assert apply(url, got, edits) == got, f'{url}: edits are not idempotent'
        n = len(edits['pages'].get(url, []))
        print(f'ok  {url:28} {n} edit(s){" + menu CSS" if MENU["marker"] in got and MENU["marker"] not in live else ""}')

    plugin = out / 'fox-fern-additions'
    shutil.rmtree(plugin, ignore_errors=True)
    (plugin / 'files').mkdir(parents=True)
    shutil.copy2(SOURCE / 'fox-fern-additions.php', plugin)
    routes = {'built': int(time.time()), 'redirects': REDIRECTS, 'files': {}}
    for url, (src, name, ctype) in ROUTES.items():
        shutil.copy2(PUBLIC / src, plugin / 'files' / name)
        routes['files'][url] = {'file': f'files/{name}', 'type': ctype}
    (plugin / 'routes.json').write_text(json.dumps(routes, indent=1), encoding='utf-8')
    (plugin / 'edits.json').write_text(json.dumps(edits, indent=1, ensure_ascii=False), encoding='utf-8')

    zpath = out / 'fox-fern-additions.zip'
    with zipfile.ZipFile(zpath, 'w', zipfile.ZIP_DEFLATED) as z:
        for f in sorted(plugin.rglob('*')):
            if f.is_file():
                z.write(f, f.relative_to(out).as_posix())
    print(f'wrote {zpath} ({zpath.stat().st_size:,} bytes)')


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2])
