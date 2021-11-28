<?php

namespace App\Mailer;

use Twig\Environment;

class MailerRenderer
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function renderBody($template, $format, array $context = [])
    {
        if (!\in_array($format, ($formats = ['html', 'txt']), true)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid format (only "%s" are allowed).', $format, implode('", "', $formats)));
        }

        return $this->twig->render($this->getTemplate($template), array_replace($context, [
            'format' => $format,
            'template' => $template,
        ]));
    }

    public function renderSubject($template, $context = [])
    {
        return htmlspecialchars_decode($this->twig
            ->load($this->getTemplate($template))
            ->renderBlock('subject', $context)
        );
    }

    private function getTemplate($template)
    {
        return sprintf('email/%s.html.twig', $template);
    }
}
