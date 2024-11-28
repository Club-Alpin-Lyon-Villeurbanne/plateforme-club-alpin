<?php

namespace App\Mailer;

use App\Entity\User;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\MessageIDValidation;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class Mailer
{
    private MailerInterface $mailer;
    private MailerRenderer $renderer;
    private EmailValidator $emailValidator;
    private string $replyTo;
    private string $mailEmitter;

    public function __construct(
        MailerInterface $mailer,
        MailerRenderer $renderer,
        EmailValidator $emailValidator,
        string $replyTo,
        string $nameEmitter
    ) {
        $this->mailer = $mailer;
        $this->renderer = $renderer;
        $this->emailValidator = $emailValidator;
        $this->replyTo = $replyTo;
        $this->mailEmitter = sprintf('%s <%s>', $nameEmitter, $replyTo);
    }

    /**
     * @param array|string|User $to
     * @param string|User|null  $sender
     * @param string|bool|null  $replyTo
     *
     * @throws TransportExceptionInterface
     */
    public function send($to, string $template, array $context = [], array $headers = [], $sender = null, $replyTo = null): void
    {
        $subject = $this->renderer->renderSubject($template, $context);
        $htmlBody = $this->renderer->renderBody($template, 'html', $context);
        $txtBody = $this->renderer->renderBody($template, 'txt', $context);

        if (!\is_array($to)) {
            $to = [$to];
        }

        $toFlat = [];
        foreach ($to as $email => $name) {
            if ($name instanceof User) {
                // ie. `[User, User]`
                if ($this->isValid($name->getEmail())) {
                    $toFlat[] = new Address($name->getEmail(), $name->getNickname() ?? '');
                }
            } elseif ($email === $name || is_numeric($email)) {
                // ie. `[email => email]` or `[email, email]`
                if ($this->isValid($name)) {
                    $toFlat[] = new Address($name);
                }
            } else {
                // ie. `[email => name]`
                if ($this->isValid($email)) {
                    $toFlat[] = new Address($email, $name);
                }
            }
        }

        if (empty($toFlat)) {
            return;
        }

        if ($sender instanceof User) {
            if ($this->isValid($sender->getEmail())) {
                $sender = new Address($sender->getEmail(), $sender->getNickname() ?? '');
            } else {
                $sender = null;
            }
        }

        if ($replyTo instanceof User) {
            if ($this->isValid($replyTo->getEmail())) {
                $replyTo = new Address($replyTo->getEmail(), $replyTo->getNickname() ?? '');
            } else {
                $replyTo = null;
            }
        } elseif ($this->isValid($replyTo)) {
            $replyTo = new Address($replyTo, $replyTo);
        }

        $email = (new Email())
            ->from($this->mailEmitter)
            ->subject($subject)
            ->html($htmlBody)
            ->text($txtBody)
        ;

        if (\count($toFlat) > 1) {
            $email->bcc(...$toFlat);
        } else {
            $email->to(...$toFlat);
        }

        if (false !== $replyTo) {
            $email->replyTo($replyTo ?? $sender ?? $this->replyTo);
        }

        if ($headers) {
            $headersObject = $email->getHeaders();
            foreach ($headers as $name => $value) {
                $headersObject->addHeader($name, $value);
            }
        }

        $this->mailer->send($email);
    }

    private function isValid(?string $email): bool
    {
        if (!$email || '' === trim($email)) {
            return false;
        }

        return $this->emailValidator->isValid($email, new MessageIDValidation());
    }
}
