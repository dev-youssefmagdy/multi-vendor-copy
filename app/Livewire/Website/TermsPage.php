<?php

namespace App\Livewire\Website;

use Livewire\Component;

class TermsPage extends Component
{
    public function render()
    {
        return view('livewire.website.terms')
            ->layout('layouts.website', ['title' => 'Terms & Conditions — Ecommet']);
    }
}
