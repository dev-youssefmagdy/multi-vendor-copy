"""
ai_config.py
────────────
Per-request AI configuration.

The source of truth for the OpenAI key, base URL and model names is the Laravel central
database (Admin → Settings → AI Settings, table `app_settings`). Laravel forwards them on every
call to this service as headers (see App\\Support\\AiSettings::pythonHeaders()):

    X-OpenAI-Key, X-OpenAI-Base-Url, X-OpenAI-Text-Model, X-OpenAI-Image-Model,
    X-OpenAI-Vision-Model, X-OpenAI-Search-Model, X-OpenAI-Embedding-Model

A middleware in app.py stores them in a ContextVar for the duration of the request
(contextvars are copied into worker threads, so sync endpoints and asyncio.to_thread see them).
Environment variables (.env) are only a fallback for local runs / manual curl tests.

Never log these values.
"""

from __future__ import annotations

import os
from contextvars import ContextVar
from typing import Dict, Optional

from openai import OpenAI

# setting name -> (request header, env var, default)
_SETTINGS = {
    "api_key": ("x-openai-key", "OPENAI_API_KEY", ""),
    "base_url": ("x-openai-base-url", "OPENAI_BASE_URL", ""),
    "text_model": ("x-openai-text-model", "OPENAI_TEXT_MODEL", "gpt-4o-mini"),
    "image_model": ("x-openai-image-model", "OPENAI_IMAGE_MODEL", "gpt-image-1"),
    "vision_model": ("x-openai-vision-model", "OPENAI_VISION_MODEL", "gpt-4o-mini"),
    "search_model": ("x-openai-search-model", "OPENAI_SEARCH_MODEL", "gpt-4o-mini"),
    "embedding_model": ("x-openai-embedding-model", "OPENAI_IMAGE_EMBEDDING_MODEL", "text-embedding-3-small"),
}

HEADER_NAMES = {header for header, _, _ in _SETTINGS.values()}

_request_overrides: ContextVar[Dict[str, str]] = ContextVar("ai_request_overrides", default={})


def set_request_overrides(headers: Dict[str, str]) -> object:
    """Store the AI headers of the current request. Returns a token for reset_request_overrides()."""
    values = {}
    for name, (header, _, _) in _SETTINGS.items():
        value = (headers.get(header) or "").strip()
        if value:
            values[name] = value
    return _request_overrides.set(values)


def reset_request_overrides(token: object) -> None:
    _request_overrides.reset(token)  # type: ignore[arg-type]


def get(name: str) -> str:
    """Request header (from the DB settings) → environment variable → default."""
    header, env, default = _SETTINGS[name]
    value = _request_overrides.get().get(name)
    if value:
        return value
    return (os.getenv(env) or "").strip() or default


def api_key() -> Optional[str]:
    return get("api_key") or None


def client() -> OpenAI:
    key = api_key()
    if not key:
        raise RuntimeError(
            "OPENAI_API_KEY is not configured. Set it in Admin → Settings → AI Settings."
        )
    base_url = get("base_url") or None
    return OpenAI(api_key=key, base_url=base_url) if base_url else OpenAI(api_key=key)
