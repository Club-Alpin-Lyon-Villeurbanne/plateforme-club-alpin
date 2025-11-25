<?php

namespace App\Helper;

use Symfony\Component\String\Slugger\SluggerInterface;

class SlugHelper
{
    public function __construct(protected SluggerInterface $slugger)
    {
    }

    public function generateSlug(string $title, int $limit = 30): string
    {
        return substr(strtolower($this->slugger->slug($title)), 0, $limit);
    }
}
