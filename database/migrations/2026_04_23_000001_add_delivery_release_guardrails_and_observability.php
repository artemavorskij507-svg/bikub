<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION enforce_delivery_release_guardrails()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    IF NEW.tracking_status IN ('assigned', 'picked_up', 'in_transit')
       AND NEW.courier_id IS NULL THEN
        RAISE EXCEPTION
            'Delivery guardrail: courier is required for status %',
            NEW.tracking_status
            USING ERRCODE = '23514';
    END IF;

    IF NEW.tracking_status IN ('assigned', 'picked_up', 'in_transit')
       AND (NEW.pickup_location IS NULL OR NEW.delivery_location IS NULL) THEN
        RAISE EXCEPTION
            'Delivery guardrail: pickup and delivery coordinates are required for status %',
            NEW.tracking_status
            USING ERRCODE = '23514';
    END IF;

    RETURN NEW;
END;
$$;
SQL);

        DB::statement(<<<'SQL'
DROP TRIGGER IF EXISTS delivery_release_guardrails_trigger ON delivery_orders;
CREATE TRIGGER delivery_release_guardrails_trigger
BEFORE INSERT OR UPDATE ON delivery_orders
FOR EACH ROW
EXECUTE FUNCTION enforce_delivery_release_guardrails();
SQL);

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW delivery_release_observability AS
SELECT
    now() AS observed_at,
    zones.active_delivery_zones,
    orders.active_delivery_orders,
    orders.active_orders_missing_courier,
    orders.active_orders_missing_eta,
    orders.active_orders_missing_route,
    CASE
        WHEN zones.active_delivery_zones = 0
          OR orders.active_orders_missing_courier > 0
          OR orders.active_orders_missing_route > 0
        THEN 'fail'
        WHEN orders.active_orders_missing_eta > 0
        THEN 'warn'
        ELSE 'pass'
    END AS status
FROM (
    SELECT count(*)::integer AS active_delivery_zones
    FROM delivery_zones
    WHERE is_active = true
) AS zones
CROSS JOIN (
    SELECT
        count(*) FILTER (
            WHERE tracking_status IN ('pending', 'assigned', 'picked_up', 'in_transit')
        )::integer AS active_delivery_orders,
        count(*) FILTER (
            WHERE tracking_status IN ('assigned', 'picked_up', 'in_transit')
              AND courier_id IS NULL
        )::integer AS active_orders_missing_courier,
        count(*) FILTER (
            WHERE tracking_status IN ('pending', 'assigned', 'picked_up', 'in_transit')
              AND eta IS NULL
        )::integer AS active_orders_missing_eta,
        count(*) FILTER (
            WHERE tracking_status IN ('pending', 'assigned', 'picked_up', 'in_transit')
              AND (pickup_location IS NULL OR delivery_location IS NULL)
        )::integer AS active_orders_missing_route
    FROM delivery_orders
    WHERE deleted_at IS NULL
) AS orders;
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS delivery_release_observability');
        DB::statement('DROP TRIGGER IF EXISTS delivery_release_guardrails_trigger ON delivery_orders');
        DB::statement('DROP FUNCTION IF EXISTS enforce_delivery_release_guardrails()');
    }
};
