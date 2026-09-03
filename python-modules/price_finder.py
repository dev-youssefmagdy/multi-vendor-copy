"""
price_finder.py
───────────────
Heuristic product-price discovery. Given a product name and/or a product or
variant image (and optional currency hint), search the public web (DuckDuckGo
HTML search — no API key required), fetch the top shopping result pages, and
extract price candidates from the HTML.

When an image is supplied, OpenAI vision (gpt-4o-mini) is used to identify the
product (brand/model/distinguishing features) so the resulting description can
drive the same text-based search flow used for a typed product name.

Designed to be dependency-light: we purposely avoid site-specific scrapers so we
do not break when any single marketplace changes its HTML. Results are noisy by
nature; callers should treat them as suggestions, not ground truth.
"""

from __future__ import annotations

import asyncio
import base64
import mimetypes
import os
import re
import statistics
from dataclasses import dataclass, asdict
from typing import List, Optional
from urllib.parse import urlparse

import httpx
from bs4 import BeautifulSoup
try:
    from ddgs import DDGS
except ImportError:
    from duckduckgo_search import DDGS
from openai import OpenAI

# Only match USD-denominated amounts ($12.99, US$12.99, USD 12.99, 12.99 USD).
# Other currency symbols/codes (€, £, EUR, GBP, SAR, AED, EGP, ...) are deliberately
# NOT matched here: their numeric value is not USD and averaging it in as if it were
# would silently corrupt the reported price.
PRICE_PATTERNS = [
    re.compile(r"(?:US\$|\$|USD\s*)\s*([0-9]{1,6}(?:[.,][0-9]{2})?)", re.IGNORECASE),
    re.compile(r"([0-9]{1,6}(?:[.,][0-9]{2})?)\s*USD\b", re.IGNORECASE),
]

# Filter obviously-bogus prices (order of magnitude too small / too large).
MIN_PRICE = 0.5
MAX_PRICE = 1_000_000.0

DEFAULT_USER_AGENT = (
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/124.0 Safari/537.36"
)


@dataclass
class PriceHit:
    source: str
    price: float
    url: str
    title: str


@dataclass
class PriceReport:
    query: str
    hits: List[dict]
    average_price: Optional[float]
    median_price: Optional[float]
    min_price: Optional[float]
    max_price: Optional[float]
    sample_size: int


def _image_to_data_url(image_path: str) -> str:
    mime, _ = mimetypes.guess_type(image_path)
    mime = mime or "image/jpeg"
    with open(image_path, "rb") as fh:
        encoded = base64.b64encode(fh.read()).decode("ascii")
    return f"data:{mime};base64,{encoded}"


def _identify_product_from_image(image_path: str, hint_text: Optional[str] = None) -> str:
    """Use OpenAI vision to turn a product/variant image into a short search
    query (brand, model, distinguishing features) suitable for a price search.
    """
    api_key = os.getenv("OPENAI_API_KEY")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY environment variable is not set.")

    if image_path.startswith("http://") or image_path.startswith("https://"):
        image_url = image_path
    elif os.path.isfile(image_path):
        image_url = _image_to_data_url(image_path)
    else:
        raise ValueError(f"Image not found: {image_path}")

    prompt = (
        "Identify this product for an online shopping price search. "
        "Reply with ONLY a short search query (brand, model, key attributes such "
        "as size/color if visible) - no preamble, no punctuation beyond what a "
        "shopper would type."
    )
    if hint_text:
        prompt += f" Known product name/context: {hint_text}."

    client = OpenAI(api_key=api_key)
    model = os.getenv("OPENAI_VISION_MODEL", "gpt-4o-mini")
    resp = client.chat.completions.create(
        model=model,
        messages=[
            {
                "role": "user",
                "content": [
                    {"type": "text", "text": prompt},
                    {"type": "image_url", "image_url": {"url": image_url}},
                ],
            }
        ],
        temperature=0.2,
        max_tokens=60,
    )
    query = (resp.choices[0].message.content or "").strip()
    if not query:
        raise RuntimeError("Could not identify a product from the supplied image.")
    return query


_STOPWORDS = {
    "the", "a", "an", "and", "or", "for", "with", "of", "to", "in", "on",
    "price", "buy", "online", "how", "much", "cost", "retail", "usd",
    "comparison", "shop", "new", "set",
}


def _significant_words(text: str) -> set:
    words = re.findall(r"[a-z0-9]+", text.lower())
    return {w for w in words if len(w) > 2 and w not in _STOPWORDS}


def _is_relevant(query: str, text: str, min_overlap: float = 0.4) -> bool:
    """Require a meaningful fraction of the query's significant words to
    appear in the candidate title/snippet, so unrelated pages (accessories,
    different products, generic articles) that merely contain a price-shaped
    number are not accepted as hits for this product.
    """
    query_words = _significant_words(query)
    if not query_words:
        return True
    text_words = _significant_words(text)
    overlap = len(query_words & text_words) / len(query_words)
    return overlap >= min_overlap


def _parse_prices(text: str) -> List[float]:
    prices: List[float] = []
    for pattern in PRICE_PATTERNS:
        for match in pattern.finditer(text):
            raw = match.group(1).replace(",", ".")
            try:
                value = float(raw)
            except ValueError:
                continue
            if MIN_PRICE <= value <= MAX_PRICE:
                prices.append(value)
    return prices


# Path fragments indicating a category/search/listing page rather than a single
# product page. Fetching one of these and taking "the median price on the page"
# mixes together many unrelated products' prices into one bogus number (e.g. a
# balloon-arch category page returning $504.50 for an unrelated garland set).
_LISTING_PAGE_PATTERNS = (
    "/c/kp/", "/c/", "/s?", "/search", "/b/", "/cat/", "/category",
    "/browse", "/results", "/sr?",
)


