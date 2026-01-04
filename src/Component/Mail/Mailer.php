<?php

declare(strict_types=1);

namespace Strux\Component\Mail;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Log\LoggerInterface;
use Strux\Component\Config\Config;
use Strux\Component\View\ViewInterface;

// To render email templates

class Mailer
{
    private Config $config;
    private ViewInterface $view;
    private LoggerInterface $logger;

    // --- Recipient Properties ---
    private array $to = [];
    private array $cc = [];
    private array $bcc = [];

    public function __construct(Config $config, ViewInterface $view, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->view = $view;
        $this->logger = $logger;
    }

    /**
     * Set the recipient of the message.
     */
    public function to(string $address, ?string $name = null): self
    {
        $this->to[] = ['address' => $address, 'name' => $name ?? ''];
        return $this;
    }

    /**
     * Send the email.
     *
     * @param string $view The view file for the email's HTML body.
     * @param array $data The data to pass to the view.
     * @param callable|null $callback A closure to customize the PHPMailer instance (e.g., for attachments).
     * @return bool
     */
    public function send(string $view, array $data = [], ?callable $callback = null): bool
    {
        try {
            // Render the HTML content from a view file.
            $htmlBody = $this->view->render($view, $data);

            $mailer = $this->createMailerInstance();

            // Set recipients
            foreach ($this->to as $recipient) {
                $mailer->addAddress($recipient['address'], $recipient['name']);
            }
            // You can add methods for CC and BCC similarly.

            // Set content
            $mailer->isHTML(true);
            $mailer->Subject = $data['subject'] ?? $this->config->get('app.name', 'Application');
            $mailer->Body = $htmlBody;
            $mailer->AltBody = strip_tags($htmlBody);

            // Allow for custom modifications, like adding attachments
            if ($callback) {
                $callback($mailer);
            }

            return $mailer->send();

        } catch (PHPMailerException $e) {
            $this->logger->error("Mailer Error: {$mailer->ErrorInfo}", ['exception' => $e]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error("An error occurred while sending email: {$e->getMessage()}", ['exception' => $e]);
            return false;
        }
    }

    /**
     * Creates and configures a PHPMailer instance based on the application's etc.
     * @throws PHPMailerException
     */
    private function createMailerInstance(): PHPMailer
    {
        $mailer = new PHPMailer(true); // Enable exceptions

        $defaultMailer = $this->config->get('mail.default', 'smtp');
        $config = $this->config->get("mail.mailers.{$defaultMailer}");

        // Configure based on the selected transport
        if ($config['transport'] === 'smtp') {
            $mailer->isSMTP();
            $mailer->Host = $config['host'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $config['username'];
            $mailer->Password = $config['password'];
            $mailer->SMTPSecure = $config['encryption'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mailer->Port = $config['port'];
        }
        // Add other transports like 'sendmail' if needed

        // Global From address
        $fromAddress = $this->config->get('mail.from.address', 'hello@example.com');
        $fromName = $this->config->get('mail.from.name', 'Example');
        $mailer->setFrom($fromAddress, $fromName);

        // Debugging
        $mailer->SMTPDebug = $this->config->get('mail.debug', 0);

        return $mailer;
    }
}