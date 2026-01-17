<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Docker環境セットアップ

### 必要条件

- Docker
- Docker Compose

### 新規セットアップ

```bash
# 1. 環境変数ファイルをコピー
cp .env.example .env

# 2. .envファイルを編集し、必要な値を設定
# - DB_PASSWORD
# - MYSQL_ROOT_PASSWORD
# - PROXYSQL_ADMIN_PASSWORD
# - PROXYSQL_MONITOR_PASSWORD
# - STRIPE_SECRET, STRIPE_PUBLIC, STRIPE_WEBHOOK_SECRET

# 3. Dockerコンテナをビルド・起動
docker-compose up -d --build

# 4. マイグレーション実行（ProxySQLをバイパスして直接接続）
docker-compose exec app php artisan migrate --database=mysql_direct

# 5. シーダー実行（必要に応じて）
docker-compose exec app php artisan db:seed --database=mysql_direct
```

### 既存環境へのProxySQL追加

既存のMySQLボリュームがある環境では、initスクリプトが自動実行されません。
以下のコマンドでProxySQL監視ユーザーを手動で作成してください。

```bash
# ProxySQL監視ユーザーを手動作成（Dockerネットワーク内のみ接続可能）
docker-compose exec mysql mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "
  CREATE USER IF NOT EXISTS 'monitor'@'laravel_proxysql.laravel' IDENTIFIED BY '${PROXYSQL_MONITOR_PASSWORD}';
  GRANT USAGE, REPLICATION CLIENT ON *.* TO 'monitor'@'laravel_proxysql.laravel';
  CREATE USER IF NOT EXISTS 'monitor'@'172.%.%.%' IDENTIFIED BY '${PROXYSQL_MONITOR_PASSWORD}';
  GRANT USAGE, REPLICATION CLIENT ON *.* TO 'monitor'@'172.%.%.%';
  FLUSH PRIVILEGES;
"

# または環境変数を直接指定
docker-compose exec mysql mysql -u root -proot -e "
  CREATE USER IF NOT EXISTS 'monitor'@'laravel_proxysql.laravel' IDENTIFIED BY 'monitor_secure_password';
  GRANT USAGE, REPLICATION CLIENT ON *.* TO 'monitor'@'laravel_proxysql.laravel';
  CREATE USER IF NOT EXISTS 'monitor'@'172.%.%.%' IDENTIFIED BY 'monitor_secure_password';
  GRANT USAGE, REPLICATION CLIENT ON *.* TO 'monitor'@'172.%.%.%';
  FLUSH PRIVILEGES;
"
```

### サービス一覧

| サービス | ポート | 説明 |
|---------|-------|------|
| nginx | 8080 | Webサーバー |
| mysql | 3306 | データベース（直接接続） |
| proxysql | 6033 | データベースプロキシ（アプリ用） |
| redis | 6379 | キャッシュ/セッション/キュー |
| phpmyadmin | 8888 | データベース管理UI |

> **Note**: ProxySQL管理インターフェース（6032）はセキュリティのためホストに公開していません。
> アクセスには `docker-compose exec` を使用してください。

### ProxySQL管理

```bash
# ProxySQL管理コンソールに接続
docker-compose exec proxysql mysql -h 127.0.0.1 -P 6032 -u admin -p"${PROXYSQL_ADMIN_PASSWORD}"

# Query Cache統計を確認
SELECT * FROM stats_mysql_global WHERE Variable_Name LIKE 'Query_Cache%';

# 接続プール状況を確認
SELECT * FROM stats_mysql_connection_pool;
```

### トラブルシューティング

#### Redis接続エラー（Class "Redis" not found）
Dockerイメージを再ビルドしてください：
```bash
docker-compose build --no-cache app queue
docker-compose up -d
```

#### ProxySQL接続エラー
監視ユーザーが作成されているか確認してください（上記「既存環境へのProxySQL追加」参照）。

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
