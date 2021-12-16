<?php

namespace App\Bridge\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ConvertUrlsExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('converturls', [$this, 'autoConvertUrls'],
                [
                    'pre_escape' => 'html',
                    'is_safe' => ['html'],
                ]),
        ];
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

        if (false !== strpos($url, '@')) {
            $urlWithPrefix = 'mailto:'.$url;
        } elseif (0 === strpos($url, 'https://')) {
            $urlWithPrefix = $url;
        } elseif (0 !== strpos($url, 'http://')) {
            $urlWithPrefix = 'http://'.$url;
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

        return '<a href="'.$urlWithPrefix.'" target="_blank">'.$url.'</a>'.$punctuation;
    }
}
