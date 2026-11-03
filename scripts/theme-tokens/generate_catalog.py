#!/usr/bin/env python3
"""Builds resources/theme-tokens/catalog.json (see README.md in this folder).

Usage: python3 scripts/theme-tokens/generate_catalog.py <inventory-dir>
"""
import json,glob,re,os,collections
import sys
R=os.path.abspath(os.path.join(os.path.dirname(__file__),'..','..'))+'/'
S=(sys.argv[1] if len(sys.argv)>1 else 'inventory')+'/'   # directory with one <theme>-<variant>.json per variant (see inventory.js)
pal=re.compile(r'^--color-(slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-\d+$')
skip=re.compile(r'^--(tw|text|spacing|font|radius|shadow|inset-shadow|drop-shadow|ease|animate|blur|breakpoint|container|leading|tracking|perspective|aspect|default|swiper|ds|swal2|iti|livewire)')
colre=re.compile(r'^(#[0-9a-fA-F]{3,8}|rgba?\(|hsla?\()')
THEME_SRC={
 'ecommet':['resources/views/themes/ecommet','resources/css/ecommet.css','resources/css/ecommet','public/ecommet','resources/js/ecommet','resources/js/ecommet.js'],
 'elora':['resources/views/themes/elora','resources/css/elora','resources/js/elora','public/elora','public/elora-1','public/elora-2','public/elora-3','public/elora-4','public/elora-5','resources/views/components/elora'],
 'souqify':['resources/views/themes/souqify','resources/css/souqify','resources/js/souqify','public/souqify','public/souqify-1','public/souqify-2','public/souqify-3','public/souqify-4','public/souqify-5'],
}
SHARED=['resources/views/components','resources/views/storefront','resources/views/livewire/tenant/storefront']
MARK={'elora':{'v2':['home-v2','-v2','elora-1/'],'v3':['home-v3','-v3','elora-3/'],'v4':['home-v4','-v4','elora-4/'],'v5':['home-v5','-v5','elora-5/'],'v6':['home-v6','-v6','elora-2/'],'v1':[]},
      'souqify':{'v2':['home-v2','-v2','souqify-1/'],'v3':['home-v3','-v3','souqify-2/'],'v4':['home-v4','-v4','souqify-3/'],'v5':['home-v5','-v5','souqify-4/'],'v6':['home-v6','-v6','souqify-5/'],'v1':[]},
      'ecommet':{'v1':[]}}
AREAS=[('header','Header & navigation'),('mobile-home-header','Header & navigation'),('footer','Footer'),('hero','Hero banner'),('flash','Flash sale'),('trending','Trending now'),('new_arrivals','New In'),('new-in','New In'),('new_in','New In'),('newin','New In'),('best_seller','Best sellers'),('best-selling','Best sellers'),('bestseller','Best sellers'),('top_products','Best sellers'),('recommended','Recommended for you'),('explore','Explore products'),('categor','Categories'),('trust','Trust / features bar'),('promo','Promo banner'),('product_card','Product cards'),('product-card','Product cards'),('_card','Product cards'),('card','Product cards'),('cart','Cart'),('checkout','Checkout'),('pages/product','Product page'),('product.','Product page'),('login','Login & registration'),('auth','Login & registration'),('profile','Account / profile'),('order','Orders'),('search','Search'),('favorites','Favorites'),('offers','Offers page'),('tabbed','Tabbed products'),('layout','Global styles'),('styles','Global styles'),('app.blade','Global styles'),('components','Shared components')]
def area(path):
    p=path.lower()
    for k,v in AREAS:
        if k in p: return v
    return 'Global styles'
def files_for(theme,var):
    out=[]; others=[m for v,ms in MARK[theme].items() if v!=var for m in ms if m not in MARK[theme].get(var,[])]
    mine=MARK[theme].get(var,[])
    for base in THEME_SRC[theme]+SHARED:
        pth=R+base
        cand=[pth] if os.path.isfile(pth) else [os.path.join(dp,f) for dp,_,fs in os.walk(pth) for f in fs] if os.path.isdir(pth) else []
        for f in cand:
            if not re.search(r'\.(css|php|js|html)$',f): continue
            rel=f.replace(R,'')
            if any(m in rel for m in others) and not any(m in rel for m in mine): continue
            out.append(rel)
    return sorted(set(out))
