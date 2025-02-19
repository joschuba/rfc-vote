<?php

namespace App\Models;

enum VoteType: string
{
    case YES = 'yes';
    case NO = 'no';

    public function getColor(): string
    {
        return match($this) {
            self::YES => 'green',
            self::NO => 'red',
        };
    }

    public function getBackgroundColor(): string
    {
        return match($this) {
            self::YES => 'bg-green-200',
            self::NO => 'bg-red-200',
        };
    }

    public function getBorderColor(): string
    {
        return match($this) {
            self::YES => 'border-green-400',
            self::NO => 'border-red-400',
        };
    }

    public function getJustify(): string
    {
        return match($this) {
            self::YES => 'justify-start',
            self::NO => 'justify-end',
        };
    }

    public function getDirection(): string
    {
        return match($this) {
            self::YES => 'flex-row',
            self::NO => 'flex-row-reverse',
        };
    }
}
