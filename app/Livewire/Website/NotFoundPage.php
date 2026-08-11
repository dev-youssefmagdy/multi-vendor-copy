<?php

namespace App\Livewire\Website;

use Livewire\Component;

class NotFoundPage extends Component
{
    public function render()
    {
        return view('livewire.website.not-found')
            ->layout('layouts.website', ['title' => __('Page Not Found')])
            ->withHeaders(['X-Status-Code' => 404]);
    }
}