def norm(v):
    v=v.strip().lower()
    m=re.match(r'^#([0-9a-f])([0-9a-f])([0-9a-f])$',v)
    if m: v='#'+''.join(c*2 for c in m.groups())
    return v

SEL_AREAS=[('footer','Footer'),('header','Header & navigation'),('navbar','Header & navigation'),('topbar','Header & navigation'),('topnav','Header & navigation'),('utility','Header & navigation'),('menu','Header & navigation'),('hero','Hero banner'),('flash','Flash sale'),('trend','Trending now'),('newin','New In'),('new-in','New In'),('best','Best sellers'),('reco','Recommended for you'),('categor','Categories'),('cats','Categories'),('trust','Trust / features bar'),('feature','Trust / features bar'),('promo','Promo banner'),('card','Product cards'),('deal','Product cards'),('product','Product cards'),('cart','Cart'),('checkout','Checkout'),('auth','Login & registration'),('login','Login & registration'),('btn','Buttons'),('button','Buttons'),('badge','Badges'),('search','Search'),('drawer','Header & navigation'),('mobile','Mobile bar')]
def area_from_selector(sel,rel):
    s=sel.lower()
    for k,v in SEL_AREAS:
        if k in s: return v
    return area(rel)
def usage(txt,rel,n):
    c=collections.Counter()
    pat=re.compile(r'var\(\s*'+re.escape(n)+r'[\s,)]')
    if rel.endswith('.css') or '<style' in txt:
        for m in pat.finditer(txt):
            head=txt[:m.start()]; i=head.rfind('{'); j=max(head.rfind('}',0,i if i>0 else 0), head.rfind(';',0,0))
            sel=head[(head.rfind('}',0,i)+1 if i>=0 else 0):i] if i>=0 else ''
            c[area_from_selector(sel,rel) if sel.strip() and rel.endswith('.css') else area(rel)]+=1
    else:
        for m in pat.finditer(txt): c[area(rel)]+=1
    short=n[len('--color-'):] if n.startswith('--color-') else None
    if short and not rel.endswith('.css'):
        u=re.compile(r'(?<![\w-])(?:[a-z0-9:!-]+:)?(?:bg|text|border|from|to|via|fill|stroke|ring|outline|divide|placeholder|accent|shadow|decoration|caret)-'+re.escape(short)+r'(?:/\d+)?(?![\w-])')
        for m in u.finditer(txt): c[area(rel)]+=1
    return c
