/* ============================================================
   Merveilles – Vanilla JS client (MooTools removed)
   ============================================================ */

// --- DOM helper ---
function el(tag, attrs, children) {
    var e = document.createElement(tag);
    if (attrs) {
        for (var k in attrs) {
            if (!attrs.hasOwnProperty(k)) continue;
            var v = attrs[k];
            if (k === 'className')       e.className = v;
            else if (k === 'cssText')    e.style.cssText = v;
            else if (k === 'onclick')    e.addEventListener('click', v);
            else if (k === 'onmousedown') e.addEventListener('mousedown', v);
            else if (k === 'onmouseup')  e.addEventListener('mouseup', v);
            else if (k === 'ondblclick') e.addEventListener('dblclick', v);
            else e.setAttribute(k, v);
        }
    }
    if (children) {
        if (!Array.isArray(children)) children = [children];
        for (var i = 0; i < children.length; i++) {
            if (children[i]) e.appendChild(children[i]);
        }
    }
    return e;
}

function adopt(parent, items) {
    if (!Array.isArray(items)) items = [items];
    for (var i = 0; i < items.length; i++) {
        if (items[i]) parent.appendChild(items[i]);
    }
}

function empty(node) { node.innerHTML = ''; }

// ============================================================
var Merveilles = {

    /* data */
    map: [],
    players: [],
    monsters: [],
    status: { x: 0, y: 0, xp: null, percentXp: null, level: null, hp: null, mp: null },

    viewport: { x: 17, y: 17, middleX: 8, middleY: 8 },

    /* elements */
    elementPosition: null,
    elementXPText: null,
    elementXPBar: null,
    elementLevelText: null,
    elementHP: null,
    elementMP: null,
    elementEquipText: null,
    elementNav: null,

    elementMessage: null,

    elementPlayerChatMessage: null,
    elementPlayerChat: null,
    elementPlayer: null,
    elementGround: null,
    elementMap: null,

    /* internal */
    directions: { left: 0, right: 0, up: 0, down: 0 },
    timer: null,
    refreshTimer: null,
    eventTimer: null,
    requestEnable: true,
    messageOpened: false,
    messageStack: [],
    flipped: false,
    isDead: false,

    currentEvent: null,
    moved: false,
    updateWithoutMove: 0,

    setViewport: function (x, y) {
        this.viewport = {
            x: (x < 17 ? 17 : x),
            y: y,
            middleX: Math.floor(x / 2),
            middleY: Math.floor(y / 2)
        };
    },

    log: function () {},

    init: function (data) {
        var self = this;
        var width = this.viewport.x * 16;
        var height = this.viewport.y * 16;

        var body = document.body;
        body.classList.add('dimension' + this.viewport.x + 'x' + this.viewport.y);

        this.elementPosition = el('div', { id: 'position' });
        body.appendChild(this.elementPosition);

        // --- Status bar ---
        var statusDiv = el('div', { id: 'status' });

        var table = el('table');

        // Row 1: Level / XP
        var tr = el('tr');
        this.elementLevelText = el('td');
        tr.appendChild(this.elementLevelText);
        this.elementXPText = el('td');
        tr.appendChild(this.elementXPText);
        var td = el('td', { valign: 'top', width: '50px' });
        var bar = el('div', { className: 'bar' });
        this.elementXPBar = el('div', { className: 'xp' });
        bar.appendChild(this.elementXPBar);
        td.appendChild(bar);
        tr.appendChild(td);
        table.appendChild(tr);

        // Row 2: HP
        tr = el('tr');
        td = el('td');
        adopt(td, this.generateText('hp'));
        tr.appendChild(td);
        this.elementHPText = el('td');
        tr.appendChild(this.elementHPText);
        td = el('td', { valign: 'top', width: '50px' });
        bar = el('div', { className: 'bar' });
        this.elementHPBar = el('div', { className: 'hp' });
        bar.appendChild(this.elementHPBar);
        td.appendChild(bar);
        tr.appendChild(td);
        table.appendChild(tr);

        // Row 3: MP
        tr = el('tr');
        td = el('td');
        adopt(td, this.generateText('mp'));
        tr.appendChild(td);
        this.elementMPText = el('td');
        tr.appendChild(this.elementMPText);
        td = el('td', { valign: 'top', width: '50px' });
        bar = el('div', { className: 'bar' });
        this.elementMPBar = el('div', { className: 'mp' });
        bar.appendChild(this.elementMPBar);
        td.appendChild(bar);
        tr.appendChild(td);
        table.appendChild(tr);

        statusDiv.appendChild(table);

        var avatar = el('div', { id: 'avatar' });
        statusDiv.appendChild(avatar);

        body.appendChild(statusDiv);

        // --- Ground / Map ---
        this.elementGround = el('div', { id: 'ground', cssText: 'width:' + width + 'px; height:' + height + 'px' });

        this.elementHome = el('div', { id: 'home' });
        this.elementNav = el('div', { id: 'nav' });

        var navInner = el('div');

        this.elementPlayer = el('a', {
            className: 'starter',
            href: '#',
            cssText: 'top:' + (height / 2 - 8) + 'px; left:' + (width / 2 - 8) + 'px',
            onclick: function () { self.fireEvent('c'); return false; }
        });

        adopt(this.elementPlayer, [
            el('div', { className: 'playerShadow' }),
            el('div', { className: 'playerHead', cssText: 'background-position: 0px ' + (data.status.avatarHead * 8) + 'px' }),
            el('div', { className: 'playerBody', cssText: 'background-position: -16px ' + (data.status.avatarBody * 8) + 'px' })
        ]);

        navInner.appendChild(this.elementPlayer);

        // Player chat bubble
        this.elementPlayerChat = el('div', {
            className: 'test',
            cssText: 'position:absolute; top:' + (height / 2 - 24) + 'px; left:' + (width / 2 - 12) + 'px; z-index:11;'
        });
        this.elementPlayerChatMessage = el('div', {
            className: 'test2',
            cssText: 'position:absolute; width:24px; height:6px; background-color:#fff; padding:2px 0px 2px 2px; z-index:11;'
        });
        this.elementPlayerChat.appendChild(this.elementPlayerChatMessage);
        this.elementPlayerChat.appendChild(el('div', {
            className: 'test2',
            cssText: 'position:absolute; width:2px; height:2px; background:#fff; top:10px; left:4px; z-index:11;'
        }));
        navInner.appendChild(this.elementPlayerChat);

        // Arrow buttons
        var dirs = ['right', 'left', 'up', 'down'];
        for (var di = 0; di < dirs.length; di++) {
            (function (dir) {
                navInner.appendChild(el('a', {
                    className: 'arr_' + dir,
                    href: '#',
                    onmousedown: function () { this.blur(); self.fireEvent(dir); },
                    onmouseup: function () { this.blur(); self.fireEvent('stop', dir); }
                }));
            })(dirs[di]);
        }

        this.elementNav.appendChild(navInner);
        this.elementHome.appendChild(this.elementNav);

        this.elementMap = el('div', { id: 'map' });
        this.elementHome.appendChild(this.elementMap);

        this.elementGround.appendChild(this.elementHome);
        body.appendChild(this.elementGround);

        // Window
        this.Window.init(this);

        // Message overlay
        this.elementMessage = el('div', { className: 'message hide', cssText: 'padding: 8px 8px' });
        this.elementNav.appendChild(this.elementMessage);

        // Chat
        this.Chat.init(this);

        // Keyboard
        window.addEventListener('keydown', function (e) {
            if (self.currentEvent !== e.key) self.fireEvent(e.key);
        });
        window.addEventListener('keyup', function (e) {
            self.fireEvent('stop', e.key);
        });

        // Compatibility: map arrow key names
        this._keyMap = { ArrowLeft: 'left', ArrowRight: 'right', ArrowUp: 'up', ArrowDown: 'down' };

        this.elementGround.className = 'level' + Math.floor(this.status.level / 10) + '0 floor' + this.status.floor;

        this.refresh(data);
        this.timer = setTimeout(function () { self.update(); }, 4000);
    },

    showMessage: function (dom) {
        empty(this.elementMessage);
        this.messageOpened = true;
        if (Array.isArray(dom)) { adopt(this.elementMessage, dom); }
        else { this.elementMessage.appendChild(dom); }
        this.elementMessage.classList.remove('hide');
    },

    hideMessage: function () {
        this.elementMessage.classList.add('hide');
        this.messageOpened = false;
    },

    refresh: function (data, noVisualRefresh) {
        if (this.Chat.opened || this.Window.opened) return;

        var oldStatus = this.status;

        if (data) {
            if (data.map) this.map = data.map;
            if (data.monsters) this.monsters = data.monsters;
            if (data.players) this.players = data.players;
            this.status = data.status;
        }

        var letters = null;

        /* Status update */
        if (oldStatus.build !== this.status.build) {
            empty(this.elementPlayer);
            adopt(this.elementPlayer, [
                el('div', { className: 'playerShadow' }),
                el('div', { className: 'playerHead', cssText: 'background-position: 0px ' + (data.status.avatarHead * 8) + 'px' }),
                el('div', { className: 'playerBody', cssText: 'background-position: -16px ' + (data.status.avatarBody * 8) + 'px' })
            ]);
        }

        if (oldStatus.message !== this.status.message) {
            if (this.status.message === '' || this.status.message == null) {
                this.elementPlayerChat.classList.add('hide');
            } else {
                empty(this.elementPlayerChatMessage);
                this.Chat.showMessage(this.status.message, 2, 2, this.elementPlayerChatMessage);
                this.elementPlayerChat.classList.remove('hide');
            }
        }

        if (oldStatus.xp !== this.status.xp) {
            empty(this.elementXPText);
            adopt(this.elementXPText, this.generateText(this.status.xp.toString() + 'xp'));
            this.elementXPBar.style.width = this.status.percentXp.toString() + '%';

            empty(this.elementLevelText);
            letters = this.generateText('lvl');
            letters = letters.concat(this.generateText(this.status.level.toString(), 'white'));
            adopt(this.elementLevelText, letters);
        }

        this.elementGround.className = 'level' + this.status.level + ' floor' + this.status.floor;

        if (this.status.hp > 0) {
            this.isDead = false;
        } else {
            this.isDead = true;
            this.elementGround.className += ' phantom';
        }

        if (oldStatus.hp !== this.status.hp) {
            empty(this.elementHPText);
            adopt(this.elementHPText, this.generateText(this.status.hp.toString() + '/30'));
            this.elementHPBar.style.width = Math.ceil((this.status.hp / 30) * 100).toString() + '%';
        }

        if (oldStatus.mp !== this.status.mp) {
            empty(this.elementMPText);
            adopt(this.elementMPText, this.generateText(this.status.mp.toString() + '/30'));
            this.elementMPBar.style.width = Math.ceil((this.status.mp / 30) * 100).toString() + '%';
        }

        if (noVisualRefresh) {
            this.status.x = oldStatus.x;
            this.status.y = oldStatus.y;
        }

        empty(this.elementPosition);
        adopt(this.elementPosition, this.generateText('f' + this.status.floor + ' / ' + this.status.x + '-' + this.status.y));

        var tile = this.map[this.status.y] != null ? this.map[this.status.y][this.status.x] : null;
        var classname = 'starter';
        if (tile === 7) classname += ' hide';
        else if (tile === 8) classname += ' inwater';
        if (this.flipped) classname += ' flip';
        if (this.elementPlayer.className !== classname) this.elementPlayer.className = classname;

        if (noVisualRefresh) return;

        clearTimeout(this.refreshTimer);

        /* Map update */
        var visibBaseX = this.viewport.middleX;
        var visibBaseY = this.viewport.middleY;

        var groundx = ((this.status.x + visibBaseX + 1) * 16) - 1152;
        var groundy = ((this.status.y + visibBaseY + 1) * 16) - 1152;

        this.elementGround.style.backgroundPosition = groundx + 'px ' + groundy + 'px';
        if (data && data.background) this.elementGround.style.backgroundImage = 'url(img/' + data.background + ')';

        /* display all the tiles */
        empty(this.elementMap);

        var self = this;
        var y = 0;

        while (y < this.viewport.y) {
            var currentY = this.status.y + y - visibBaseY;
            var x = 0;

            while (x < this.viewport.x) {
                var currentX = this.status.x + x - visibBaseX;
                var t = 0;

                if (this.map[currentY] != null && this.map[currentY][currentX] != null) {
                    t = this.map[currentY][currentX];
                }

                if (t !== 0) {
                    var cn = null;
                    var events = {};
                    var style = '';
                    var healthPercent = null;

                    if (t === 1) {
                        cn = 'wall';
                    } else if (t > 19) {
                        cn = 'add-wall-' + (t - 19);
                    } else if (t === 2) {
                        var attackable = !this.isDead && (
                            ((currentX === this.status.x) && (currentY === this.status.y + 1 || currentY === this.status.y - 1)) ||
                            ((currentY === this.status.y) && (currentX === this.status.x + 1 || currentX === this.status.x - 1))
                        );
                        healthPercent = (this.monsters[currentY] && this.monsters[currentY][currentX]);

                        if (attackable) {
                            events.click = (function (cx, cy) {
                                return function () { self.attack(cx, cy); };
                            })(currentX, currentY);
                            cn = 'monstera';
                        } else {
                            cn = 'monster';
                        }
                    } else if (t === 3) {
                        cn = 'monsterd';
                    } else if (t === 4 || t === 5) {
                        var takeable = (
                            ((currentX === this.status.x) && (currentY === this.status.y + 1 || currentY === this.status.y - 1)) ||
                            ((currentY === this.status.y) && (currentX === this.status.x + 1 || currentX === this.status.x - 1))
                        );
                        cn = (t === 4 ? 'stairup' : 'stairdown');
                        if (takeable) {
                            events.click = (function (cx, cy) {
                                return function () { self.takeStair(cx, cy); };
                            })(currentX, currentY);
                            cn += ' clickable';
                        }
                    } else if (typeof t === 'object' && t !== null && t.image !== '') {
                        cn = 'special';
                        style = 'background-image:url(img/specials/' + t.image + ')';
                    }

                    if (cn != null) {
                        var posX = (this.status.x - currentX + visibBaseX) * 16;
                        var posY = (this.status.y - currentY + visibBaseY) * 16;

                        var tileEl = el('div', {
                            className: cn,
                            cssText: 'top:' + posY + 'px; left:' + posX + 'px;' + style
                        });
                        if (events.click) tileEl.addEventListener('click', events.click);
                        this.elementMap.appendChild(tileEl);

                        if (healthPercent) {
                            var hpBar = el('div', { className: 'bar', cssText: 'top:' + (posY - 6) + 'px; left:' + (posX - 2) + 'px;' });
                            var hpInner = el('div', { className: 'health' });
                            hpInner.appendChild(el('div', { cssText: 'width:' + healthPercent + '%' }));
                            hpBar.appendChild(hpInner);
                            this.elementMap.appendChild(hpBar);
                        }
                    }
                }
                x++;
            }
            y++;
        }

        /* display all the players */
        var l = this.players.length;
        for (var i = 0; i < l; i++) {
            var player = this.players[i];
            var px = this.status.x - player.x + visibBaseX;
            var py = this.status.y - player.y + visibBaseY;

            if (px < 0 || py < 0 || px >= this.viewport.x || py >= this.viewport.y || (px === this.viewport.middleX && py === this.viewport.middleY)) continue;

            var ptile = this.map[player.y] != null ? this.map[player.y][player.x] : null;
            if (ptile != null && ptile !== 7) {
                px *= 16;
                py *= 16;

                var nx = px - 4;
                var ny = py - 12;
                var healable = player.hp < 16 && this.status.mp > 0 && (player.hp > 0 || this.status.level > 29);

                var nameEl = el('div', { cssText: 'width:24px; height:6px; position:absolute; top:' + ny + 'px; left:' + nx + 'px; background-color:#fff; padding:2px 0px 2px 2px; z-index:11' });
                if (player.message) {
                    this.Chat.showMessage(player.message, 2, 2, nameEl);
                } else {
                    adopt(nameEl, this.generateText(player.name, (healable ? 'red' : 'normal')));
                }
                this.elementMap.appendChild(nameEl);

                this.elementMap.appendChild(el('div', {
                    cssText: 'width:2px; height:2px; position:absolute; background:#fff; top:' + (py - 2) + 'px; left:' + px + 'px'
                }));

                var pcn = '';
                if (player.hp < 1) pcn += 'spirit ';
                if (ptile === 8) pcn += 'inwater ';

                var pDiv;
                if (healable) {
                    pDiv = el('div', {
                        cssText: 'width:16px; height:16px; position:absolute; top:' + py + 'px; left:' + px + 'px',
                        className: pcn + 'clickable',
                        onclick: (function (pname) { return function () { self.heal(pname); }; })(player.name)
                    });
                } else {
                    pDiv = el('div', {
                        cssText: 'width:16px; height:16px; position:absolute; top:' + py + 'px; left:' + px + 'px',
                        className: pcn
                    });
                }
                adopt(pDiv, [
                    el('div', { className: 'playerShadow' }),
                    el('div', { className: 'playerHead', cssText: 'background-position: 0px ' + (player.avatarHead * 8) + 'px' }),
                    el('div', { className: 'playerBody', cssText: 'background-position: -16px ' + (player.avatarBody * 8) + 'px' })
                ]);
                this.elementMap.appendChild(pDiv);
            }
        }

        /* battle result */
        if (data && data.information) {
            this.showInformation(data.information, oldStatus);
        }

        var self2 = this;
        this.refreshTimer = setTimeout(function () { self2.refresh(); }, 2000);
    },

    showInformation: function (info, oldStatus) {
        var content = null;
        var self = this;

        switch (info.type) {
            case 1:
            case 2:
                var selfDmg = '-' + info.self.damage;
                var monsDmg = '' + info.monster.damage;
                var cx = this.viewport.middleX * 16;
                var cy = (this.viewport.middleY * 16) - 12;

                this.elementMap.appendChild(
                    el('div', { className: 'player-damage', cssText: 'z-index:200;position:absolute; top:' + (cy - 10) + 'px; left:' + cx + 'px;' },
                        this.generateText(selfDmg, 'red'))
                );
                this.elementMap.appendChild(
                    el('div', { className: 'monster-damage', cssText: 'z-index:200;position:absolute; top:' + (cy - info.monster.relativeY * 16) + 'px; left:' + (cx - info.monster.relativeX * 16) + 'px;' },
                        this.generateText(monsDmg, 'red'))
                );
                break;

            case 9:
                content = el('div');
                var d1 = el('div', { cssText: 'clear:both; padding-left:50px; padding-bottom:12px' });
                adopt(d1, this.generateText('healing', 'white'));
                content.appendChild(d1);

                var difMp = oldStatus.mp - this.status.mp;
                var difXp = this.status.xp - oldStatus.xp;
                var d2 = el('div', { cssText: 'clear:both; margin-left:0px; padding-bottom:15px;' });
                adopt(d2, this.generateText('lvl' + info.playerLevel, 'white'));
                adopt(d2, this.generateText(' +' + info.heal + 'hp'));
                adopt(d2, this.generateText(' +' + difXp + 'xp '));
                adopt(d2, this.generateText('-' + difMp + 'mp', 'red'));
                content.appendChild(d2);
                break;

            default:
                return;
        }

        if (content) {
            content.appendChild(el('hr', { cssText: 'clear:both;border:0; border-top:2px dotted #cacbb7; padding-bottom:3px' }));
            var a = el('a', {
                href: '#',
                cssText: 'clear:both; background-image:none; width:60px; padding-left:60px; margin-top:4px;',
                onclick: function () { self.Window.hide(); }
            });
            adopt(a, self.generateText('close'));
            content.appendChild(a);
            this.Window.show(content);
        }
    },

    generateText: function (texte, color) {
        texte = texte.toLowerCase();
        var letters = [];
        var col = (color === 'red' ? 'r' : (color === 'white' ? 'w' : ''));

        for (var i = 0; i < texte.length; i++) {
            var c = texte.charAt(i);
            switch (c) {
                case '/': c = 'slas'; break;
                case ' ': c = 'spac'; break;
                case '+': c = 'plus'; break;
                case '-': c = 'minu'; break;
            }
            letters.push(el('div', { className: 'letter' + col + c }));
        }
        return letters;
    },

    message: function (m) {
        if (typeof m === 'string') {
            m = m.replace('\n', '').split('\r');
        }
        this.messageStack = m;
        var letters = this.generateText(this.messageStack[0]);
        this.messageStack.shift();
        this.showMessage(letters);
    },

    fireEvent: function (e, e2) {
        var self = this;
        this.moved = true;

        clearTimeout(this.eventTimer);

        // Map arrow key names
        if (this._keyMap && this._keyMap[e]) e = this._keyMap[e];
        if (this._keyMap && e2 && this._keyMap[e2]) e2 = this._keyMap[e2];

        if (e !== 'stop') {
            if (this.Window.opened) { this.Window.hide(); return; }
            if (this.messageOpened) {
                if (this.messageStack.length > 0) this.message(this.messageStack);
                else this.hideMessage();
                return;
            }
        } else {
            if      (e2 === 'left')  this.directions.left = 0;
            else if (e2 === 'right') this.directions.right = 0;
            else if (e2 === 'up')    this.directions.up = 0;
            else if (e2 === 'down')  this.directions.down = 0;
        }

        if (this.Chat.opened) {
            if (e === 'c') this.Chat.hide();
            return;
        } else {
            if (e === 'c') this.Chat.show();
        }

        var difX = 0, difY = 0;

        if      (e === 'left')  this.directions.left = 1;
        else if (e === 'right') this.directions.right = 1;
        else if (e === 'up')    this.directions.up = 1;
        else if (e === 'down')  this.directions.down = 1;

        var doTimer = false;

        if      (this.directions.left)  difX = 1;
        else if (this.directions.right) difX = -1;
        if      (this.directions.up)    difY = 1;
        else if (this.directions.down)  difY = -1;

        if (difY !== 0 || difX !== 0) {
            var sx = this.status.x;
            var sy = this.status.y;
            var newX = sx + difX;
            var newY = sy + difY;

            if (difX > 0) this.flipped = true;
            else if (difX < 0) this.flipped = false;

            var diag = (difX !== 0 && difY !== 0);
            var tile = 1, adj1 = 1, adj2 = 1;

            if (this.map[newY] != null && this.map[newY][newX] != null) tile = this.map[newY][newX];
            if (diag && this.map[newY] != null && this.map[newY][sx] != null) adj1 = this.map[newY][sx];
            if (diag && this.map[sy] != null && this.map[sy][newX] != null) adj2 = this.map[sy][newX];

            doTimer = true;

            var walkable = function (t) { return t < 1 || t === 3 || t === 8 || t === 7 || t === 9 || t === 4 || t === 5 || t === 10 || t === 11; };

            if (diag && !walkable(adj1) && !walkable(adj2)) {
                doTimer = false;
            } else if (tile < 1 || tile === 3 || tile === 8 || tile === 7) {
                this.move(newX, newY);
            } else if (tile === 9) {
                if (this.status.hp < 30 || this.status.mp < 30) {
                    this.status.y = newY;
                    this.status.x = newX;
                    this.updateWithRefresh();
                } else {
                    this.move(newX, newY);
                }
            } else if (tile === 2) {
                if (!this.isDead) { this.attack(newX, newY); doTimer = false; }
                else this.move(newX, newY);
            } else if (tile === 4 || tile === 5 || tile === 10 || tile === 11) {
                if (this.isDead && this.status.maxFloor === this.status.floor && (tile === 4 || tile === 10)) {
                    this.message("Lowly ghosts aren't welcome here.");
                    doTimer = false;
                } else {
                    this.takeStair(newX, newY);
                    doTimer = false;
                }
            } else if (typeof tile === 'object' && tile !== null) {
                this.triggerSpecial(tile);
                doTimer = false;
            } else {
                doTimer = false;
            }
        }

        if (doTimer) {
            this.eventTimer = setTimeout(function () { self.fireEvent(e); }, 180);
            this.currentEvent = e;
        } else {
            this.eventTimer = null;
            this.currentEvent = null;
        }
    },

    takeStair: function (x, y) {
        this.request('stair', x, y, this.takeStairCallback);
    },
    takeStairCallback: function (data) {
        this.refresh(data);
        this.requestEnable = true;
    },

    heal: function (name) {
        this.request('heal', name, name, this.healCallback);
    },
    healCallback: function (data) {
        this.refresh(data);
        this.requestEnable = true;
    },

    triggerSpecial: function (special) {
        if (special.message !== '') {
            this.message(special.message);
        } else if (special.toFloor > 0) {
            this.request('portal', special.x, special.y, this.portalCallback);
        }
    },
    portalCallback: function (data) {
        this.refresh(data);
        this.requestEnable = true;
    },

    move: function (x, y) {
        if (!this.requestEnable) return;

        var data = { status: Object.assign({}, this.status) };
        data.status.x = x;
        data.status.y = y;

        var healablePlayer = false;
        for (var i = 0; i < this.players.length && !healablePlayer; i++) {
            var p = this.players[i];
            if (p.y === data.status.y && p.x === data.status.x) {
                if (p.hp < 16 && this.status.mp > 0 && (p.hp > 0 || this.status.level > 29)) {
                    healablePlayer = p.name;
                }
            }
        }

        if (healablePlayer) {
            this.status = data.status;
            this.heal(healablePlayer);
        } else {
            this.refresh(data);
        }
    },

    attack: function (x, y) {
        if (this.isDead) return;
        this.request('attack', x, y, this.attackCallback);
    },
    attackCallback: function (data) {
        this.refresh(data);
        this.requestEnable = true;
    },

    update: function () {
        this.request('update', 0, 0, this.updateCallback);
    },
    updateCallback: function (data) {
        this.refresh(data, true);
        this.requestEnable = true;
    },

    updateWithRefresh: function () {
        this.request('updateWithRefresh', 0, 0, this.updateWithRefreshCallback);
    },
    updateWithRefreshCallback: function (data) {
        this.refresh(data);
        this.requestEnable = true;
    },

    request: function (action, x, y, callback) {
        if (!this.requestEnable || (this.Chat.opened && action !== 'chat')) return;

        this.requestEnable = action !== 'update' ? false : true;
        clearTimeout(this.timer);

        var self = this;
        var delayNext = 2000;

        if (action === 'update' && this.moved === false) {
            this.updateWithoutMove++;
            if (this.updateWithoutMove > 10) delayNext = 10000;
            else if (this.updateWithoutMove > 4) delayNext = 4000;
        } else {
            this.moved = false;
            this.updateWithoutMove = 0;
        }

        var params = new URLSearchParams({
            position_x: this.status.x,
            position_y: this.status.y,
            action: action,
            x: x,
            y: y,
            viewport_x: this.viewport.x,
            viewport_y: this.viewport.y
        });

        fetch('/api/game?' + params.toString())
            .then(function (resp) { return resp.json(); })
            .then(function (data) {
                self.timer = setTimeout(function () { self.update(); }, delayNext);
                callback.call(self, data);
            })
            .catch(function () {
                self.timer = setTimeout(function () { self.update(); }, delayNext);
                self.requestEnable = true;
            });
    }
};

