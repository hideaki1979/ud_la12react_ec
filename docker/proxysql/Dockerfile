FROM proxysql/proxysql:3.0.5

# envsubst（テンプレート処理）とmysql-client（ヘルスチェック）をインストール
RUN apt-get update && apt-get install -y gettext-base default-mysql-client && rm -rf /var/lib/apt/lists/*

# テンプレートとエントリポイントをコピー
COPY proxysql.cnf.template /etc/proxysql.cnf.template
COPY docker-entrypoint.sh /docker-entrypoint.sh

RUN chmod +x /docker-entrypoint.sh

ENTRYPOINT ["/docker-entrypoint.sh"]