# ── curated vocabulary: label / group / description ───────────────────────────
GROUPS=['Brand & accents','Header & top bar','Text','Backgrounds & surfaces','Borders & lines','Status, prices & badges','Flash sale','Hero banner','Footer','Sections & decorations','Neutrals']
CUR={ # name -> (label, group, description)
'--color-primary':('Primary brand color','Brand & accents','Main brand color: primary buttons, links, active states, highlights and accents across the store.'),
'--color-secondary':('Secondary brand color','Brand & accents','Supporting accent used next to the primary color: secondary buttons, highlights and small accents.'),
'--color-souqify-teal':('Brand color (Teal)','Brand & accents','The Modern edition brand color: header bar, buttons, hero, section titles and active states.'),
'--color-brand-purple':('Brand color (Purple)','Brand & accents','The Purple edition brand color: header bar, buttons, hero, tags and active states.'),
'--color-brand-green':('Brand color (Green)','Brand & accents','The Green edition brand color: header bar, buttons, flash sale, hero and active states.'),
'--color-brand-pink':('Brand color (Pink)','Brand & accents','The Pink edition brand color: header, buttons, hero, flash sale, footer accents and prices.'),
'--color-brand-orange':('Brand color (Orange)','Brand & accents','The Orange edition brand color: header, buttons, flash sale panel, footer accents.'),
'--color-brand-orange-bright':('Brand orange (bright)','Brand & accents','Brighter orange used for sale panels, badges and hover highlights.'),
'--color-brand-green-tint':('Brand green (tint)','Brand & accents','Soft transparent green used as light backgrounds behind brand-colored items.'),
'--color-accent-purple':('Accent purple','Brand & accents','Purple accent used on category chips, trending labels and small highlights.'),
'--color-accent-purple-bright':('Accent purple (bright)','Brand & accents','Bright purple used on the footer and highlight accents.'),
'--color-accent-purple-dark':('Accent purple (dark)','Brand & accents','Dark purple shade of the accent used for gradients and pressed states.'),
'--color-accent-purple-light':('Accent purple (light)','Brand & accents','Light purple shade of the accent used for soft backgrounds.'),
'--color-accent-yellow':('Accent yellow','Brand & accents','Yellow accent used on hero buttons, flash sale labels and highlights.'),
'--color-accent-amber':('Accent amber','Brand & accents','Amber accent used for ratings, badges and warning highlights.'),
'--color-accent-green':('Accent green','Brand & accents','Green accent used on categories, flash sale and success highlights.'),
'--color-yellow':('Yellow highlight','Brand & accents','Yellow used on discount chips, sold labels and highlight stickers on product cards.'),
'--color-yellow-bright':('Yellow (bright)','Brand & accents','Bright yellow used on best-seller and New In stickers.'),
'--color-yellow-light':('Yellow (light)','Brand & accents','Soft yellow used for notices and highlighted rows.'),
'--color-purple':('Purple','Brand & accents','Purple used for special labels and accents.'),
'--color-souqify-orange':('Orange accent','Brand & accents','Orange accent used for sale badges and call-to-action highlights.'),
'--color-bg-main':('Page background (main)','Backgrounds & surfaces','Main background color of pages and white sections.'),
'--color-page-bg':('Page background (soft)','Backgrounds & surfaces','Soft grey page background behind cards and sections.'),
'--color-surface':('Surface','Backgrounds & surfaces','Panel and block backgrounds (menus, inputs, light cards).'),
'--color-card-bg-soft':('Card background (soft)','Backgrounds & surfaces','Soft tinted background inside product cards.'),
'--color-card-bg-lavender':('Card background (lavender)','Backgrounds & surfaces','Lavender tinted background behind card images.'),
'--color-card-image-bg':('Card image background','Backgrounds & surfaces','Background shown behind product images before they load or when transparent.'),
'--color-section-cream':('Section background (cream)','Backgrounds & surfaces','Cream background used by highlighted sections such as New In.'),
'--color-newin-gold':('New In section background','Backgrounds & surfaces','Gold/yellow background of the New In section.'),
'--color-black':('Black','Neutrals','Pure black used for strong text, icons and dark buttons.'),
'--color-black-alt':('Black (soft)','Neutrals','Soft black used for headings and dark UI elements.'),
'--color-white':('White','Neutrals','Pure white used for cards, text on dark backgrounds and icons.'),
'--color-gray':('Grey','Neutrals','Mid grey used for secondary icons, captions and dividers.'),
'--color-gray-dark':('Grey (dark)','Neutrals','Dark grey used for body text and icons.'),
'--color-gray-darkest':('Grey (darkest)','Neutrals','Very dark grey used for headings and strong labels.'),
'--color-gray-medium':('Grey (medium)','Neutrals','Medium grey used for muted text and placeholders.'),
'--color-gray-light':('Grey (light)','Neutrals','Light grey used for borders and disabled states.'),
'--color-gray-lighter':('Grey (lighter)','Neutrals','Lighter grey used for subtle borders and separators.'),
'--color-gray-lightest':('Grey (lightest)','Neutrals','Lightest grey used for page and row backgrounds.'),
'--color-success':('Success / green','Status, prices & badges','Success messages, in-stock labels, delivery text and green confirmation icons.'),
'--color-error':('Error / red','Status, prices & badges','Error messages, sale-red labels, out-of-stock and destructive actions.'),
'--color-price-blue':('Price color (blue)','Status, prices & badges','Price text and progress accents on product cards.'),
'--color-price-alt-blue':('Price color (alt blue)','Status, prices & badges','Alternate price color used on dark/highlighted cards.'),
'--color-progress-fill':('Progress bar fill','Status, prices & badges','Filled part of stock/sold progress bars on product cards.'),
'--color-progress-red':('Progress bar (red)','Status, prices & badges','Filled part of the red urgency progress bars (best sellers).'),
'--color-progress-track':('Progress bar track','Borders & lines','Empty track behind progress bars.'),
'--color-badge-green':('Badge green','Status, prices & badges','Background of green badges.'),
'--color-badge-orange':('Badge orange','Status, prices & badges','Background of orange badges and weight tags.'),
'--color-badge-pink':('Badge pink','Status, prices & badges','Background of pink badges and tags.'),
'--color-badge-purple':('Badge purple','Status, prices & badges','Background of purple badges and tags.'),
'--color-badge-yellow':('Badge yellow','Status, prices & badges','Background of yellow badges.'),
'--color-badge-discount-orange':('Discount badge (orange)','Status, prices & badges','Background of orange discount chips (e.g. 10% off).'),
'--color-badge-discount-yellow':('Discount badge (yellow)','Status, prices & badges','Background of yellow discount chips.'),
'--color-discount-orange':('Discount chip (orange)','Status, prices & badges','Orange discount percentage chip on cards.'),
'--color-discount-yellow':('Discount chip (yellow)','Status, prices & badges','Yellow discount percentage chip on cards.'),
'--color-weight-tag':('Weight tag','Status, prices & badges','Product weight / size tag next to the product name.'),
'--color-stroke':('Border color','Borders & lines','Default border and divider color for cards, inputs and tables.'),
'--color-strike-gray':('Old price (strikethrough)','Text','Color of the crossed-out old price.'),
'--color-text-primary':('Text (primary)','Text','Main body and heading text color.'),
'--color-text-heading':('Text (headings)','Text','Section and product headings.'),
'--color-text-heading-alt':('Text (headings, alt)','Text','Alternative heading color on tinted cards.'),
'--color-text-secondary':('Text (secondary)','Text','Secondary text such as descriptions and meta lines.'),
'--color-text-secondary-dark':('Text (secondary, dark)','Text','Darker secondary text.'),
'--color-text-subtitle':('Text (subtitle)','Text','Subtitles, helper lines and captions.'),
'--color-subtitle':('Text (subtitle)','Text','Subtitles, helper lines and captions.'),
'--color-text-muted':('Text (muted)','Text','De-emphasised text: counts, hints, small captions.'),
'--color-text-dim':('Text (dim)','Text','Dimmed text in menus and secondary labels.'),
'--color-text-faint':('Text (faint)','Text','Very light text such as placeholders and inactive labels.'),
'--color-text-faint-alt':('Text (faint, alt)','Text','Alternative faint text color.'),
'--color-text-placeholder':('Text (placeholder)','Text','Placeholder text inside inputs and the search box.'),
'--color-text-slate':('Text (slate)','Text','Slate text for secondary labels.'),
'--color-text-navbar-category':('Text (menu categories)','Header & top bar','Category links in the main navigation bar.'),
'--color-text-location-muted':('Text (location, muted)','Header & top bar','Delivery location text in the header.'),
'--color-card-title':('Product title','Text','Product name on product cards.'),
'--color-charcoal-text':('Text (charcoal)','Text','Charcoal text for titles on light cards.'),
'--color-navy-text':('Text (navy)','Text','Navy text used in some titles and labels.'),
'--color-desc-gray':('Text (description)','Text','Product descriptions and short captions.'),
'--color-feature-text':('Features bar text','Text','Titles in the trust / features bar.'),
'--color-feature-subtext':('Features bar subtext','Text','Sub-lines in the trust / features bar.'),
'--color-greeting-muted':('Greeting text','Header & top bar','"Hello, ..." greeting text in the header.'),
'--color-topbar':('Top bar background','Header & top bar','Background of the thin top bar above the header.'),
'--color-topbar-text':('Top bar text','Header & top bar','Text and icons in the top bar.'),
'--color-topbar-muted':('Top bar (muted)','Header & top bar','Muted text in the top bar.'),
'--color-utility-bar-bg':('Utility bar background','Header & top bar','Background of the utility bar (language, currency, store links).'),
'--color-utility-text':('Utility bar text','Header & top bar','Text in the utility bar.'),
'--color-navbar-dark':('Navigation bar (dark)','Header & top bar','Dark navigation / category bar background.'),
'--color-navbar-muted':('Navigation text (muted)','Header & top bar','Muted text and icons in the navigation bar.'),
'--color-topnav-grad-dark':('Header gradient (dark end)','Header & top bar','Dark end of the header gradient.'),
'--color-topnav-wordmark':('Header logo text','Header & top bar','Store-name wordmark color in the header.'),
'--color-footer-bg':('Footer background','Footer','Main footer background.'),
'--color-footer-bar':('Footer bottom bar','Footer','Bottom copyright bar of the footer.'),
'--color-footer-subbar':('Footer sub bar','Footer','Secondary strip inside the footer.'),
'--color-footer-text-muted':('Footer text','Footer','Footer links and text.'),
'--color-flash-sale-bg':('Flash sale background','Flash sale','Background of the flash sale section.'),
'--color-flash-from':('Flash sale gradient (start)','Flash sale','Start color of the flash sale gradient.'),
'--color-flash-via':('Flash sale gradient (middle)','Flash sale','Middle color of the flash sale gradient.'),
'--color-flash-to':('Flash sale gradient (end)','Flash sale','End color of the flash sale gradient.'),
'--color-flash-stripe':('Flash sale stripes','Flash sale','Diagonal stripe pattern over the flash sale background.'),
'--color-flash-timer-blue':('Flash sale timer','Flash sale','Countdown timer boxes of the flash sale.'),
'--color-flash-rainbow-pink':('Flash sale rainbow 1','Flash sale','1st band of the flash sale rainbow decoration.'),
'--color-flash-rainbow-orange':('Flash sale rainbow 2','Flash sale','2nd band of the flash sale rainbow decoration.'),
'--color-flash-rainbow-yellow':('Flash sale rainbow 3','Flash sale','3rd band of the flash sale rainbow decoration.'),
'--color-flash-rainbow-teal':('Flash sale rainbow 4','Flash sale','4th band of the flash sale rainbow decoration.'),
'--color-flash-rainbow-purple':('Flash sale rainbow 5','Flash sale','5th band of the flash sale rainbow decoration.'),
'--color-hero-heading':('Hero heading','Hero banner','Main heading text of the hero banner.'),
'--color-hero-subtitle':('Hero subtitle','Hero banner','Sub-text of the hero banner.'),
'--color-hero-placeholder':('Hero placeholder','Hero banner','Placeholder tint shown while hero images load.'),
'--color-hero-dot-inactive':('Hero dots (inactive)','Hero banner','Inactive pagination dots under the hero banner.'),
'--color-dot-active':('Carousel dot (active)','Sections & decorations','Active pagination dot of carousels.'),
'--color-dot-inactive':('Carousel dot (inactive)','Sections & decorations','Inactive pagination dots of carousels.'),
'--color-icon-chip-bg':('Icon chip background','Sections & decorations','Translucent chip behind small icons.'),
'--color-placeholder-icon':('Placeholder icon','Sections & decorations','Icon color of empty-image placeholders.'),
'--color-bestseller-grad-1':('Best sellers gradient 1','Sections & decorations','1st color of the best sellers section gradient.'),
'--color-bestseller-grad-2':('Best sellers gradient 2','Sections & decorations','2nd color of the best sellers section gradient.'),
'--color-bestseller-grad-3':('Best sellers gradient 3','Sections & decorations','3rd color of the best sellers section gradient.'),
'--color-ribbon-red-1':('Ribbon red (1)','Sections & decorations','Red gradient of delivery / urgency ribbons (start).'),
'--color-ribbon-red-2':('Ribbon red (2)','Sections & decorations','Red gradient of delivery / urgency ribbons (middle).'),
'--color-ribbon-red-3':('Ribbon red (3)','Sections & decorations','Red gradient of delivery / urgency ribbons (end).'),
'--color-urgency-red-1':('Urgency red (1)','Sections & decorations','Red gradient of urgency labels (start).'),
'--color-urgency-red-2':('Urgency red (2)','Sections & decorations','Red gradient of urgency labels (middle).'),
'--color-urgency-red-3':('Urgency red (3)','Sections & decorations','Red gradient of urgency labels (end).'),
'--color-tile-bags':('Category tile: Bags','Sections & decorations','Placeholder tile color for the Bags category.'),
'--color-tile-footwear':('Category tile: Footwear','Sections & decorations','Placeholder tile color for the Footwear category.'),
'--color-tile-outerwear':('Category tile: Outerwear','Sections & decorations','Placeholder tile color for the Outerwear category.'),
'--color-tile-watches':('Category tile: Watches','Sections & decorations','Placeholder tile color for the Watches category.'),
'--color-blob-blue':('Blue blob','Sections & decorations','Decorative blue background blob.'),
'--color-sale-light':('Sale (light)','Status, prices & badges','Very light red used behind sale notices.'),
}
def guess(name):
    n=name.replace('--color-','')
    label=' '.join(w.capitalize() for w in n.split('-'))
    g='Brand & accents'
    for pat,grp in [(r'^(text|.*-text)',  'Text'),(r'bg|surface|card|section|page',  'Backgrounds & surfaces'),(r'footer','Footer'),(r'flash','Flash sale'),(r'hero','Hero banner'),(r'top|nav|utility','Header & top bar'),(r'badge|price|discount|progress|success|error','Status, prices & badges'),(r'gray|black|white','Neutrals'),(r'stroke|border|track','Borders & lines')]:
        if re.search(pat,n): g=grp; break
    return label,g
