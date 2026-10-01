<a class="back-link" href="<?= e(url('/orders')) ?>">← Back to orders</a>
<div class="page-heading">
    <div>
        <h1>Schedule Laundry Pickup</h1>
        <p>Fill in the details to place a new order.</p>
    </div>
</div>
<form method="post" action="<?= e(url('/orders')) ?>" id="booking-form" class="booking-layout">
    <?= csrf() ?>
    <section class="panel stack-form">
        <div class="form-grid">
            <label>
                Hostel
                <select name="hostel_id" id="hostel-select" required>
                    <option value="">Select hostel</option>
                    <?php foreach ($hostels as $hostel): ?>
                        <option value="<?= e($hostel['id']) ?>"><?= e($hostel['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Room number
                <select name="room_id" id="room-select" required>
                    <option value="">Select room</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= e($room['id']) ?>" data-hostel="<?= e($room['hostel_id']) ?>"><?= e($room['room_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <fieldset>
            <legend>Service type</legend>
            <div class="service-choices">
                <?php foreach ($services as $index => $service): ?>
                    <label class="service-choice">
                        <input type="radio" name="service_id" value="<?= e($service['id']) ?>" data-price="<?= e($service['price']) ?>" data-unit="<?= e($service['pricing_type']) ?>" <?= $index === 0 ? 'checked' : '' ?> required>
                        <strong><?= e($service['name']) ?></strong>
                        <small><?= e(money($service['price'])) ?>/<?= $service['pricing_type'] === 'per_kg' ? 'kg' : 'item' ?></small>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <div class="form-grid">
            <label>
                <span id="quantity-label">Estimated weight (kg)</span>
                <input type="number" id="quantity" name="quantity" min="0.1" max="999" step="0.1" value="3" required>
            </label>
            <label>
                Coupon <span class="muted">(optional)</span>
                <input name="coupon" maxlength="50" placeholder="Enter coupon code">
            </label>
        </div>
        <div class="form-grid">
            <label>
                Pickup date
                <input type="date" name="pickup_date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+60 days')) ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
            </label>
            <label>
                Pickup time slot
                <select name="pickup_slot" required>
                    <?php foreach ($slots as $slot): ?>
                        <option><?= e($slot) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>
            Special instructions <span class="muted">(optional)</span>
            <textarea name="special_instructions" rows="4" maxlength="2000" placeholder="E.g. delicate clothes, wash separately…"></textarea>
        </label>
    </section>
    <aside class="panel price-summary">
        <p>Estimated price</p>
        <strong id="estimated-price" class="price-display">—</strong>
        <p id="price-equation" class="muted">Choose a service</p>
        <hr>
        <div class="soft-callout">
            <span>♧</span>
            <p>Final total is confirmed at collection. Coupon eligibility is checked when booking.</p>
        </div>
        <button class="btn btn-primary w-100" <?= !$services || !$hostels || !$rooms ? 'disabled' : '' ?>>Schedule Pickup</button>
        <?php if (!$services || !$hostels || !$rooms): ?>
            <p class="muted">The administrator needs to add an active service, hostel and room before booking opens.</p>
        <?php endif; ?>
    </aside>
</form>