def _looks_like_listing_page(url: str) -> bool:
    path = urlparse(url).path.lower()
    return any(p in path or p in url.lower() for p in _LISTING_PAGE_PATTERNS)


_SEARCH_QUERY_TEMPLATES = [
    '"{query}" price buy online',
    '"{query}" how much cost retail price USD',
    '"{query}" price comparison shop',
]


def _search_results(query: str, limit: int = 15) -> List[dict]:
    # Run multiple query templates to maximise price-bearing result coverage,
    # then deduplicate by URL so downstream fetches don't double-count.
    seen_urls: set = set()
    results: List[dict] = []
    per_template = max(6, limit // len(_SEARCH_QUERY_TEMPLATES))
    try:
        with DDGS() as ddgs:
            for template in _SEARCH_QUERY_TEMPLATES:
                q = template.format(query=query)
                for r in ddgs.text(
                    q,
                    region="wt-wt",
                    safesearch="moderate",
                    max_results=per_template,
                ):
                    url = r.get("href") or r.get("url") or ""
                    if url and url not in seen_urls:
                        seen_urls.add(url)
                        results.append(r)
                if len(results) >= limit * 2:
                    break
    except Exception:
        pass
    return results


async def _fetch(client: httpx.AsyncClient, url: str) -> Optional[str]:
    try:
        resp = await client.get(url, timeout=10.0, follow_redirects=True)
        if resp.status_code >= 400:
            return None
        return resp.text
    except Exception:
        return None


async def _gather_prices(query: str, limit: int) -> List[PriceHit]:
    results = _search_results(query, limit=limit)
    if not results:
        return []

    hits: List[PriceHit] = []
    urls_with_hits: set = set()

    # Primary: extract prices from search-result snippets (body field).
    # DuckDuckGo embeds price mentions directly in snippets — this avoids
    # fetching JS-heavy pages that render prices client-side.
    for result in results:
        snippet = result.get("body") or ""
        title = (result.get("title") or "").strip()
        url = result.get("href") or result.get("url") or ""
        combined = f"{snippet} {title}"
        if _looks_like_listing_page(url):
            continue
        if not _is_relevant(query, combined):
            continue
        candidates = _parse_prices(combined)
        if not candidates:
            continue
        rep_price = statistics.median(candidates)
        hits.append(
            PriceHit(
                source=urlparse(url).netloc or "unknown",
                price=round(rep_price, 2),
                url=url,
                title=title[:200],
            )
        )
        urls_with_hits.add(url)

    # Secondary: fetch pages that did not yield a price from the snippet.
    # Skip category/search/listing pages entirely — see _looks_like_listing_page.
    pages_to_fetch = [
        r for r in results
        if (r.get("href") or r.get("url") or "") not in urls_with_hits
        and not _looks_like_listing_page(r.get("href") or r.get("url") or "")
    ]

    if pages_to_fetch:
        headers = {"User-Agent": DEFAULT_USER_AGENT, "Accept-Language": "en-US,en;q=0.9"}
        async with httpx.AsyncClient(headers=headers) as client:
            pages = await asyncio.gather(
                *[_fetch(client, r.get("href") or r.get("url") or "") for r in pages_to_fetch]
            )

        for result, html in zip(pages_to_fetch, pages):
            if not html:
                continue
            soup = BeautifulSoup(html, "html.parser")
            for tag in soup(["script", "style", "noscript"]):
                tag.decompose()
            text = soup.get_text(" ", strip=True)
            title = (result.get("title") or "").strip()
            if not _is_relevant(query, f"{title} {text[:2000]}"):
                continue
            candidates = _parse_prices(text)
            if not candidates:
                continue
            rep_price = statistics.median(candidates)
            url = result.get("href") or result.get("url") or ""
            hits.append(
                PriceHit(
                    source=urlparse(url).netloc or "unknown",
                    price=round(rep_price, 2),
                    url=url,
                    title=(result.get("title") or "").strip()[:200],
                )
            )

    return hits


def _summarise(hits: List[PriceHit], query: str) -> PriceReport:
    prices = [h.price for h in hits]
    if not prices:
        return PriceReport(
            query=query,
            hits=[],
            average_price=None,
            median_price=None,
            min_price=None,
            max_price=None,
            sample_size=0,
        )

    # Outlier trim: drop values more than 3× the median to avoid "accessory"
    # listings throwing off the mean (e.g. cables for a phone query).
    med = statistics.median(prices)
    trimmed = [p for p in prices if p <= med * 3 and p >= med / 3] or prices

    return PriceReport(
        query=query,
        hits=[asdict(h) for h in hits],
        average_price=round(statistics.fmean(trimmed), 2),
        median_price=round(statistics.median(trimmed), 2),
        min_price=round(min(trimmed), 2),
        max_price=round(max(trimmed), 2),
        sample_size=len(trimmed),
    )


async def find_average_price(
    product_name: str = "",
    limit: int = 15,
    image_path: Optional[str] = None,
) -> PriceReport:
    product_name = (product_name or "").strip()
    image_path = (image_path or "").strip() or None

    if not product_name and not image_path:
        raise ValueError("product_name or image_path is required")

    query = product_name
    if image_path:
        # Run the (blocking) vision call in a worker thread so it doesn't block
        # the event loop, then use the identified description as the search query.
        query = await asyncio.to_thread(_identify_product_from_image, image_path, product_name or None)

    hits = await _gather_prices(query, limit=limit)
    return _summarise(hits, query)