VARIANTS={ # theme-variant -> (name, description, default fonts ltr/rtl)
'ecommet-v1':('Ecommet — Default','Classic red-and-white marketplace look with a dark navigation header. Brand colors are baked into utility styles, so the list below is the set of colors the theme exposes.',('outfit','cairo')),
'elora-v1':('Elora — Default','Light, clean storefront with an orange accent. The primary/secondary colors drive buttons, links and highlights.',('outfit','cairo')),
'elora-v2':('Elora — Purple Edition','Purple-gradient edition with rounded cards and a vibrant flash sale block.',('outfit','cairo')),
'elora-v3':('Elora — Fresh Edition','Fresh pink/coral accents with a yellow features bar and wide New In cards.',('outfit','tajawal')),
'elora-v4':('Elora — Bold Edition','High-contrast edition with bold orange accents and large product cards.',('outfit','almarai')),
'elora-v5':('Elora — Minimal Edition','Minimal blue edition with a gold New In section and compact wide cards.',('outfit','ibm_plex_ar')),
'elora-v6':('Elora — New In Edition','Edition that highlights new arrivals with orange accents.',('outfit','readex_pro')),
'souqify-v1':('Souqify — Default','Neutral dark default edition. The primary color drives buttons, header and highlights.',('outfit','cairo')),
'souqify-v2':('Souqify — Purple Edition','Purple brand edition with a gradient header and mini product cards.',('outfit','cairo')),
'souqify-v3':('Souqify — Modern Edition','Modern teal edition with a clean header and compact deal cards.',('outfit','tajawal')),
'souqify-v4':('Souqify — Green Edition','Green edition with bold hero and flash sale panels.',('outfit','almarai')),
'souqify-v5':('Souqify — Orange Edition','Warm orange edition with a yellow features bar and patterned sections.',('outfit','readex_pro')),
'souqify-v6':('Souqify — Pink Edition','Pink edition with a scalloped best-seller section and rounded flash sale cards.',('outfit','cairo')),
}

