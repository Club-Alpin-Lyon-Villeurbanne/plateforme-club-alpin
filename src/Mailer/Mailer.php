<?php

namespace App\Mailer;

use App\Entity\User;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\MessageIDValidation;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class Mailer
{
    private string $mailEmitter;

    public function __construct(
        protected MailerInterface $mailer,
        protected MailerRenderer $renderer,
        protected EmailValidator $emailValidator,
        protected LoggerInterface $logger,
        protected string $replyTo,
        string $nameEmitter
    ) {
        $this->mailEmitter = sprintf('%s <%s>', $nameEmitter, $replyTo);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function send(
        array|string|User $to,
        string $template,
        array $context = [],
        array $headers = [],
        string|User|null $sender = null,
        array|string|User|bool|null $replyTo = null): void
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

        if ($replyTo instanceof User) {
            if ($this->isValid($replyTo->getEmail())) {
                $replyTo = new Address($replyTo->getEmail(), $replyTo->getNickname() ?? '');
            } else {
                $replyTo = null;
            }
        } elseif (\is_array($replyTo)) {
            $items = [];
            foreach ($replyTo as $item) {
                $items[] = $this->isValid($item) ? new Address($item, $item) : null;
            }
            $replyTo = $items;
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

        if ($replyTo instanceof Address) {
            $email->replyTo($replyTo);
        } elseif (\is_array($replyTo)) {
            $email->replyTo(...$replyTo);
        }

        if ($headers) {
            $headersObject = $email->getHeaders();
            foreach ($headers as $name => $value) {
                $headersObject->addHeader($name, $value);
            }
        }

        try {
            $this->mailer->send($email);
        } catch (\Exception $exception) {
            $this->logger->error('Mailer : Erreur envoi du mail "' . $subject . '"');
            $this->logger->error($exception->getMessage());
        }
    }

    private function isValid(?string $email): bool
    {
        if (!$email || '' === trim($email)) {
            return false;
        }

        return $this->emailValidator->isValid($email, new MessageIDValidation());
    }
}
