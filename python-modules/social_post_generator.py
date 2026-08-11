"""
social_post_generator.py
────────────────────────
Generates marketing copy and a companion image for a product, per social
platform. Uses OpenAI's chat + image APIs. A single OPENAI_API_KEY env var is
required; if the key is missing every call raises RuntimeError so the Laravel
side gets a clear failure rather than a silent empty response.

Supported platforms: instagram, facebook, twitter, linkedin, tiktok, generic.
Pass platform="all" to get one tailored post per platform in a single call.
"""

from __future__ import annotations

import base64
import logging
import os
import urllib.request
from dataclasses import asdict, dataclass
from typing import Dict, List, Optional

from openai import OpenAI

SUPPORTED_PLATFORMS = ["instagram", "facebook", "twitter", "linkedin", "tiktok", "generic"]

# Platform-specific copy briefs. Kept short so the model gets clear direction
# without prescribing exact wording.
PLATFORM_BRIEFS: Dict[str, str] = {
    "instagram": (
        "Instagram post: visually-driven, upbeat, 2-3 short paragraphs, "
        "3-6 relevant hashtags at the end, use 2-3 emojis naturally."
    ),
    "facebook": (
        "Facebook post: conversational, 1-2 paragraphs, "
        "end with a soft call-to-action (e.g. 'Shop now'), 1-2 emojis max."
    ),
    "twitter": (
        "Twitter/X post: under 260 characters total, punchy, "
        "one strong hook line, up to 2 hashtags, no emoji overload."
    ),
    "linkedin": (
        "LinkedIn post: professional tone, lead with a value proposition, "
        "3-4 short lines, no emojis, no hashtags stacking - max 3."
    ),
    "tiktok": (
        "TikTok caption: very short, trend-friendly, 1 hook line, "
        "2-4 hashtags including one trend-style (#fyp style allowed)."
    ),
    "generic": (
        "Generic social post: neutral tone, 2 short paragraphs, "
        "end with a call-to-action and 3 hashtags."
    ),
}

# Recommended image dimensions per platform (informational - we generate 1024x1024
# and the caller can crop/resize to these on the client side).
PLATFORM_IMAGE_HINTS: Dict[str, Dict[str, int]] = {
    "instagram": {"width": 1080, "height": 1080},
    "facebook": {"width": 1200, "height": 630},
    "twitter": {"width": 1200, "height": 675},
    "linkedin": {"width": 1200, "height": 627},
    "tiktok": {"width": 1080, "height": 1920},
    "generic": {"width": 1080, "height": 1080},
}

# Approximate character limits for copy guidance.
PLATFORM_CHAR_LIMITS: Dict[str, int] = {
    "instagram": 2200,
    "facebook": 500,
    "twitter": 260,
    "linkedin": 700,
    "tiktok": 150,
    "generic": 500,
}


@dataclass
class SocialPost:
    platform: str
    caption: str
    char_limit: int
    image_hint: Dict[str, int]


@dataclass
class SocialPostBundle:
    posts: List[dict]
    generated_image_b64: Optional[str]
    model_text: str
    model_image: str


def _client() -> OpenAI:
    api_key = os.getenv("OPENAI_API_KEY")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY environment variable is not set.")
    return OpenAI(api_key=api_key)


def _build_prompt(
    platform: str,
    title: str,
    description: str,
    language_code: Optional[str] = None,
    language_name: Optional[str] = None,
) -> str:
    brief = PLATFORM_BRIEFS[platform]
    limit = PLATFORM_CHAR_LIMITS[platform]
    language_label = (language_name or "").strip() or (language_code or "").strip()

    language_instruction = (
        f"Write the caption in {language_label}.\n"
        if language_label
        else ""
    )

    return (
        "You write high-performing social media copy.\n\n"
        f"Platform brief: {brief}\n"
        f"Character limit: approximately {limit} characters.\n"
        f"{language_instruction}\n"
        f"Product title: {title}\n"
        f"Product description: {description}\n\n"
        f"Write ONE ready-to-publish caption for this product on {platform}. "
        "Do not include any preamble, labels, quotes, or explanation - output the caption only."
    )


