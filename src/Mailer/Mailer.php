<?php

namespace App\Mailer;

use App\Entity\User;
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
        string $nameEmitter
    ) {
        $this->mailer = $mailer;
        $this->renderer = $renderer;
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
                $toFlat[] = new Address($name->getEmail(), $name->getNickname() ?? '');
            } elseif ($email === $name || is_numeric($email)) {
                // ie. `[email => email]` or `[email, email]`
                $toFlat[] = new Address($name);
            } else {
                // ie. `[email => name]`
                $toFlat[] = new Address($email, $name);
            }
        }

        if ($sender instanceof User) {
            $sender = new Address($sender->getEmail(), $sender->getNickname() ?? '');
        }

        $email = (new Email())
            ->sender($sender ?? $this->mailEmitter)
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
}