# ── Tailwind-v3 themes: palette entries exposed as real CSS variables (see resources/tailwind-v3/*.config.cjs) ──
def T(label,group,desc,default,areas,classes=()): return {'label':label,'group':group,'description':desc,'default':default,'uses':99,'areas':areas,'classes':list(classes)}
CLS={'--color-brand':['primary'],'--color-brand-alt':['secondary'],'--color-main':['main'],'--color-main-light':['main-light','main-l'],'--color-ink':['black','elblack'],'--color-charcoal':['charcoal'],'--color-gray-1':['gray1'],'--color-gray-2':['gray2'],'--color-gray-3':['gray3'],'--color-gray-4':['elgray'],'--color-fill':['fill','elfill'],'--color-bg':['bg'],'--color-green':['green','elgreen'],'--color-red':['red'],'--color-purple':['purple'],'--color-blue':['blue']}
EXTRA={
'ecommet-v1':{
 '--color-brand':T('Brand color (red)','Brand & accents','Ecommet brand red: the header bar, flash sale, cart and order highlights, call-to-action buttons, tags and badges.','#de1709',['Header & navigation','Buttons','Badges']),
 '--color-brand-alt':T('Brand accent (amber)','Brand & accents','Amber accent used for ratings, secondary buttons and small highlights.','#ffaf09',['Buttons','Product cards']),
},
'elora-v1':{
 '--color-main':T('Brand color (orange)','Brand & accents','Elora brand orange: active category and tab highlights, header accents and small call-to-action states.','#ff4d00',['Header & navigation','Buttons','Product cards']),
 '--color-main-light':T('Brand color (light)','Brand & accents','Soft tint of the brand color used for borders and highlights in the tabbed products section.','#ffac88',['Buttons','Product cards']),
 '--color-ink':T('Text & icons (ink)','Text','Dark text and icon color on the product, cart, checkout and account pages.','#171717',['Global styles']),
 '--color-charcoal':T('Text (charcoal)','Text','Charcoal text used in the header, category cards and the hero banner.','#242424',['Product cards']),
 '--color-gray-1':T('Text (grey 1)','Text','Dark grey text for descriptions and secondary headings.','#3b3b3b',['Global styles']),
 '--color-gray-2':T('Text (grey 2)','Text','Medium-dark grey text for captions.','#5b5b5b',['Global styles']),
 '--color-gray-3':T('Text (grey 3)','Text','Light grey hint text on product cards.','#adadad',['Global styles']),
 '--color-gray-4':T('Grey (neutral)','Neutrals','Neutral grey for icons, borders and muted labels.','#8f8f8f',['Global styles']),
 '--color-fill':T('Card background','Backgrounds & surfaces','Background of product cards and category cards.','#fdfdfd',['Product cards']),
 '--color-bg':T('Page background (soft)','Backgrounds & surfaces','Soft grey background on the product page and header.','#f6f5f5',['Global styles']),
 '--color-green':T('Green (success)','Status, prices & badges','Green used for in-stock, delivery text and success icons.','#2aaf2f',['Product cards']),
 '--color-red':T('Red (sale / error)','Status, prices & badges','Red used for sale tags, errors and out-of-stock labels.','#de1709',['Product cards']),
 '--color-purple':T('Purple accent','Brand & accents','Purple accent used for special labels.','#8a38f5',['Global styles']),
 '--color-blue':T('Blue accent','Brand & accents','Blue accent used for links and info highlights.','#002b97',['Global styles']),
},
}
LABEL_OVERRIDES={
 'ecommet-v1':{'--color-primary':('Shop accent (dark)','Brand & accents','Dark accent for buttons, progress bars and highlights on the product, cart and checkout pages.'),
               '--color-secondary':('Shop accent (grey)','Brand & accents','Secondary accent for subtle highlights.')},
 'elora-v1':{'--color-primary':('Shop accent (dark)','Brand & accents','Dark accent for buttons, checkboxes, progress bars and highlights on the product, cart and category pages.')},
 'souqify-v2':{'--color-primary':('Shop pages accent','Brand & accents','Accent used on the cart, favorites, orders and other shared shop pages and components. The edition\'s main brand color is a separate color.')},
 'souqify-v6':{'--color-primary':('Shop pages accent','Brand & accents','Accent used on the cart, favorites, orders and other shared shop pages and components. The edition\'s main brand color is a separate color.')},
 'souqify-v4':{'--color-primary':('Shop pages accent','Brand & accents','Accent used on the cart, favorites, orders and other shared shop pages and components. The edition\'s main brand color is a separate color.')},
 'souqify-v3':{'--color-primary':('Shop pages accent','Brand & accents','Accent used on the cart, favorites, orders and other shared shop pages and components. The edition\'s main brand color is a separate color.')},
 'souqify-v1':{'--color-primary':('Brand color','Brand & accents','Main brand color of this edition: header, buttons, links, badges, progress bars and active states across the whole store.')},
}

