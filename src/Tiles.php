<?php

class Tiles
{
    const ABSOLUTE_EMPTY = -1;
    const EMPTY = 0;
    const WALL = 1;
    const MONSTER = 2;
    const DEAD_MONSTER = 3;
    const STAIR_UP = 4;
    const STAIR_DOWN = 5;
    const INVISIBLE_WALL = 6;
    const INVISIBLE = 7;
    const WATER = 8;
    const RAISE = 9;
    const INVISIBLE_STAIR_UP = 10;
    const INVISIBLE_STAIR_DOWN = 11;
    const ADD_WALL_START = 20;
    const ADD_WALL_NUMBER = 2;

    public static function isWalkable(int $tile): bool
    {
        return $tile < 1 || $tile === self::DEAD_MONSTER || $tile === self::WATER || $tile === self::INVISIBLE;
    }

    public static function isStair(int $tile): bool
    {
        return in_array($tile, [self::STAIR_UP, self::STAIR_DOWN, self::INVISIBLE_STAIR_UP, self::INVISIBLE_STAIR_DOWN], true);
    }

    public static function isUpStair(int $tile): bool
    {
        return $tile === self::STAIR_UP || $tile === self::INVISIBLE_STAIR_UP;
    }
}
