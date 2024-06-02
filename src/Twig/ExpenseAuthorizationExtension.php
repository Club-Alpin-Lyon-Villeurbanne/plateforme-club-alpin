<?php

namespace App\Twig;

use App\Security\ExpenseAuthorization;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ExpenseAuthorizationExtension extends AbstractExtension
{
    private $expenseAuthorization;

    public function __construct(ExpenseAuthorization $expenseAuthorization)
    {
        $this->expenseAuthorization = $expenseAuthorization;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_authorized_to_view_expenses', [$this, 'isUserAuthorized']),
        ];
    }

    public function isUserAuthorized(): bool
    {
        return $this->expenseAuthorization->isAuthorized();
    }
}
