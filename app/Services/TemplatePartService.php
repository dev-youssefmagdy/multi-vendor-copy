<?php

namespace App\Services;

use App\Models\Template;
use App\Models\TemplatePart;
use Illuminate\Support\Str;

class TemplatePartService
{
    /**
     * Create a new template part. If `is_default` is true, any existing default
     * for the same (template, type) is demoted first.
     */
    public function create(Template $template, array $data): TemplatePart
    {
        $type = $this->validateType($data['type'] ?? 'header');
        $slug = $this->ensureUniqueSlug(
            $template,
            $data['slug'] ?? Str::slug($data['name'] ?? ($type . '-' . uniqid()))
        );

        $part = TemplatePart::create([
            'template_id' => $template->id,
            'type' => $type,
            'name' => $data['name'] ?? ucfirst($type),
            'slug' => $slug,
            'content' => $data['content'] ?? '',
            'image' => $data['image'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_default' => false,
        ]);

        if (!empty($data['is_default'])) {
            $this->setDefault($part);
        }

        return $part->refresh();
    }

    public function update(TemplatePart $part, array $data): TemplatePart
    {
        if (array_key_exists('name', $data)) {
            $part->name = $data['name'];
        }

        if (array_key_exists('content', $data)) {
            $part->content = (string) $data['content'];
        }

        if (array_key_exists('image', $data)) {
            $part->image = $data['image'];
        }

        if (array_key_exists('is_active', $data)) {
            $part->is_active = (bool) $data['is_active'];
        }

        $part->save();

        if (!empty($data['is_default'])) {
            $this->setDefault($part);
        }

        return $part->refresh();
    }

    /**
     * Promote $part to be the default for its (template_id, type) — demotes
     * any sibling that was previously default. Also marks the part active.
     */
    public function setDefault(TemplatePart $part): TemplatePart
    {
        TemplatePart::query()
            ->where('template_id', $part->template_id)
            ->where('type', $part->type)
            ->where('id', '!=', $part->id)
            ->update(['is_default' => false]);

        $part->forceFill([
            'is_default' => true,
            'is_active' => true,
        ])->save();

        return $part;
    }

    public function setActive(TemplatePart $part, bool $active): TemplatePart
    {
        $part->is_active = $active;
        $part->save();

        return $part;
    }

    public function delete(TemplatePart $part): void
    {
        $part->delete();
    }

    protected function validateType(string $type): string
    {
        $type = strtolower(trim($type));

        if (!in_array($type, TemplatePart::TYPES, true)) {
            throw new \InvalidArgumentException("Unsupported template part type: {$type}");
        }

        return $type;
    }

    protected function ensureUniqueSlug(Template $template, string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug) ?: 'part';
        $candidate = $base;
        $i = 2;

        while (
            TemplatePart::query()
                ->where('template_id', $template->id)
                ->where('slug', $candidate)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base . '-' . $i++;
        }

        return $candidate;
    }
}
