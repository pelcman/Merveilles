<?php

class Game
{
    private PDO $db;
    private MapGenerator $mapGen;
    private int $square = 64;

    public function __construct(PDO $db, MapGenerator $mapGen)
    {
        $this->db = $db;
        $this->mapGen = $mapGen;
    }

    public static function getPlayerBuild(int $kill, int $save): int
    {
        $build = (int) round(($kill - $save + 26) / 4);
        return max(0, min(13, $build));
    }

    public static function calcLevel(int $xp): int
    {
        return max(1, (int) floor(pow($xp, 1 / 3)));
    }

    public function loadState(string $playerName): array
    {
        $stmt = $this->db->prepare('SELECT * FROM players WHERE mv_name = ?');
        $stmt->execute([$playerName]);
        $u = $stmt->fetch();

        if (!$u) {
            throw new RuntimeException('Player not found');
        }

        $playerx = (int) $u['x'];
        $playery = (int) $u['y'];
        $playerf = (int) $u['floor'];
        $playermf = (int) $u['max_floor'];
        $section = 1;
        $playerxp = (int) $u['xp'];
        $playerhp = (int) $u['hp'];
        $playermp = (int) $u['mp'];
        $playerkp = (int) $u['kill'];
        $playersv = (int) $u['save'];
        $playerBuild = self::getPlayerBuild($playerkp, $playersv);
        $playermsg = $u['message'] ?? '';
        $playermsgts = (int) $u['message_timestamp'];
        $playerHead = (int) $u['avatar_head'];
        $playerBody = (int) $u['avatar_body'];
        $warp1 = (int) $u['warp1'];

        $playerlv = self::calcLevel($playerxp);

        $playernext = (int) floor(pow($playerlv + 1, 3));
        $playerprev = (int) floor(pow($playerlv, 3));
        $playerleft = $playernext - $playerxp;
        $playerrigh = $playernext - $playerprev;
        $playerxx = $playerrigh > 0 ? (int) floor(($playerleft / $playerrigh) * 100) : 0;
        $playerxx = 100 - $playerxx;

        $result = $this->mapGen->load($playerf, $section);
        $map = $result['map'];
        $this->square = $result['square'];

        return [
            'x' => $playerx, 'y' => $playery,
            'floor' => $playerf, 'maxFloor' => $playermf, 'section' => $section,
            'xp' => $playerxp, 'hp' => $playerhp, 'mp' => $playermp,
            'kill' => $playerkp, 'save' => $playersv,
            'build' => $playerBuild,
            'level' => $playerlv, 'percentXp' => $playerxx,
            'message' => $playermsg, 'messageTimestamp' => $playermsgts,
            'avatarHead' => $playerHead, 'avatarBody' => $playerBody,
            'warp1' => $warp1,
            'map' => $map,
        ];
    }

