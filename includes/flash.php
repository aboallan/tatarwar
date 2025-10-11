<?php if (!empty($flashMessages)): ?>
    <div class="flash-container">
        <?php foreach ($flashMessages as $flash): ?>
            <div class="flash-message flash-<?php echo escape($flash['type']); ?>"><?php echo escape($flash['message']); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
