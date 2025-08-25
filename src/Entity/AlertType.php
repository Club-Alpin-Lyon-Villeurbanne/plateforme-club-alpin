<?php

namespace App\Entity;

enum AlertType
{
    case Article;
    case Sortie;
    case ArticlePush;
    case SortiePush;
}
