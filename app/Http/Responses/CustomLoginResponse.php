<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class CustomLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = auth()->user();

        if($user->role === UserRole::CASHIER) {
            return redirect()->route('orders.index');
        }

        // Default Filament redirect
        return redirect()->intended(filament()->getUrl());
    }
}
