<?php

namespace App\Livewire\Website;

use Livewire\Component;

class PrivacyPage extends Component
{
    public function render()
    {
        return view('livewire.website.privacy')
            ->layout('layouts.website', ['title' => 'Privacy Policy — Ecommet']);
    }
}
