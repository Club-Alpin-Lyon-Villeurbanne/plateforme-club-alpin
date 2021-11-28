<?php

namespace App\Mailer;

use App\Entity\CafUser;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class Mailer
{
    private MailerInterface $mailer;
    private MailerRenderer $renderer;
    private string $replyTo;
    private string $mailEmitter;

    public function __construct(
        MailerInterface $mailer,
        MailerRenderer $renderer,
        string $replyTo,
        string $mailEmitter = 'Club Alpin Français Lyon-Villeurbanne <noreply@clubalpinlyon.fr>'
    ) {
        $this->mailer = $mailer;
        $this->renderer = $renderer;
        $this->replyTo = $replyTo;
        $this->mailEmitter = $mailEmitter;
    }

    /**
     * @param array|string|CafUser $to
     * @param string|CafUser|null  $sender
     * @param string|bool|null     $replyTo
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
            if ($name instanceof CafUser) {
                // ie. `[User, User]`
                $toFlat[] = new Address($name->getEmailUser(), $name->getNicknameUser() ?? '');
            } elseif ($email === $name || is_numeric($email)) {
                // ie. `[email => email]` or `[email, email]`
                $toFlat[] = new Address($name);
            } else {
                // ie. `[email => name]`
                $toFlat[] = new Address($email, $name);
            }
        }

        if ($sender instanceof CafUser) {
            $sender = new Address($sender->getEmailUser(), $sender->getNicknameUser() ?? '');
        }

        $email = (new Email())
            ->sender($this->mailEmitter)
            ->from($sender ?? $this->mailEmitter)
            ->to(...$toFlat)
            ->subject($subject)
            ->html($htmlBody)
            ->text($txtBody)
        ;
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
}
