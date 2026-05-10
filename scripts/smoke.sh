#!/usr/bin/env bash
set -euo pipefail

# Smoke test script for GLF BiKube
# Проверяет готовность системы к продакшену

echo "🔍 GLF BiKube Smoke Test"
echo "========================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

ERROR_COUNT=0

# Function to check command
check() {
    if $@ >/dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} $1"
        return 0
    else
        echo -e "${RED}✗${NC} $1"
        ((ERROR_COUNT++))
        return 1
    fi
}

# Function to check with message
check_msg() {
    local msg=$1
    shift
    if $@ >/dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} $msg"
        return 0
    else
        echo -e "${RED}✗${NC} $msg"
        ((ERROR_COUNT++))
        return 1
    fi
}

echo "📋 Laravel & PHP"
check php artisan about | grep -qi "Laravel"
check php artisan about | grep -qi "PHP"

echo ""
echo "💾 Cache & Configuration"
check_msg "Config cache" php artisan config:cache
check_msg "Route cache" php artisan route:cache
check_msg "View cache" php artisan view:cache

echo ""
echo "🗄️  Database"
check_msg "Migrations" php artisan migrate --force
check_msg "Database connection" php artisan tinker --execute="DB::connection()->getPdo();"

echo ""
echo "📊 Data Check"
TASK_COUNT=$(php artisan tinker --execute="echo \App\Models\Task::count();" 2>/dev/null | tail -1)
echo "  Tasks in database: $TASK_COUNT"

echo ""
echo "🌐 API Health"
if curl -fsS http://localhost:2244/api/v1/health >/dev/null 2>&1; then
    HEALTH_RESPONSE=$(curl -fsS http://localhost:2244/api/v1/health)
    if echo "$HEALTH_RESPONSE" | jq -e '.status=="ok"' >/dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} Health endpoint returns OK"
    else
        echo -e "${YELLOW}⚠${NC} Health endpoint exists but status != ok"
    fi
else
    echo -e "${YELLOW}⚠${NC} Health endpoint not reachable (may be normal if server not running)"
fi

echo ""
echo "📚 Public Catalog"
if curl -fsS http://localhost:2244/catalog >/dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} /catalog endpoint accessible"
else
    echo -e "${YELLOW}⚠${NC} /catalog endpoint not reachable"
fi

echo ""
echo "⚙️  Queue"
if php artisan queue:restart >/dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Queue can be restarted"
else
    echo -e "${YELLOW}⚠${NC} Queue restart command"
fi

if command -v horizon:status >/dev/null 2>&1; then
    if php artisan horizon:status >/dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} Horizon status check"
    else
        echo -e "${YELLOW}⚠${NC} Horizon not running"
    fi
fi

echo ""
echo "📦 Dependencies"
check_msg "Composer autoload" composer dump-autoload --no-interaction --quiet

echo ""
echo "🔐 Environment"
if [ -f .env ]; then
    echo -e "${GREEN}✓${NC} .env file exists"
    if grep -q "APP_KEY=" .env && ! grep -q "APP_KEY=$" .env; then
        echo -e "${GREEN}✓${NC} APP_KEY is set"
    else
        echo -e "${RED}✗${NC} APP_KEY is not set"
        ((ERROR_COUNT++))
    fi
else
    echo -e "${RED}✗${NC} .env file missing"
    ((ERROR_COUNT++))
fi

echo ""
echo "📁 Permissions"
if [ -w storage/logs ]; then
    echo -e "${GREEN}✓${NC} storage/logs is writable"
else
    echo -e "${RED}✗${NC} storage/logs is not writable"
    ((ERROR_COUNT++))
fi

if [ -w storage/framework/cache ]; then
    echo -e "${GREEN}✓${NC} storage/framework/cache is writable"
else
    echo -e "${RED}✗${NC} storage/framework/cache is not writable"
    ((ERROR_COUNT++))
fi

echo ""
echo "================================"
if [ $ERROR_COUNT -eq 0 ]; then
    echo -e "${GREEN}✅ All checks passed!${NC}"
    exit 0
else
    echo -e "${RED}❌ $ERROR_COUNT check(s) failed${NC}"
    exit 1
fi

