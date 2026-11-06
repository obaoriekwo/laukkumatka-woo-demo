#!/usr/bin/env bash
# One-shot setup for the demo store. Run inside the WP-CLI container:
#   docker compose run --rm cli bash /project/bin/setup.sh
# Or on any server with WP-CLI, from the WordPress root:
#   bash /path/to/project/bin/setup.sh /path/to/project
set -euo pipefail

PROJECT="${1:-/project}"
URL="${WP_URL:-http://localhost:8080}"

echo "⏳ Waiting for WordPress files…"
for _ in $(seq 1 30); do [ -f wp-includes/version.php ] && break; sleep 2; done

if ! wp core is-installed 2>/dev/null; then
  wp core install --url="$URL" --title="Laukkumatka DEMO" \
    --admin_user=admin --admin_password=admin --admin_email=admin@example.com --skip-email
fi

echo "🇫🇮 Finnish language"
wp language core install fi --activate || echo "  (language download failed – continuing in English)"

echo "🛒 WooCommerce + Storefront"
wp plugin install woocommerce --activate
wp language plugin install woocommerce fi || true
wp theme install storefront
wp language theme install storefront fi || true

echo "🧳 Our theme and plugin"
wp theme activate laukku-storefront-child
wp plugin activate laukku-travel-tools

# Make sure WooCommerce pages (shop, cart, checkout, my account) exist.
wp wc tool run install_pages --user=admin >/dev/null 2>&1 || true

echo "📦 Store settings, shipping, categories, products, pages"
wp eval-file "$PROJECT/bin/setup-store.php" "$PROJECT/sample-data"

wp rewrite flush
wp cache flush >/dev/null 2>&1 || true

echo
echo "✅ Done → $URL  (admin: $URL/wp-admin  user: admin  pass: admin)"
echo "   Try:  docker compose run --rm cli wp laukku audit"
