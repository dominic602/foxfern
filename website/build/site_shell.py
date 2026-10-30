"""The live site's page shell, taken from template-residential-remodeling.html (a copy of a live page).

Pages built here reuse its head assets (font preloads and the shared stylesheet), icon sprite, header and footer,
so they look and behave like every other page on the site.
"""
import re
from pathlib import Path

HERE = Path(__file__).resolve().parent


def shell(sprite_extra=''):
    """Returns (head_assets, sprite, header, footer). sprite_extra adds <symbol>s to the icon sprite."""
    tpl = (HERE / 'template-residential-remodeling.html').read_text(encoding='utf-8')
    head_assets = tpl[tpl.index('<link rel="preload" href="/assets/fonts'):tpl.index('</head>')]
    body = tpl[tpl.index('<body>'):]
    sprite = re.search(r'<svg width="0" height="0".*?</svg>', body, re.S).group(0)
    sprite = sprite[:sprite.rindex('</svg>')] + sprite_extra + '\n</svg>'
    header = re.search(r'<header class="site-header".*?</header>', body, re.S).group(0)
    header = header.replace(' aria-current="page"', '')  # built pages aren't in the menu
    footer = re.search(r'<footer class="footer">.*?</footer>', body, re.S).group(0)
    return head_assets, sprite, header, footer
