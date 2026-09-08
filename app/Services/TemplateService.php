<?php

namespace App\Services;

use App\Models\Template;

class TemplateService
{
    public function setActive(Template $template): Template
    {
        $template->forceFill(['is_active' => true])->save();

        return $template->fresh('previewFile');
    }

    public function setInactive(Template $template): Template
    {
        $template->forceFill(['is_active' => false])->save();

        return $template->fresh('previewFile');
    }

    public function setDefault(Template $template): Template
    {
        // Only templates available in every country can be the global default.
        // A template with specific country assignments would leave visitors in
        // other countries without a fallback.
        if ($template->countries()->exists()) {
            throw new \DomainException(
                'Only templates with "All countries" can be set as default. '
                . 'Remove country restrictions or set a different template as default.'
            );
        }

        Template::query()->update(['is_default' => false]);
        $template->forceFill(['is_default' => true, 'is_active' => true])->save();

        return $template->fresh('previewFile');
    }

    public function update(Template $template, array $data): Template
    {
        $template->fill($data)->save();

        return $template->fresh('previewFile');
    }

    /**
     * Replace the template's allowed-country set. Pass an empty array to mean
     * "all countries" (pivot stays empty so future additions are inclusive).
     */
    public function syncCountries(Template $template, array $countryIds): Template
    {
        $template->countries()->sync(array_values(array_unique(array_map('intval', $countryIds))));

        // Fan out to all tenants so tenant themes reflect the new allowed set.
        // Reuse the shared observer path by triggering a "saved" event.
        $template->touch();

        return $template->fresh(['previewFile', 'countries']);
    }
}
