<?php

namespace app\Services;

final class Settings
{
    public static function get(): array
    {
        return FileStore::read('settings') + [
            'headline' => 'Fresh Clothes, Brighter Days',
            'intro' => 'Affordable, reliable and convenient laundry service for university hostels.',
            'contact_email' => '',
            'contact_phone' => '',
            'about' => 'We make hostel life a little easier. Book a pickup, hand your laundry to your hostel representative, and follow every step until it is returned fresh and folded.',
            'terms' => 'Final per-kilogram charges are based on the weight confirmed at collection. Item-based services are charged by confirmed item count. You may cancel before collection. Please check pockets, identify delicate garments and report any issue through Support. Delivery estimates depend on the selected service. Cash payments are recorded by authorized staff.',
            'privacy' => 'We use your name, contact details, hostel, room and order information to operate the laundry service. Assigned representatives receive the details needed for collection and delivery. Contact the service administrator for access or correction requests. Order and payment records may be retained for accounting and dispute handling.',
            'pickup_slots' => '9–11 AM,2–4 PM,5–7 PM',
        ];
    }

    public static function slots(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', self::get()['pickup_slots']))));
    }
}
