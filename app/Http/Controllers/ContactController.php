<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Page;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('site.contact', ['page' => Page::system('contact')]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (filled($request->input('website'))) {
            return back()->with('contact_sent', true); // honeypot
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        ContactMessage::create($data + [
            'phone' => PhoneNumber::normalize($data['phone'] ?? null) ?? ($data['phone'] ?? null),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('contact_sent', true);
    }
}
