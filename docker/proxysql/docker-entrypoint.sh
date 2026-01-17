#!/bin/sh
set -e

# 環境変数をテンプレートに適用して設定ファイルを生成
envsubst < /etc/proxysql.cnf.template > /etc/proxysql.cnf

# ProxySQLを起動
exec proxysql -f -c /etc/proxysql.cnf