# ── Live-preview roles (Appearance > Colors & Fonts) ─────────────────────────────────────────────────────────
# A role is a slot of the mini storefront preview. For every edition the first token that exists in its list drives
# the role, so the preview shows that edition's real brand/background/text tokens.
BRAND={'ecommet-v1':'--color-brand','elora-v1':'--color-main','elora-v2':'--color-primary','elora-v3':'--color-primary','elora-v4':'--color-primary','elora-v5':'--color-primary','elora-v6':'--color-primary',
       'souqify-v1':'--color-primary','souqify-v2':'--color-brand-purple','souqify-v3':'--color-souqify-teal','souqify-v4':'--color-brand-green','souqify-v5':'--color-primary','souqify-v6':'--color-brand-pink'}
ROLE_TOKENS={
 'brand-alt':['--color-secondary','--color-brand-alt','--color-brand-orange','--color-accent-yellow','--color-yellow','--color-accent-amber'],
 'text':['--color-text-primary','--color-text-heading','--color-card-title','--color-black-alt','--color-ink','--color-charcoal','--color-gray-darkest','--color-black'],
 'text-muted':['--color-text-subtitle','--color-subtitle','--color-text-muted','--color-desc-gray','--color-gray-3','--color-gray-medium','--color-text-faint'],
 'page-bg':['--color-page-bg','--color-bg-main','--color-bg','--color-fill','--color-gray-lightest','--color-surface'],
 'surface':['--color-surface','--color-card-bg-soft','--color-card-image-bg','--color-fill','--color-bg-main','--color-white'],
 'border':['--color-stroke','--color-gray-lighter','--color-gray-light','--color-progress-track'],
 'topbar-bg':['--color-topbar','--color-utility-bar-bg','--color-navbar-dark','--color-topnav-grad-dark'],
 'topbar-text':['--color-topbar-text','--color-utility-text','--color-navbar-muted','--color-topbar-muted'],
 'footer-bg':['--color-footer-bg'],'footer-bar':['--color-footer-bar'],'footer-text':['--color-footer-text-muted'],
 'price':['--color-price-blue','--color-price-alt-blue'],
 'discount':['--color-badge-discount-orange','--color-discount-orange','--color-badge-discount-yellow','--color-discount-yellow','--color-yellow'],
 'success':['--color-success','--color-accent-green'],'error':['--color-error'],
 'flash-bg':['--color-flash-sale-bg','--color-flash-from'],'flash-accent':['--color-flash-timer-blue','--color-flash-via'],
 'hero-title':['--color-hero-heading'],'hero-sub':['--color-hero-subtitle'],
 'white':['--color-white'],'black':['--color-black'],
}
def build_roles(key,tokens):
    r={}
    b=BRAND.get(key)
    if b in tokens: r['brand']=b
    for role,cands in ROLE_TOKENS.items():
        for c in cands:
            if c in tokens and c!=r.get('brand'): r[role]=c; break
    return r

