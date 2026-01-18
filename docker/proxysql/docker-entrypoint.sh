#!/bin/sh
set -e

# 環境変数をテンプレートに適用して設定ファイルを生成
envsubst < /etc/proxysql.cnf.template > /etc/proxysql.cnf

# ProxySQLを起動
# 注意: 設定変更を反映するには proxysql_data ボリュームを削除して再起動
# docker volume rm la12react-ec_proxysql_data
exec proxysql -f -c /etc/proxysql.cnf
