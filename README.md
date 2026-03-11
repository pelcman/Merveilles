# Merveilles

Browser-based mini MMORPG.

Coding by DEEO, design by Aliceffekt (XXIIVV).
Modded by Pelcman (OLDHOME).

## Stack

- **Server**: PHP 7.4+ / MySQL (PDO)
- **Client**: HTML5 / Vanilla JS
- **Rendering**: 16x16 sprite sheets (GIF) + CSS background-position
- **Communication**: AJAX polling via Fetch API (JSON)
- **Auth**: Sessions + bcrypt

## Setup (Windows)

```
Copy `.env.example` to `.env` and edit as needed.
setup\win\setup.bat        # Full setup (PHP + DB + start)
setup\win\setup-db.bat     # Database only
start-server.bat           # Start server
```