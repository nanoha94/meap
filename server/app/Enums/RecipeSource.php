<?php

namespace App\Enums;

enum RecipeSource: string
{
    case MANUAL = 'manual';
    case AI_IMPORTED = 'ai_imported';
}