raw=None
out={}
for f in sorted(glob.glob(S+'*.json')):
    d=json.load(open(f)); key=d['label']; theme,var=key.split('-')
    files=files_for(theme,var); txt={rel:open(R+rel,errors='ignore').read() for rel in files}
    toks={}
    for n,v in d['tokens'].items():
        if pal.match(n) or skip.match(n): continue
        c=v['computed']
        if not re.match(r'^#[0-9a-fA-F]{3,8}$',c.strip()) or v['raw'].startswith('var('): continue
        use=collections.Counter()
        for rel,t in txt.items(): use.update(usage(t,rel,n))
        total=sum(use.values())
        label,group,desc=LABEL_OVERRIDES.get(key,{}).get(n) or CUR.get(n,(None,None,None))
        if not label: label,group=guess(n); desc=label+'.'
        areas=[a for a,_ in use.most_common(4)]
        if total==0: continue
        toks[n]={'label':label,'group':group,'description':desc,'default':norm(c) if c.startswith('#') else c,'uses':total,'areas':areas}
    for tok,spec in EXTRA.get(key,{}).items():
        cnt=collections.Counter()
        for rel,t in txt.items():
            if not rel.endswith('.php'): continue
            for c in CLS.get(tok,[]):
                n=len(re.findall(r'(?<![\w-])(?:[a-z0-9:!-]+:)?(?:bg|text|border|from|to|via|fill|stroke|ring|outline|divide|placeholder|accent|shadow|decoration|caret)-'+re.escape(c)+r'(?:/\d+)?(?![\w-])',t))
                if n: cnt[area(rel)]+=n
        if sum(cnt.values())==0: continue
        spec=dict(spec); spec.pop('classes',None); spec['uses']=sum(cnt.values()); spec['areas']=[a for a,_ in cnt.most_common(4)]
        toks[tok]=spec
    name,desc,(ltr,rtl)=VARIANTS[key]
    if BRAND.get(key) in toks:   # the edition's own brand color leads the list
        toks={BRAND[key]:toks[BRAND[key]],**{k:v for k,v in toks.items() if k!=BRAND[key]}}
    out[key]={'theme':theme,'variant':var,'name':name,'description':desc,'fonts':{'ltr':ltr,'rtl':rtl},'roles':build_roles(key,toks),'tokens':toks}
os.makedirs(R+'resources/theme-tokens',exist_ok=True)
json.dump({'groups':GROUPS,'variants':out},open(R+'resources/theme-tokens/catalog.json','w'),indent=1,ensure_ascii=False)
for k,v in out.items():
    unused=[n for n,t in v['tokens'].items() if t['uses']==0]
    print(f"{k:11} {len(v['tokens']):3} tokens, unused(no var() usage found): {len(unused)} {unused[:5]}")
