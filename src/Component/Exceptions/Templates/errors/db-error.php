<?php $this->layout('base', ['title' => $title ?? 'Database Error']) ?>

<section class="d-flex justify-content-center align-items-center min-vh-100">
    <div class="text-center">
        <img src="<?= asset('images/logo.webp') ?>" width="100px" alt="Logo">
        <hr class="mx-auto mt-4 mb-2" style="width: 1rem; border: none; height: 1px; background-color: black;" />
        <h3 class="display-4 font-weight-bold text-danger">Database Error</h3>
        <p class="lead mb-4">
            <?= $message ?? 'Oops! Could not connect to DB.' ?>
        </p>
    </div>
</section>