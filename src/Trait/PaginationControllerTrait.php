<?php

namespace App\Trait;

use Symfony\Component\HttpFoundation\Request;

trait PaginationControllerTrait
{
    protected function getPaginationParams(Request $request, int $total, int $perPage = 30): array
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pages = (int) ceil($total / $perPage);
        $page = min($page, max(1, $pages));
        $first = $perPage * ($page - 1);

        return [
            'total' => $total,
            'per_page' => $perPage,
            'pages' => $pages,
            'page' => $page,
            'first' => $first,
        ];
    }
}
