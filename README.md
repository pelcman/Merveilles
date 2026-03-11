# Merveilles

ブラウザベースのミニMMORPG。グリッド型のダンジョン探索、モンスター戦闘、プレイヤー間協力をリアルタイムで提供する。
DEEO によるコーディング、Aliceffekt (XXIIVV) によるデザイン。

## 技術スタック

| レイヤー | 技術 |
|---------|------|
| サーバー | PHP 7.4+ / MySQL (PDO + プリペアドステートメント) |
| クライアント | HTML5 / CSS3 / Vanilla JavaScript (外部ライブラリ不要) |
| 描画 | 16x16 スプライトシート (GIF) + CSS background-position |
| 通信 | Fetch API による AJAX ポーリング (JSON) |
| 認証 | セッション + bcrypt パスワードハッシュ |
| 音響 | フロア連動の MP3 BGM (6トラック) |

## アーキテクチャ

```
Client (public/js/merveilles.js)     Server (public/index.php → src/)
  |                                     |
  |-- GET /api/game?action=...  ----->  |-- Game.php: アクション処理
  |                                     |-- MapGenerator.php: マップ生成
  |                                     |-- PDO: DB更新
  |<-- JSON (状態, マップ, 他PL) -----  |
  |-- refresh() で再描画                |
  |-- 4秒後に再ポーリング              |
```

フロントコントローラパターンを採用。`public/index.php` が全リクエストを受け、ルーティングにより適切なロジック (src/) とテンプレート (templates/) を呼び出す。

## ディレクトリ構成

```
/
├── public/                  # ドキュメントルート (Webサーバーはここを公開)
│   ├── index.php            # フロントコントローラ (ルーティング)
│   ├── .htaccess            # Apache URL リライト
│   ├── style.css            # 全スタイル定義 (スプライト座標・カスタムフォント)
│   ├── js/
│   │   ├── merveilles.js    # ゲームクライアント (Vanilla JS)
│   │   └── jsme/            # マップエディタ (MooTools, 管理者専用)
│   ├── img/                 # スプライト・UI・背景画像
│   ├── audio/               # BGM (mp3 x 6トラック)
│   └── levels/              # シリアライズされた 64x64 タイルマップ (.dat)
│
├── src/                     # サーバーサイドロジック
│   ├── bootstrap.php        # オートロード・セッション・DB初期化
│   ├── Database.php         # PDO シングルトン (MySQL接続)
│   ├── Tiles.php            # タイルタイプ定数 + ユーティリティ
│   ├── Auth.php             # 認証 (ログイン/登録/ログアウト/bcrypt)
│   ├── MapGenerator.php     # 手続き的マップ生成 + ファイルキャッシュ
│   └── Game.php             # ゲームロジック (戦闘・回復・階段・ポータル・スペル)
│
├── templates/               # HTMLテンプレート (ロジック分離)
│   ├── login.php            # ログイン画面
│   ├── game.php             # メインゲーム画面
│   ├── editor.php           # マップエディタ画面
│   ├── admin.php            # 管理画面 (プレイヤーワープ・リーダーボード)
│   └── partials/
│       ├── head.php         # 共通 HTML head
│       ├── guide.php        # ガイドサイドバー
│       ├── spellbook.php    # スペルブックUI
│       └── audio.php        # オーディオプレイヤー
│
├── sql/
│   └── schema.sql           # MySQL スキーマ (正規化済み)
│
├── old/                     # リファクタリング前の旧コード
├── package.json
└── README.md
```

## セットアップ

```bash
# 1. MySQL でデータベースを作成
mysql -u root < sql/schema.sql

# 2. 環境変数で DB 接続情報を設定 (省略時は localhost/root/空パスワード)
export DB_HOST=localhost
export DB_NAME=merveilles
export DB_USER=root
export DB_PASS=

# 3. Apache/Nginx のドキュメントルートを public/ に設定
# Apache: DocumentRoot "/path/to/Merveilles/public"
# Nginx:  root /path/to/Merveilles/public;

# 4. PHP ビルトインサーバーで動作確認
php -S localhost:8080 -t public
```

## ルーティング

| メソッド | パス | 機能 |
|---------|------|------|
| GET | `/` `/login` | ログイン画面 |
| POST | `/login` | ログイン / 自動登録 |
| GET | `/logout` | ログアウト |
| GET | `/game` | ゲーム画面 (要認証) |
| GET | `/api/game` | ゲーム状態 API (AJAX) |
| GET | `/api/cast` | スペル発動 API |
| GET | `/editor` | マップエディタ (管理者のみ) |
| GET | `/admin` | 管理画面 (管理者のみ) |

