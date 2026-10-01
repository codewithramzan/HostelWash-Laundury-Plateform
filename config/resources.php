<?php

// Whitelisted administration resources: label, input type, maximum/options/lookup.
return [
    'hostels' => ['title' => 'Hostels', 'fields' => [
        'name' => ['Hostel name', 'text', 100], 'description' => ['Description', 'optional', 255],
        'address' => ['Address', 'optional', 255], 'status' => ['Status', 'select', ['active', 'inactive']],
    ]],
    'rooms' => ['title' => 'Rooms', 'fields' => [
        'hostel_id' => ['Hostel', 'lookup', 'hostels'], 'room_number' => ['Room number', 'text', 20],
        'capacity' => ['Capacity', 'integer', 100], 'status' => ['Status', 'select', ['active', 'inactive']],
    ]],
    'services' => ['title' => 'Services & Pricing', 'fields' => [
        'name' => ['Service name', 'text', 100], 'description' => ['Description', 'optional', 255],
        'pricing_type' => ['Pricing type', 'select', ['per_kg', 'per_item']], 'price' => ['Price (Rs.)', 'money', 999999],
        'estimated_time_hours' => ['Estimated hours', 'integer', 720], 'status' => ['Status', 'select', ['active', 'inactive']],
    ]],
    'representatives' => ['title' => 'Representatives', 'fields' => [
        'user_id' => ['Representative account', 'lookup', 'representative_users'],
        'hostel_id' => ['Hostel', 'lookup', 'hostels'], 'is_active' => ['Active', 'select', ['1', '0']],
    ]],
    'expenses' => ['title' => 'Expenses', 'fields' => [
        'category' => ['Category', 'text', 50], 'title' => ['Title', 'text', 100],
        'amount' => ['Amount (Rs.)', 'money', 99999999], 'expense_date' => ['Expense date', 'date', null],
        'notes' => ['Notes', 'optional', 255],
    ]],
    'coupons' => ['title' => 'Coupons', 'fields' => [
        'code' => ['Code', 'text', 50], 'discount_type' => ['Discount type', 'select', ['percentage', 'fixed']],
        'discount_value' => ['Discount value', 'money', 999999], 'min_order_amount' => ['Minimum order (Rs.)', 'money', 999999],
        'start_date' => ['Start date', 'optional_date', null], 'end_date' => ['End date', 'optional_date', null],
        'usage_limit' => ['Usage limit (blank = unlimited)', 'optional_integer', 1000000],
        'status' => ['Status', 'select', ['active', 'inactive']],
    ]],
];