    public function processAction(string $playerName, array $state, ?string $action, ?string $reqX, ?string $reqY, ?string $posX, ?string $posY): array
    {
        $logs = [];
        $monsters = [];
        $information = null;

        $playerx = $posX !== null ? (int) $posX : $state['x'];
        $playery = $posY !== null ? (int) $posY : $state['y'];
        $playerf = $state['floor'];
        $playermf = $state['maxFloor'];
        $section = $state['section'];
        $playerxp = $state['xp'];
        $playerhp = $state['hp'];
        $playermp = $state['mp'];
        $playerkp = $state['kill'];
        $playersv = $state['save'];
        $playerlv = $state['level'];
        $playermsg = $state['message'];
        $playermsgts = $state['messageTimestamp'];
        $playerHead = $state['avatarHead'];
        $playerBody = $state['avatarBody'];
        $playerBuild = $state['build'];
        $playerxx = $state['percentXp'];

        $map = $state['map'];

        if ($action !== null && $map[$playery][$playerx] === Tiles::RAISE) {
            $playerhp = 30;
            $playermp = 30;
        }

        $specials = $this->loadSpecials($playerf);
        foreach ($specials as $sp) {
            $map[$sp['y']][$sp['x']] = $sp;
        }

        if ($action === 'portal' && isset($map[$reqY][$reqX]) && is_array($map[$reqY][$reqX])) {
            $tile = $map[$reqY][$reqX];
            $newFloor = max(1, (int) $tile['toFloor']);
            if ($newFloor <= $playermf || $playerhp > 0) {
                $playerx = min(max(0, (int) $tile['toX']), $this->square - 1);
                $playery = min(max(0, (int) $tile['toY']), $this->square - 1);
                $playerf = $newFloor;
                if ($playerf > $playermf) $playermf = $playerf;
                $result = $this->mapGen->load($playerf, $section, $playerx, $playery);
                $map = $result['map'];
            }
        } elseif ($action === 'stair' && isset($map[$reqY][$reqX])) {
            $tile = $map[$reqY][$reqX];
            $difF = null;
            if ($tile === Tiles::STAIR_UP || $tile === Tiles::INVISIBLE_STAIR_UP) $difF = 1;
            elseif ($tile === Tiles::STAIR_DOWN || $tile === Tiles::INVISIBLE_STAIR_DOWN) $difF = -1;

            if ($difF !== null) {
                $newFloor = $playerf + $difF;
                if ($newFloor > 0 && ($newFloor <= $playermf || $playerhp > 0)) {
                    $playerx = (int) $reqX;
                    $playery = (int) $reqY;
                    $playerf = $newFloor;
                    if ($playerf > $playermf) $playermf = $playerf;
                    $result = $this->mapGen->load($playerf, $section, $playerx, $playery);
                    $map = $result['map'];
                }
            }
        } elseif ($action === 'chat') {
            $playermsg = $reqX;
            $playermsgts = time();
        }

        if ((time() - $playermsgts) > 5) {
            $playermsg = '';
        }

        $monsters = $this->loadMonsters($playerf, $map);

        $arrayPlayers = $this->loadOtherPlayers($playerf, $playerName);

        if ($action === 'heal' && $playermp > 0) {
            $healResult = $this->processHeal($reqX, $arrayPlayers, $playerlv, $playermp, $playerxp, $playersv);
            if ($healResult) {
                $information = $healResult['information'];
                $playerxp = $healResult['xp'];
                $playermp = $healResult['mp'];
                $playersv = $healResult['save'];
                $arrayPlayers = $healResult['players'];
            }
        }

        if ($action === 'attack') {
            $attackResult = $this->processAttack(
                $playerx, $playery, (int) $reqX, (int) $reqY,
                $playerf, $playerlv, $playerhp, $playerxp, $playerkp,
                $map, $monsters
            );
            if ($attackResult) {
                $information = $attackResult['information'];
                $playerhp = $attackResult['hp'];
                $playerxp = $attackResult['xp'];
                $playerkp = $attackResult['kill'];
                $map = $attackResult['map'];
                $monsters = $attackResult['monsters'];
            }
        }

        $background = "backgrounds/{$playerf}-{$section}.gif";
        if (!file_exists(__DIR__ . '/../public/img/' . $background)) {
            $background = 'world.gif';
        }

        $playerlv = self::calcLevel($playerxp);
        $playerBuild = self::getPlayerBuild($playerkp, $playersv);

        $playernext = (int) floor(pow($playerlv + 1, 3));
        $playerprev = (int) floor(pow($playerlv, 3));
        $playerleft = $playernext - $playerxp;
        $playerrigh = $playernext - $playerprev;
        $playerxx = $playerrigh > 0 ? (int) floor(($playerleft / $playerrigh) * 100) : 0;
        $playerxx = 100 - $playerxx;

        $this->savePlayerState($playerName, $playerx, $playery, $playerxp, $playerhp, $playermp, $playerkp, $playersv, $playerf, $playermf, $playermsg, $playermsgts);

        $data = [
            'status' => [
                'x' => $playerx, 'y' => $playery,
                'floor' => $playerf, 'maxFloor' => $playermf, 'section' => $section,
                'hp' => $playerhp, 'mp' => $playermp,
                'xp' => $playerxp, 'percentXp' => $playerxx,
                'level' => $playerlv, 'build' => $playerBuild,
                'message' => $playermsg,
                'avatarBody' => $playerBody, 'avatarHead' => $playerHead,
            ],
            'background' => $background,
            'map' => $map,
            'players' => $arrayPlayers,
            'monsters' => $monsters,
            'logs' => $logs,
        ];

        if ($information !== null) {
            $data['information'] = $information;
        }

        return $data;
    }

    public function getInitialData(string $playerName): array
    {
        $state = $this->loadState($playerName);
        return $this->processAction($playerName, $state, null, null, null, null, null);
    }

