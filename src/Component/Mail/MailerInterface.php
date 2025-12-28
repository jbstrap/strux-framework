<?php

declare(strict_types=1);

namespace Strux\Component\Mail;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

interface MailerInterface
{
    /**
     * Returns an instance of PHPMailer
     * @param array $data
     * @return PHPMailer
     * @throws Exception
     * @throws \Exception
     */
    public static function send(array $data = []): PHPMailer;
}
