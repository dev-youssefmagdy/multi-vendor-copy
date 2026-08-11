<?php

namespace App\Livewire\Website;

use App\Models\Template;
use Livewire\Component;
use Livewire\WithPagination;

class TemplatesPage extends Component
{
    use WithPagination;

    public function render()
    {
        $templates = Template::query()
            ->with('previewFile')
            ->where('is_active', true)
            ->paginate(12);

        return view('livewire.website.templates', [
            'templates' => $templates,
        ])->layout('layouts.website', ['title' => __('Templates — Ecommet')]);
    }
}