## データベース設計

3テーブル構成 (`sql/schema.sql`):

### players (プレイヤー)

| カラム | 型 | 用途 |
|--------|---|------|
| mv_name | VARCHAR(3) UNIQUE | ユーザー名 (英数字のみ) |
| mv_password | VARCHAR(255) | bcrypt ハッシュ |
| x, y | INT | 現在座標 |
| floor / max_floor | INT | 現在階 / 到達最深階 |
| xp | INT | 経験値 (レベル = floor(cbrt(xp))) |
| hp, mp | INT | 体力・魔力 (上限30) |
| kill / save | INT | 撃破数 / セーブ回数 (ビルド値算出用) |
| avatar_head / avatar_body | TINYINT | 外見 (登録時ランダム 2-13) |
| warp1-4 | TINYINT | ワープ魔法のアンロック状態 |
| mv_time | INT | ハートビート (オンライン判定) |

### monsters (モンスター)

フロアごとに動的に生成・管理。位置 (x, y, floor)、HP%、タイムスタンプを保持。

### specials (特殊タイル)

ポータルや階段の転送先情報を格納。

## ゲームシステム

### マップ生成

- 1フロア = 64x64 タイルグリッド
- シード値 `(floor * 4) + section` による疑似ランダム生成
- 壁 5%、モンスター 2% の確率で配置
- 初回アクセス時に生成し `public/levels/` に PHP serialize() 形式でキャッシュ

### タイルタイプ (`src/Tiles.php`)

| 定数 | 値 | 意味 |
|------|---|------|
| EMPTY | 0 | 空地 (移動可) |
| WALL | 1 | 壁 |
| MONSTER | 2 | モンスター |
| DEAD_MONSTER | 3 | 倒されたモンスター |
| STAIR_UP / STAIR_DOWN | 4 / 5 | 上り階段 / 下り階段 |
| INVISIBLE_WALL | 6 | 不可視壁 |
| INVISIBLE | 7 | 不可視 |
| WATER | 8 | 水 |
| RAISE | 9 | HP/MP回復ポイント |
| ADD_WALL_START | 20+ | 壁バリアント |

### 戦闘

- 隣接モンスターをクリックで攻撃
- モンスターHP = `floor(フロア + ((x+y)/64) * (フロア+1))`
- プレイヤー攻撃力 = `floor(レベル * 1.8) - floor(フロア / 3)`
- 被ダメージ = `floor(フロア / 2)`
- 撃破時にXPを獲得、レベルアップで攻撃力が上昇

### マルチプレイヤー

- 同一フロアのプレイヤーをリアルタイム表示
- 隣接プレイヤーへの回復魔法 (MP消費)
- 5秒間表示されるチャットメッセージ
- `mv_time` による10秒以内のオンライン判定

### レベリング

- レベル = `floor(cbrt(xp))` (経験値の立方根)
- ビルド値 = `round((kill - save + 26) / 4)` (0-13、外見に反映)

## 実装思想

1. **シンプルさ優先**: WebSocket不使用、AJAXポーリングのみでリアルタイム性を確保。帯域最小化のためJSONペイロードは軽量に保つ
2. **クロスプラットフォーム**: モバイル・デスクトップ対応。プラグイン不要のHTML5ベース
3. **協力的マルチプレイ**: 回復魔法をプレイヤー間インタラクションの中心に据え、PvPではなく協力を促進
4. **手続き的生成 + 永続化**: シード値による再現可能なマップ生成と、ファイルキャッシュによる高速ロードの両立
5. **ピクセルアート美学**: カスタムフォント "Marvelous3x3"、統一されたスプライトシート、フロア連動BGMによる一貫した世界観
6. **段階的難易度**: フロアが深くなるほどモンスターのHP・攻撃力が上昇し、レベルとの差分で獲得XPを調整

## リファクタリング (v2)

旧コード (`old/`) からの主な改善点:

| 項目 | 旧 (old/) | 新 |
|------|----------|-----|
| DB接続 | mysqli + magic_quotes対応 | PDO + プリペアドステートメント |
| パスワード | 平文保存 | bcrypt (password_hash/verify) |
| ルーティング | ファイルベース (10+ PHPファイル) | フロントコントローラ (index.php) |
| テンプレート | ロジック混在 | src/ と templates/ に分離 |
| JavaScript | MooTools 1.2.4 + jQuery 1.8.1 | Vanilla JS (Fetch API) |
| SQL | 文字列結合クエリ | プリペアドステートメント |
| テーブル名 | xiv_merveilles* | players / monsters / specials |
| 型定義 | x,y が VARCHAR | 全座標が INT |
