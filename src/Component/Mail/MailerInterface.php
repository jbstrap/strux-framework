<?php

declare(strict_types=1);

namespace Strux\Component\Mail;

interface MailerInterface
{
    /**
     * Set the recipient of the message. You can call this method multiple times to add multiple recipients.
     * @param string $address The email address of the recipient.
     * @param string|null $name The name of the recipient (optional).
     * @return self
     */
    public function to(string $address, ?string $name = null): self;

    /**
     * Send the email.
     * The email's HTML body is generated from a view file, and you can pass data to that view.
     * Additionally, you can provide a callback to customize the PHPMailer instance before sending (e.g., to add attachments or set additional headers).
     *
     * @param string $view The view file for the email's HTML body.
     * @param array $data The data to pass to the view.
     * @param callable|null $callback A closure to customize the PHPMailer instance (e.g., for attachments).
     * @return bool
     */
    public function send(string $view, array $data = [], ?callable $callback = null): bool;
}
