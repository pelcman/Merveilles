# Merveilles

Browser-based mini MMORPG. Grid dungeon crawling, monster combat, and real-time cooperative multiplayer.

Coding by DEEO, design by Aliceffekt (XXIIVV).

## Stack

- **Server**: PHP 7.4+ / MySQL (PDO)
- **Client**: HTML5 / Vanilla JS
- **Rendering**: 16x16 sprite sheets (GIF) + CSS background-position
- **Communication**: AJAX polling via Fetch API (JSON)
- **Auth**: Sessions + bcrypt

## Setup (Windows)

```
setup\win\setup.bat        # Full setup (PHP + DB + start)
setup\win\setup-db.bat     # Database only
start-server.bat           # Start server
```

Copy `.env_example` to `.env` and edit as needed.

## Routes

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` `/login` | Login page |
| POST | `/login` | Login / auto-register |
| GET | `/logout` | Logout |
| GET | `/game` | Game (auth required) |
| GET | `/api/game` | Game state API |
| GET | `/api/cast` | Spell cast API |
| GET | `/editor` | Map editor (admin) |
| GET | `/admin` | Admin panel (admin) |

## Structure

```
public/          Web root
  index.php      Front controller
  js/            Client JS + map editor
  img/           Sprites and UI
  audio/         BGM (6 tracks)
  levels/        Cached 64x64 tile maps
src/             Server logic
  Game.php       Combat, healing, stairs, portals, spells
  MapGenerator.php  Procedural map generation
  Auth.php       Login / register / bcrypt
  Database.php   PDO singleton
  Tiles.php      Tile type constants
templates/       HTML templates
sql/schema.sql   Database schema
setup/win/       Windows setup scripts
```
