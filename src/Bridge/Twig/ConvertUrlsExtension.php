<?php

namespace App\Bridge\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Runtime\EscaperRuntime;
use Twig\TwigFilter;

class ConvertUrlsExtension extends AbstractExtension
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('converturls', [$this, 'autoConvertUrls'], [
                'pre_escape' => 'html',
                'is_safe' => ['html'],
            ]),
            new TwigFilter('mail_this', [$this, 'mailThis'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function mailThis(Environment $environment, string $address, ?string $placeholder = null)
    {
        [$user, $domain] = explode('@', $address, 2);
        [$domain, $tld] = explode('.', $domain, 2);

        return sprintf(
            '<a class="mailthisanchor"></a><script type="text/javascript" class="mailthis">mailThis(\'%s\', \'%s\', \'%s\', undefined, %s)</script>',
            $this->twig->getRuntime(EscaperRuntime::class)->escape($user, 'js'),
            $this->twig->getRuntime(EscaperRuntime::class)->escape($domain, 'js'),
            $this->twig->getRuntime(EscaperRuntime::class)->escape($tld, 'js'),
            $placeholder ? sprintf('\'%s\'', $this->twig->getRuntime(EscaperRuntime::class)->escape($placeholder, 'js')) : 'undefined',
        );
    }

    /**
     * method that finds different occurrences of urls or email addresses in a string.
     *
     * @param string $string input string
     *
     * @return string with replaced links
     */
    public function autoConvertUrls($string)
    {
        $pattern = '/(href="|src=")?([-a-zA-Zа-яёА-ЯЁ0-9@:%_\+.~#?&\*\/\/=]{2,256}\.[a-zа-яё]{2,4}\b(\/?[-\p{L}0-9@:%_\+.~#?&\*\/\/=\(\),;]*)?)/u';

        return preg_replace_callback($pattern, [$this, 'callbackReplace'], $string);
    }

    public function callbackReplace($matches)
    {
        if ('' !== $matches[1]) {
            return $matches[0]; // don't modify existing <a href="">links</a> and <img src="">
        }

        $url = $matches[2];
        $urlWithPrefix = $matches[2];

        if (str_contains($url, '@')) {
            $urlWithPrefix = 'mailto:' . $url;
        } elseif (str_starts_with($url, 'https://')) {
            $urlWithPrefix = $url;
        } elseif (!str_starts_with($url, 'http://')) {
            $urlWithPrefix = 'http://' . $url;
        }

        // ignore tailing special characters
        // TODO: likely this could be skipped entirely with some more tweakes to the regular expression
        if (preg_match("/^(.*)(\.|\,|\?)$/", $urlWithPrefix, $matches)) {
            $urlWithPrefix = $matches[1];
            $url = substr($url, 0, -1);
            $punctuation = $matches[2];
        } else {
            $punctuation = '';
        }

        return '<a href="' . $urlWithPrefix . '" target="_blank">' . $url . '</a>' . $punctuation;
    }
}
