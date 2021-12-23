<?php

namespace App\Legacy;

use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;

class ContentHtml
{
    private Environment $environment;
    private LoggerInterface $logger;
    private string $inlineContentPath;

    public function __construct(Environment $environment, LoggerInterface $logger, string $inlineContentPath)
    {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->inlineContentPath = $inlineContentPath;
    }

    public function getEasyInclude($elt, $style = 'vide')
    {
        $template = sprintf('content_html/%s.html.twig', $elt);
        try {
            $content = $this->environment->render($template);
        } catch (LoaderError $e) {
            $this->logger->error(sprintf('Unable to find html content "%s".', $template));
            $content = '';
        }

        return '<div id="'.$elt.'" class="'.$style.'">'.$content.'</div>';
    }

    public function getInlineContent(string $key): string
    {
        $inlineContent = require $this->inlineContentPath;

        if (!isset($inlineContent[$key])) {
            $this->logger->error(sprintf('Unable to find inline content "%s".', $key));

            return '';
        }

        return $inlineContent[$key];
    }
}
