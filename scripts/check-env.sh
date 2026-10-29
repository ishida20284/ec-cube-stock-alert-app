#!/usr/bin/env bash
set -euo pipefail

PASS="[PASS]"
FAIL="[FAIL]"
WARN="[WARN]"
failed=0

check() {
  local label="$1"
  local result="$2"
  local detail="${3:-}"
  if [ "$result" = "ok" ]; then
    echo "$PASS $label${detail:+: $detail}"
  elif [ "$result" = "warn" ]; then
    echo "$WARN $label${detail:+: $detail}"
  else
    echo "$FAIL $label${detail:+: $detail}"
    failed=$((failed + 1))
  fi
}

echo "=== 環境チェック ==="
echo ""

# git
if command -v git &>/dev/null; then
  check "git" "ok" "$(git --version)"
else
  check "git" "fail" "git コマンドが見つかりません"
fi

# Docker
if command -v docker &>/dev/null; then
  if docker info &>/dev/null; then
    check "Docker" "ok" "$(docker --version)"
  else
    check "Docker" "fail" "Docker デーモンが起動していません (Docker Desktop を起動してください)"
  fi
else
  check "Docker" "fail" "docker コマンドが見つかりません"
fi

# docker compose
if docker compose version &>/dev/null 2>&1; then
  check "Docker Compose" "ok" "$(docker compose version)"
else
  check "Docker Compose" "fail" "docker compose が使えません"
fi

# .env
if [ -f ".env" ]; then
  check ".env" "ok" "存在します"

  if grep -q '^ECCUBE_AUTH_MAGIC=<change.me>' .env 2>/dev/null || ! grep -q '^ECCUBE_AUTH_MAGIC=' .env 2>/dev/null; then
    check "ECCUBE_AUTH_MAGIC" "fail" "未設定のままです。README.md の手順に従って openssl rand -hex 32 等で生成してください"
  else
    check "ECCUBE_AUTH_MAGIC" "ok" "設定済み"
  fi

  if grep -q '^UID=' .env 2>/dev/null && grep -q '^GID=' .env 2>/dev/null; then
    check "UID/GID" "ok" "設定済み"
  else
    check "UID/GID" "warn" "未設定です。README.md の手順に従って .env に UID/GID を追記してください（初回起動時にファイルの所有者が壊れる原因になります）"
  fi
else
  check ".env" "fail" ".env が見つかりません → cp .env.example .env"
fi

# ポート競合確認
if command -v ss &>/dev/null; then
  for port in 8080 1080 1025; do
    if ss -ltn 2>/dev/null | grep -q ":${port} "; then
      check "ポート ${port}" "warn" "すでに使用中です。他のアプリが使っていないか確認してください"
    else
      check "ポート ${port}" "ok" "空き"
    fi
  done
fi

# コンテナの起動状態
if docker compose ps ec-cube --format '{{.Status}}' 2>/dev/null | grep -qi "healthy"; then
  check "ec-cube コンテナ" "ok" "起動済み・healthy"
elif docker compose ps ec-cube --format '{{.Status}}' 2>/dev/null | grep -qi "up"; then
  check "ec-cube コンテナ" "warn" "起動していますが healthy ではありません。docker compose logs ec-cube を確認してください"
else
  check "ec-cube コンテナ" "warn" "起動していません → docker compose up -d"
fi

echo ""
if [ "$failed" -eq 0 ]; then
  echo "✅ チェック完了 — 問題は見つかりませんでした"
else
  echo "❌ チェック完了 — $failed 件の問題があります"
fi

exit "$failed"
