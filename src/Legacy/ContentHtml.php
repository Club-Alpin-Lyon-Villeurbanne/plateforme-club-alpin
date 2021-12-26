<?php

namespace App\Legacy;

use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;

class ContentHtml
{
    private Environment $environment;
    private LoggerInterface $logger;

    public function __construct(Environment $environment, LoggerInterface $logger)
    {
        $this->environment = $environment;
        $this->logger = $logger;
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
}
