"""
image_search.py
────────────────
Image Search v1 — expandable to vector DB (pgvector, Pinecone, etc.).

OpenAI does not expose a direct image-embedding endpoint, so this module
reuses the same two-step pattern as price_finder.py's image identification:
  1. OpenAI vision (gpt-4o-mini) turns the image into a short, dense visual
     description (object type, colors, materials, distinguishing features).
  2. That description is embedded with OpenAI's text-embedding-3-small model
     to produce a fixed-length vector suitable for cosine-similarity search.

This keeps the service dependency-light (no local CLIP/torch) while still
producing a real embedding vector. Swapping step 1+2 for a true CLIP image
encoder — or swapping the brute-force cosine similarity done by the Laravel
caller for an indexed vector DB — is a drop-in upgrade later; callers only
depend on this module returning a list[float] embedding.
"""

from __future__ import annotations

import base64
import mimetypes
import os
from dataclasses import dataclass
from typing import List, Optional

from openai import OpenAI

EMBEDDING_MODEL = os.getenv("OPENAI_IMAGE_EMBEDDING_MODEL", "text-embedding-3-small")
VISION_MODEL = os.getenv("OPENAI_VISION_MODEL", "gpt-4o-mini")


@dataclass
class ImageEmbeddingResult:
    embedding: List[float]
    dims: int
    description: str


def _image_to_data_url(image_path: str) -> str:
    mime, _ = mimetypes.guess_type(image_path)
    mime = mime or "image/jpeg"
    with open(image_path, "rb") as fh:
        encoded = base64.b64encode(fh.read()).decode("ascii")
    return f"data:{mime};base64,{encoded}"


def _resolve_image_url(image_path: str) -> str:
    if image_path.startswith("http://") or image_path.startswith("https://"):
        return image_path
    if os.path.isfile(image_path):
        return _image_to_data_url(image_path)
    raise ValueError(f"Image not found: {image_path}")


def _describe_image(image_url: str) -> str:
    api_key = os.getenv("OPENAI_API_KEY")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY environment variable is not set.")

    client = OpenAI(api_key=api_key)
    prompt = (
        "Describe this product image for a visual similarity search index. "
        "List the object type/category, shape, colors, materials, patterns, "
        "and any distinguishing visual features. Be dense and factual, no "
        "preamble, 1-2 sentences."
    )
    resp = client.chat.completions.create(
        model=VISION_MODEL,
        messages=[
            {
                "role": "user",
                "content": [
                    {"type": "text", "text": prompt},
                    {"type": "image_url", "image_url": {"url": image_url}},
                ],
            }
        ],
        temperature=0.1,
        max_tokens=120,
    )
    description = (resp.choices[0].message.content or "").strip()
    if not description:
        raise RuntimeError("Could not derive a visual description from the supplied image.")
    return description


def embed_image(image_path: str) -> ImageEmbeddingResult:
    """Turn a local image path or a public image URL into an embedding vector."""
    api_key = os.getenv("OPENAI_API_KEY")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY environment variable is not set.")

    image_url = _resolve_image_url(image_path)
    description = _describe_image(image_url)

    client = OpenAI(api_key=api_key)
    resp = client.embeddings.create(model=EMBEDDING_MODEL, input=description)
    vector = resp.data[0].embedding

    return ImageEmbeddingResult(embedding=vector, dims=len(vector), description=description)