def _generate_caption(
    client: OpenAI,
    platform: str,
    title: str,
    description: str,
    model: str,
    language_code: Optional[str] = None,
    language_name: Optional[str] = None,
) -> str:
    prompt = _build_prompt(platform, title, description, language_code, language_name)
    resp = client.chat.completions.create(
        model=model,
        messages=[
            {"role": "system", "content": "You are an expert social media copywriter."},
            {"role": "user", "content": prompt},
        ],
        temperature=0.8,
        max_tokens=500,
    )
    return (resp.choices[0].message.content or "").strip()


def _generate_image(client: OpenAI, title: str, description: str, model: str) -> Optional[str]:
    prompt = (
        f"A professional product advertisement banner for '{title}'. "
        f"{description}. "
        "Studio-quality photography with dramatic moody lighting, dark or gradient background, "
        "product centered and sharply in focus. "
        f"Overlay bold elegant typography: the product name '{title}' in large white or gold sans-serif font "
        "at the top, and a short tagline like 'Premium Quality' or 'Shop Now' in smaller text at the bottom. "
        "Design looks like a high-end magazine advertisement or luxury e-commerce banner. "
        "Clean layout, minimal clutter, cinematic color grading."
    )
    try:
        resp = client.images.generate(
            model=model,
            prompt=prompt,
            size="1024x1024",
            n=1,
        )
        data = resp.data[0]
        if data.b64_json:
            return data.b64_json
        with urllib.request.urlopen(data.url) as response:
            return base64.b64encode(response.read()).decode()
    except Exception as exc:
        logging.getLogger(__name__).error("Image generation failed: %s", exc)
        return None


def generate_posts(
    title: str,
    description: str,
    platform: str = "all",
    include_image: bool = True,
    text_model: Optional[str] = None,
    image_model: Optional[str] = None,
    language_code: Optional[str] = None,
    language_name: Optional[str] = None,
) -> SocialPostBundle:
    title = (title or "").strip()
    description = (description or "").strip()
    if not title:
        raise ValueError("title is required")
    if not description:
        raise ValueError("description is required")

    platform = platform.lower().strip()
    if platform != "all" and platform not in SUPPORTED_PLATFORMS:
        raise ValueError(
            f"Unsupported platform '{platform}'. "
            f"Allowed: {', '.join(SUPPORTED_PLATFORMS)} or 'all'."
        )

    text_model = text_model or os.getenv("OPENAI_TEXT_MODEL", "gpt-4o-mini")
    image_model = image_model or os.getenv("OPENAI_IMAGE_MODEL", "gpt-image-1")

    client = _client()
    platforms = SUPPORTED_PLATFORMS if platform == "all" else [platform]

    posts: List[SocialPost] = []
    for selected_platform in platforms:
        caption = _generate_caption(
            client,
            selected_platform,
            title,
            description,
            text_model,
            language_code=language_code,
            language_name=language_name,
        )
        posts.append(
            SocialPost(
                platform=selected_platform,
                caption=caption,
                char_limit=PLATFORM_CHAR_LIMITS[selected_platform],
                image_hint=PLATFORM_IMAGE_HINTS[selected_platform],
            )
        )

    image_b64 = _generate_image(client, title, description, image_model) if include_image else None

    return SocialPostBundle(
        posts=[asdict(post) for post in posts],
        generated_image_b64=image_b64,
        model_text=text_model,
        model_image=image_model,
    )


def decode_image_to_file(b64: str, path: str) -> str:
    """Helper: persist a base64 image bundle to a PNG file on disk."""
    with open(path, "wb") as file_handle:
        file_handle.write(base64.b64decode(b64))
    return path
