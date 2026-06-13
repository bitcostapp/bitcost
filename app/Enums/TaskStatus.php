<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Open = 'open';
    case Completed = 'completed';

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
