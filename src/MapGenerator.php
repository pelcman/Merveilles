<?php

class MapGenerator
{
    private string $levelsDir;

    public function __construct(string $levelsDir)
    {
        $this->levelsDir = rtrim($levelsDir, '/\\');
    }

    public function load(int $floor, int $section = 1, ?int $downstairX = null, ?int $downstairY = null): array
    {
        $square = 64;
        $file = $this->levelsDir . "/{$floor}-{$section}.dat";

        if (file_exists($file)) {
            $map = unserialize(file_get_contents($file));
            if ($map) {
                return ['map' => $map, 'square' => $square];
            }
        }

        $map = $this->generate($floor, $section, $square, $downstairX, $downstairY);
        file_put_contents($file, serialize($map));

        return ['map' => $map, 'square' => $square];
    }

    private function generate(int $floor, int $section, int $square, ?int $downstairX, ?int $downstairY): array
    {
        srand(($floor * 4) + $section);
        $map = [];

        for ($y = 0; $y < $square; $y++) {
            $row = [];
            for ($x = 0; $x < $square; $x++) {
                if (($floor === 1 || $floor % 10 === 0) && $x >= 30 && $x <= 34 && $y >= 30 && $y <= 34) {
                    $row[] = Tiles::ABSOLUTE_EMPTY;
                } else {
                    $row[] = $this->randomTile();
                }
            }
            $map[] = $row;
        }

        if ($floor > 1 && $downstairX !== null && $downstairY !== null) {
            $map[$downstairY][$downstairX] = Tiles::STAIR_DOWN;
        }

        do {
            $sx = rand(0, $square - 1);
            $sy = rand(0, $square - 1);
        } while ($map[$sy][$sx] !== Tiles::EMPTY);

        $map[$sy][$sx] = Tiles::STAIR_UP;

        return $map;
    }

    private function randomTile(): int
    {
        if (rand(0, 99) > 94) {
            $r = floor(rand(0, Tiles::ADD_WALL_NUMBER));
            return $r < 1 ? Tiles::WALL : Tiles::ADD_WALL_START + ($r - 1);
        }

        if (rand(0, 100) > 98) {
            return Tiles::MONSTER;
        }

        return Tiles::EMPTY;
    }
}