// ============================================================
// Window sub-object
// ============================================================
Merveilles.Window = {
    parent: null,
    opened: false,
    element: null,
    container: null,
    init: function (parent) {
        this.parent = parent;
        this.container = el('div', {
            className: 'window hide',
            cssText: 'left:' + ((parent.viewport.x * 16 - 176) / 2) + 'px; top:' + ((parent.viewport.y * 16 - 108) / 2) + 'px;'
        });
        this.element = el('div', { cssText: 'padding:10px; font-size:12px;' });
        this.container.appendChild(this.element);
        this.parent.elementNav.appendChild(this.container);
    },
    show: function (dom) {
        this.opened = true;
        this.element.appendChild(dom);
        this.container.classList.remove('hide');
    },
    hide: function () {
        this.container.classList.add('hide');
        empty(this.element);
        this.opened = false;
    }
};

// ============================================================
// Chat sub-object
// ============================================================
Merveilles.Chat = {
    parent: null,
    opened: false,
    element: null,
    value: [],
    init: function (parent) {
        this.parent = parent;
        this.element = el('div', { className: 'chat hide' });

        var positions = [
            [6, 6], [6, 60], [6, 114],
            [60, 6], [60, 60], [60, 114],
            [114, 6], [114, 60], [114, 114]
        ];
        for (var i = 0; i < 9; i++) {
            this.element.appendChild(el('div', {
                className: 'button',
                id: 'chat-button' + (i + 1),
                cssText: 'margin-top:' + positions[i][0] + 'px; margin-left:' + positions[i][1] + 'px;'
            }));
        }

        var self = this;
        this.element.appendChild(el('div', { className: 'clear', cssText: 'margin-top:172px; margin-left:18px;', ondblclick: function () { self.hide(); } }));
        this.element.appendChild(el('div', { className: 'next', cssText: 'margin-top:172px; margin-left:72px;' }));
        this.element.appendChild(el('div', { className: 'send', cssText: 'margin-top:174px; margin-left:126px;' }));

        this.element.addEventListener('click', function (e) { self.clickHandler(e); });
        this.parent.elementNav.appendChild(this.element);
    },
    show: function () {
        this.opened = true;
        this.clear();
        this.element.classList.remove('limit');
        this.element.classList.remove('hide');
    },
    hide: function () {
        this.element.classList.add('hide');
        this.opened = false;
    },
    request: function () {
        var self = this;
        this.parent.request('chat', this.value.join(','), 0, function (data) {
            self.hide();
            self.parent.refresh(data, true);
            self.parent.requestEnable = true;
        });
    },
    clickHandler: function (e) {
        var t = e.target;
        if (t.classList.contains('button')) {
            if (t.classList.contains('buttonsel1'))       { t.classList.remove('buttonsel1'); t.classList.add('buttonsel2'); }
            else if (t.classList.contains('buttonsel2'))  { t.classList.remove('buttonsel2'); }
            else                                          { t.classList.add('buttonsel1'); }
        } else if (t.classList.contains('clear')) {
            this.clear();
        } else if (t.classList.contains('next')) {
            this.value.push(this.getState());
            if (this.value.length === 2) this.element.classList.add('limit');
            this.clear();
        } else if (t.classList.contains('send')) {
            this.value.push(this.getState());
            this.request();
            this.value = [];
        }
    },
    getState: function () {
        var children = this.element.children;
        var value = '';
        for (var i = 0; i < 9; i++) {
            var c = children[i];
            value += c.classList.contains('buttonsel1') ? '1' : (c.classList.contains('buttonsel2') ? '2' : '0');
        }
        return value;
    },
    clear: function () {
        var children = this.element.children;
        for (var i = 0; i < children.length; i++) {
            children[i].classList.remove('buttonsel1');
            children[i].classList.remove('buttonsel2');
        }
    },
    showMessage: function (message, top, left, element) {
        if (element == null || element === '') return;
        var a = message.split(',');
        var divs = [];
        var w = 2;
        for (var x = 0, l = a.length; x < l; x++) {
            for (var y = 0; y < 9; y++) {
                var c = a[x].charAt(y);
                if (c !== '0') {
                    divs.push(el('div', {
                        className: 'scribble' + c,
                        cssText: 'top:' + (top + w * parseInt(y / 3)) + 'px; left:' + (left + w * ((y % 3) + x * 4)) + 'px;'
                    }));
                }
            }
        }
        adopt(element, divs);
    }
};

// ============================================================
// Global helper
// ============================================================
function toggle_visibility(id) {
    var e = document.getElementById(id);
    if (e) e.style.display = (e.style.display === 'block') ? 'none' : 'block';
    return false;
}
