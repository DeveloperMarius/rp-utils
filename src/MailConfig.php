<?php

namespace utils;

use PHPMailer\PHPMailer\PHPMailer;

class MailConfig{

    /**
     * @var MailConfig|null $default - Default MailConfig
     */
    public static ?MailConfig $default = null;

    public function __construct(private readonly string $smtp_user, private readonly string $smtp_password, private readonly string $smtp_host = 'mail.repaste.de', private readonly int $smtp_port = 587, private readonly string $smtp_secure = PHPMailer::ENCRYPTION_SMTPS){}

    /**
     * @return string
     */
    public function getSmtpUser(): string{
        return $this->smtp_user;
    }

    /**
     * @return string
     */
    public function getSmtpPassword(): string{
        return $this->smtp_password;
    }

    /**
     * @return string
     */
    public function getSmtpHost(): string{
        return $this->smtp_host;
    }

    /**
     * @return int
     */
    public function getSmtpPort(): int{
        return $this->smtp_port;
    }

    /**
     * @return string
     */
    public function getSmtpSecure(): string{
        return $this->smtp_secure;
    }
}