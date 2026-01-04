<?php $this->layout('base', ['title' => $title ?? '500 - Internal Server Error']) ?>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="text-center">
        <img src="<?= asset('images/logo.webp') ?>" width="100px" alt="Logo">
        <hr class="mx-auto mt-4 mb-2" style="width: 1rem; border: none; height: 1px; background-color: black;" />
        <h3 class="display-4 font-weight-bold text-danger">
            <?= $title ?? '500 - Internal Server Error' ?>
        </h3>
        <p class="lead mb-4">
            <?= $message ?? "An internal error occurred" ?>
        </p>
        <a href="<?= request()->getRefer() ?>" class="btn btn-danger">
            Go Back
        </a>
    </div>
</div>