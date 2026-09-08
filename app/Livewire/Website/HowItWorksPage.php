<?php

namespace App\Livewire\Website;

use Livewire\Component;

class HowItWorksPage extends Component
{
    public function render()
    {
        return view('livewire.website.how-it-works')
            ->layout('layouts.website', ['title' => 'How It Works — Ecommet']);
    }
}
