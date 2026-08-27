FROM alpine:3.20

RUN apk add --no-cache \
    postgresql16-client \
    mariadb-client \
    bash \
    gzip

COPY backup.sh /backup.sh
COPY crontab /etc/crontabs/root

RUN chmod +x /backup.sh && \
    mkdir -p /backups/tcg /backups/infisical

CMD ["crond", "-f", "-l", "2", "-c", "/etc/crontabs"]
