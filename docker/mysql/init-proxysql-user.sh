#!/bin/bash
set -e

# ProxySQL監視用ユーザーを作成（ProxySQLコンテナからのみ接続可能）
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    CREATE USER IF NOT EXISTS '${PROXYSQL_MONITOR_USER:-monitor}'@'laravel_proxysql.laravel' IDENTIFIED BY '${PROXYSQL_MONITOR_PASSWORD:-monitor}';
    GRANT USAGE, REPLICATION CLIENT ON *.* TO '${PROXYSQL_MONITOR_USER:-monitor}'@'laravel_proxysql.laravel';
    -- Docker内部ネットワークからの接続も許可（サービス名解決用）
    CREATE USER IF NOT EXISTS '${PROXYSQL_MONITOR_USER:-monitor}'@'172.%.%.%' IDENTIFIED BY '${PROXYSQL_MONITOR_PASSWORD:-monitor}';
    GRANT USAGE, REPLICATION CLIENT ON *.* TO '${PROXYSQL_MONITOR_USER:-monitor}'@'172.%.%.%';
    FLUSH PRIVILEGES;
EOSQL