    private function loadSpecials(int $floor): array
    {
        $stmt = $this->db->prepare(
            'SELECT x, y, message, to_floor AS toFloor, to_x AS toX, to_y AS toY, image FROM specials WHERE floor = ?'
        );
        $stmt->execute([$floor]);
        return $stmt->fetchAll();
    }

    private function loadMonsters(int $floor, array &$map): array
    {
        $monsters = [];
        $stmt = $this->db->prepare('SELECT x, y, health FROM monsters WHERE floor = ? ORDER BY `time` DESC');
        $stmt->execute([$floor]);

        while ($row = $stmt->fetch()) {
            if ((int) $row['health'] === 0) {
                $map[$row['y']][$row['x']] = Tiles::DEAD_MONSTER;
            } else {
                if (!isset($monsters[$row['y']])) $monsters[$row['y']] = [];
                $monsters[$row['y']][$row['x']] = (int) $row['health'];
            }
        }

        return $monsters;
    }

    private function loadOtherPlayers(int $floor, string $excludeName): array
    {
        $stmt = $this->db->prepare(
            'SELECT mv_name, x, y, `save`, `kill`, hp, xp, message, avatar_head, avatar_body FROM players WHERE floor = ? AND mv_time < 10'
        );
        $stmt->execute([$floor]);

        $players = [];
        while ($row = $stmt->fetch()) {
            if (strtolower($row['mv_name']) === strtolower($excludeName)) continue;
            $players[] = [
                'name' => $row['mv_name'],
                'x' => (int) $row['x'],
                'y' => (int) $row['y'],
                'hp' => (int) $row['hp'],
                'message' => $row['message'],
                'build' => self::getPlayerBuild((int) $row['kill'], (int) $row['save']),
                'avatarHead' => (int) $row['avatar_head'],
                'avatarBody' => (int) $row['avatar_body'],
            ];
        }

        return $players;
    }

    private function processHeal(string $targetName, array &$players, int $playerlv, int &$playermp, int &$playerxp, int &$playersv): ?array
    {
        foreach ($players as &$p) {
            if ($p['name'] !== $targetName || $p['hp'] >= 16) continue;
            if ($p['hp'] <= 0 && $playerlv <= 29) continue;

            $lvl = self::calcLevel((int) $this->getPlayerXp($targetName));

            $healPerMp = 1 + (int) floor($playerlv / 15);
            $mp = (int) ceil((30 - $p['hp']) / $healPerMp);
            if ($mp > $playermp) $mp = $playermp;

            $heal = $healPerMp * $mp;
            if ($heal > 30) $heal = 30;

            $xp = (int) floor($heal * $lvl / 30);

            $information = [
                'type' => 9,
                'playerLevel' => $lvl,
                'heal' => $heal,
            ];

            $p['hp'] += $heal;
            if ($p['hp'] > 30) $p['hp'] = 30;

            $stmt = $this->db->prepare('UPDATE players SET hp = ? WHERE mv_name = ?');
            $stmt->execute([$p['hp'], $targetName]);

            $playerxp += $xp;
            $playermp -= $mp;
            $playersv++;

            return [
                'information' => $information,
                'xp' => $playerxp,
                'mp' => $playermp,
                'save' => $playersv,
                'players' => $players,
            ];
        }

        return null;
    }

    private function getPlayerXp(string $name): int
    {
        $stmt = $this->db->prepare('SELECT xp FROM players WHERE mv_name = ?');
        $stmt->execute([$name]);
        $row = $stmt->fetch();
        return $row ? (int) $row['xp'] : 0;
    }

