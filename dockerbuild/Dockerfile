# 公式イメージに Xdebug だけを追加する薄いレイヤー。
# ベースイメージ（PHPバージョン等）は ghcr.io/ec-cube/ec-cube-php にそのまま追従する。
FROM ghcr.io/ec-cube/ec-cube-php:8.1-apache

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY dockerbuild/xdebug.ini /usr/local/etc/php/conf.d/zzz-xdebug.ini
