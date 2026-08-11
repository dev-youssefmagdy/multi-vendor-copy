"""
logo_generator.py
─────────────────
Generates a store logo image using OpenAI's image generation API (DALL·E 3).

Takes store details (name, tagline, category, style, color preferences) and
produces a professional logo-style image returned as base64-encoded PNG.

Single public function:
    generate_logo(store_name, ...) -> LogoResult
"""

from __future__ import annotations

import base64
import os
from dataclasses import dataclass
from typing import Optional

from openai import OpenAI

# ── Style briefs ──────────────────────────────────────────────────────────────

STYLE_BRIEFS: dict[str, str] = {
    "modern": (
        "clean, modern logo design with geometric shapes, bold sans-serif typography, "
        "flat design aesthetic, professional and contemporary look"
    ),
    "minimalist": (
        "minimalist logo, simple and clean, lots of whitespace, thin lines, "
        "elegant simplicity, sophisticated negative space usage"
    ),
    "playful": (
        "fun and playful logo, rounded shapes, friendly typography, "
        "vibrant and energetic, approachable and cheerful design"
    ),
    "luxury": (
        "luxury premium logo, elegant serif typography, gold or metallic accents, "
        "sophisticated and exclusive, high-end brand identity"
    ),
    "vintage": (
        "vintage retro logo design, classic badge or emblem style, "
        "distressed textures, nostalgic feel, timeless appeal"
    ),
    "bold": (
        "bold and strong logo, high contrast, thick typography, "
        "powerful brand presence, confident and assertive design"
    ),
    "tech": (
        "technology-focused logo, circuit-board or digital elements, "
        "futuristic feel, sleek and innovative, modern tech aesthetic"
    ),
    "organic": (
        "organic natural logo, nature-inspired elements, earthy tones, "
        "handcrafted feel, sustainable and eco-friendly aesthetic"
    ),
}

CATEGORY_CONTEXTS: dict[str, str] = {
    "fashion": "clothing, apparel, accessories, fashion brand",
    "electronics": "electronics, gadgets, technology products",
    "food": "food, beverages, restaurant, culinary brand",
    "beauty": "beauty, cosmetics, skincare, wellness brand",
    "sports": "sports, fitness, athletic, active lifestyle brand",
    "home": "home decor, furniture, interior design, lifestyle brand",
    "jewelry": "jewelry, accessories, fine goods, artisan brand",
    "books": "books, education, knowledge, publishing brand",
    "toys": "toys, children, games, playful brand",
    "general": "online store, e-commerce, retail brand",
}

DEFAULT_IMAGE_MODEL = "gpt-image-1"
DEFAULT_IMAGE_SIZE = "1024x1024"


# ── Data classes ──────────────────────────────────────────────────────────────

@dataclass
class LogoResult:
    image_b64: str           # Base64-encoded PNG
    prompt_used: str         # The final prompt sent to DALL·E
    model: str               # Model used for generation
    revised_prompt: Optional[str] = None  # DALL·E 3 may revise the prompt


# ── Internal helpers ──────────────────────────────────────────────────────────

def _client() -> OpenAI:
    api_key = os.getenv("OPENAI_API_KEY")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY environment variable is not set.")
    return OpenAI(api_key=api_key)


def _build_logo_prompt(
    store_name: str,
    tagline: Optional[str],
    category: str,
    style: str,
    colors: Optional[str],
) -> str:
    style_desc = STYLE_BRIEFS.get(style, STYLE_BRIEFS["modern"])
    category_desc = CATEGORY_CONTEXTS.get(category, CATEGORY_CONTEXTS["general"])
    color_instruction = (
        f"Color palette: {colors}. " if colors else "Use a professional, brand-appropriate color palette. "
    )
    tagline_part = f'Tagline or slogan: "{tagline}". ' if tagline else ""

    prompt = (
        f"Create a professional store logo for '{store_name}', a {category_desc}. "
        f"{tagline_part}"
        f"Design style: {style_desc}. "
        f"{color_instruction}"
        f"The logo should prominently feature the store name '{store_name}' as legible text. "
        f"Square format, centered composition, white or transparent background, "
        f"vector-art quality, suitable for use as a brand logo on a website header and invoices. "
        f"No watermarks, no extra text, no frames or borders around the entire image."
    )
    return prompt


# ── Public API ────────────────────────────────────────────────────────────────

def generate_logo(
    store_name: str,
    tagline: Optional[str] = None,
    category: str = "general",
    style: str = "modern",
    colors: Optional[str] = None,
    model: Optional[str] = None,
) -> LogoResult:
    """
    Generate a store logo using DALL·E 3.

    Parameters
    ----------
    store_name : str
        The store name to feature in the logo.
    tagline : str, optional
        A short tagline or slogan to guide the design.
    category : str
        Store category — one of the CATEGORY_CONTEXTS keys (default: general).
    style : str
        Visual style — one of the STYLE_BRIEFS keys (default: modern).
    colors : str, optional
        Colour preferences, e.g. "blue and gold" or "#1a1a2e, #e94560".
    model : str, optional
        Override the image model (default: gpt-image-1).

    Returns
    -------
    LogoResult
        Dataclass with base64-encoded image, the prompt used, and model info.
    """
    if not store_name or not store_name.strip():
        raise ValueError("store_name is required.")

    # Normalise inputs
    category = category.lower().strip() if category else "general"
    if category not in CATEGORY_CONTEXTS:
        category = "general"

    style = style.lower().strip() if style else "modern"
    if style not in STYLE_BRIEFS:
        style = "modern"

    image_model = (model or os.getenv("OPENAI_IMAGE_MODEL", DEFAULT_IMAGE_MODEL)).strip()
    prompt = _build_logo_prompt(store_name, tagline, category, style, colors)

    client = _client()

    response = client.images.generate(
        model=image_model,
        prompt=prompt,
        size=DEFAULT_IMAGE_SIZE,
        quality="auto",
        n=1,
    )

    image_data = response.data[0]
    b64 = image_data.b64_json
    if not b64:
        raise RuntimeError("DALL·E returned an empty image response.")

    return LogoResult(
        image_b64=b64,
        prompt_used=prompt,
        model=image_model,
        revised_prompt=getattr(image_data, "revised_prompt", None),
    )


# ── Supported options (exported for the FastAPI schema) ──────────────────────

SUPPORTED_STYLES = list(STYLE_BRIEFS.keys())
SUPPORTED_CATEGORIES = list(CATEGORY_CONTEXTS.keys())
