<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\TemplatePart;
use Illuminate\Database\Seeder;

/**
 * Seeds one "Default Header" + "Default Footer" template_part per Template by
 * copying the matching on-disk blade file into the `content` column. Idempotent:
 * re-running never touches a row if it already exists for that template+slug.
 */
class TemplatePartsSeeder extends Seeder
{
    public function run(): void
    {
        $templates = Template::query()->get();

        foreach ($templates as $template) {
            foreach (['header', 'footer'] as $type) {
                $this->seedPart($template, $type);
            }
        }
    }

    protected function seedPart(Template $template, string $type): void
    {
        $slug = 'default-' . $type;

        $exists = TemplatePart::query()
            ->where('template_id', $template->id)
            ->where('slug', $slug)
            ->exists();

        if ($exists) {
            return;
        }

        $content = $this->loadBladeContent($template, $type);

        TemplatePart::create([
            'template_id' => $template->id,
            'type' => $type,
            'name' => 'Default ' . ucfirst($type),
            'slug' => $slug,
            'content' => $content,
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    protected function loadBladeContent(Template $template, string $type): string
    {
        $slug = $template->slug ?? $template->folder ?? null;
        if (!$slug) {
            return '';
        }

        $path = resource_path("views/themes/{$slug}/layout/{$type}.blade.php");

        return is_file($path) ? file_get_contents($path) : '';
    }
}
