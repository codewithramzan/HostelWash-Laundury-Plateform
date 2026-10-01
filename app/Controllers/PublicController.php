<?php

namespace app\Controllers;

use app\Models\DB;

final class PublicController
{
    public function page(string $page = 'home'): void
    {
        $titles = ['home' => 'Fresh Clothes, Brighter Days', 'services' => 'Our Laundry Services',
            'pricing' => 'Simple & Affordable Pricing', 'how-it-works' => 'How It Works',
            'hostels' => 'Our Hostels', 'about' => 'About HostelWash', 'faq' => 'Frequently Asked Questions',
            'contact' => 'Get in Touch', 'terms' => 'Terms of Service', 'privacy' => 'Privacy Policy'];
        view('public/page', ['title' => $titles[$page], 'page' => $page,
            'services' => DB::all('SELECT * FROM services WHERE status = "active" ORDER BY id'),
            'hostels' => DB::all('SELECT h.*, (SELECT COUNT(*) FROM rooms r WHERE r.hostel_id = h.id AND r.status = "active") AS rooms FROM hostels h WHERE h.status = "active" ORDER BY name')]);
    }
}