    private function processAttack(int $px, int $py, int $reqX, int $reqY, int $playerf, int $playerlv, int &$playerhp, int &$playerxp, int &$playerkp, array &$map, array &$monsters): ?array
    {
        if ((abs($px - $reqX) + abs($py - $reqY)) !== 1) return null;
        if (!isset($map[$reqY][$reqX]) || $map[$reqY][$reqX] !== Tiles::MONSTER) return null;

        $sq = $this->square;
        $monster_max_health = (int) floor($playerf + (($reqX + $reqY) / $sq) * ($playerf + 1));
        $monster_attack = (int) floor($playerf / 2);
        $monster_level = (int) floor(($monster_attack + $monster_max_health) / 5);
        $percentHealth = (isset($monsters[$reqY][$reqX])) ? (int) $monsters[$reqY][$reqX] : 100;
        $monster_health = (int) floor($monster_max_health * $percentHealth / 100);

        $monster_bonus = $monster_level - $playerlv;
        if ($monster_bonus < 1) $monster_bonus = 1;

        $monsterLowBonus = $playerf < 5 ? 5 - $playerf : 0;
        $monster_experi = (int) floor($monsterLowBonus + ($monster_attack * 0.8) + 1 + $playerf - ($playerlv / 2));
        if ($monster_experi < 0) $monster_experi = 1;

        $monster_attack = $monster_attack - (int) floor($playerlv / 2);
        if ($monster_attack < 1) $monster_attack = 1;

        $player_attack = (int) floor($playerlv * 1.8) - (int) floor($playerf / 3);

        $monster_damage = 0;
        $player_damage = 0;
        $battleresult = 1;
        $percentTaken = 0;

        while ($monster_health > 0 && $playerhp > 0 && $percentTaken < 33) {
            $monster_health -= $player_attack;
            $monster_damage += $player_attack;
            $percentTaken = (int) round($monster_damage / $monster_max_health * 100);

            $playerhp -= $monster_attack;
            $player_damage += $monster_attack;

            if ($playerhp < 1) {
                $playerhp = 0;
                $battleresult = 2;
            }
            if ($monster_health < 1) {
                $monster_health = 0;
            }
        }

        $percentHealth = (int) round($monster_health / $monster_max_health * 100);

        if ($percentHealth === 0) {
            $playerxp += $monster_experi;
            $playerkp++;
            $map[$reqY][$reqX] = Tiles::DEAD_MONSTER;
        } else {
            if (!isset($monsters[$reqY])) $monsters[$reqY] = [];
            $monsters[$reqY][$reqX] = $percentHealth;
        }

        $stmt = $this->db->prepare('DELETE FROM monsters WHERE floor = ? AND x = ? AND y = ?');
        $stmt->execute([$playerf, $reqX, $reqY]);

        $stmt = $this->db->prepare('INSERT INTO monsters (x, y, health, `time`, floor) VALUES (?, ?, ?, 10, ?)');
        $stmt->execute([$reqX, $reqY, $percentHealth, $playerf]);

        return [
            'information' => [
                'type' => $battleresult,
                'monster' => [
                    'level' => $monster_level,
                    'damage' => $monster_damage,
                    'relativeX' => $reqX - $px,
                    'relativeY' => $reqY - $py,
                ],
                'self' => ['damage' => $player_damage],
            ],
            'hp' => $playerhp,
            'xp' => $playerxp,
            'kill' => $playerkp,
            'map' => $map,
            'monsters' => $monsters,
        ];
    }

    private function savePlayerState(string $name, int $x, int $y, int $xp, int $hp, int $mp, int $kill, int $save, int $floor, int $maxFloor, string $message, int $msgTs): void
    {
        $stmt = $this->db->prepare(
            'UPDATE players SET x = ?, y = ?, xp = ?, hp = ?, mp = ?, `kill` = ?, `save` = ?, floor = ?, max_floor = ?, message = ?, message_timestamp = ?, mv_time = 0 WHERE mv_name = ?'
        );
        $stmt->execute([$x, $y, $xp, $hp, $mp, $kill, $save, $floor, $maxFloor, $message, $msgTs, $name]);
    }

    public function castSpell(string $playerName, int $spellId, array $state): bool
    {
        if ($spellId === 1 && $state['warp1'] > 0 && $state['mp'] >= 5) {
            $stmt = $this->db->prepare('UPDATE players SET floor = 10, x = 21, y = 12, mp = mp - 5 WHERE mv_name = ?');
            $stmt->execute([$playerName]);
            return true;
        }

        if ($spellId === 3 && $state['level'] >= 3 && $state['mp'] >= 10) {
            $stmt = $this->db->prepare('UPDATE players SET floor = 4, x = 29, y = 29, mp = mp - 10 WHERE mv_name = ?');
            $stmt->execute([$playerName]);
            return true;
        }

        return false;
    }

    public function getLeaderboard(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT mv_name, floor, xp, `kill`, avatar_head, avatar_body FROM players WHERE mv_name != 'ika' ORDER BY xp DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function warpPlayer(string $name, int $floor, int $x, int $y): void
    {
        $name = preg_replace('/[^A-Za-z0-9]/', '', $name);
        $name = substr($name, 0, 3);
        $stmt = $this->db->prepare('UPDATE players SET floor = ?, x = ?, y = ? WHERE mv_name = ?');
        $stmt->execute([$floor, $x, $y, $name]);
    }
}
