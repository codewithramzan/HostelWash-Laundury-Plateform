<div class="steps-grid">
    <?php foreach ([['Book', 'Schedule pickup in minutes.', '▤'], ['We Collect', 'Your representative collects from your hostel.', '♧'], ['We Wash', 'Professional cleaning and care.', '◉'], ['We Deliver', 'Back to your hostel, fresh and folded.', '▱']] as $index => [$name, $description, $symbol]): ?>
        <article class="step-card">
            <div class="step-heading">
                <span><?= $index + 1 ?></span>
                <h3><?= e($name) ?></h3>
            </div>
            <p><?= e($description) ?></p>
            <div class="step-art">
                <?= icon($symbol) ?></div>
        </article>
    <?php endforeach; ?>
</div>
