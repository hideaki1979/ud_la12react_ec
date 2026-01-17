#!/bin/bash
set -e

# 必須環境変数のチェック
if [ -z "${MYSQL_ROOT_PASSWORD}" ]; then
    echo "ERROR: MYSQL_ROOT_PASSWORD must be set" >&2
    exit 1
fi

if [ -z "${PROXYSQL_MONITOR_PASSWORD}" ]; then
    echo "ERROR: PROXYSQL_MONITOR_PASSWORD must be set" >&2
    exit 1
fi

MONITOR_USER="${PROXYSQL_MONITOR_USER:-monitor}"

# ProxySQL監視用ユーザーを作成（ProxySQLコンテナからのみ接続可能）
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    CREATE USER IF NOT EXISTS '${MONITOR_USER}'@'laravel_proxysql.laravel' IDENTIFIED BY '${PROXYSQL_MONITOR_PASSWORD}';
    GRANT USAGE, REPLICATION CLIENT ON *.* TO '${MONITOR_USER}'@'laravel_proxysql.laravel';
    -- Docker内部ネットワークからの接続も許可（サービス名解決用）
    CREATE USER IF NOT EXISTS '${MONITOR_USER}'@'172.%.%.%' IDENTIFIED BY '${PROXYSQL_MONITOR_PASSWORD}';
    GRANT USAGE, REPLICATION CLIENT ON *.* TO '${MONITOR_USER}'@'172.%.%.%';
    FLUSH PRIVILEGES;
EOSQL

echo "ProxySQL monitor user '${MONITOR_USER}' created successfully"
