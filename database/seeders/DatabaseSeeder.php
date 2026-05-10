<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MainDirectionsSeeder::class,
            CoreServicesSeeder::class,
            RoleSeeder::class,
            OpsControlPlanePermissionsSeeder::class,
            OpsControlPlaneRoleAssignmentsSeeder::class,
            OrganizationSeeder::class,
            ServiceCategorySeeder::class,
            ServiceTypeSeeder::class,
            PricingRuleSeeder::class,
            GeoZoneSeeder::class,
            RestaurantSeeder::class,
            RetailStoreSeeder::class,
            StoreCatalogSeeder::class,
            PartnerSeeder::class,
            EmployeeSeeder::class,
            SubscriptionSeeder::class,
            CouponSeeder::class,
            OrderSeeder::class,
            ClaimSeeder::class,
            AssistantConversationSeeder::class,
            RealWorldCatalogSeeder::class,
            BikubeDemoSeeder::class,
            RepairProjectSeeder::class,
            RepairStageSeeder::class,
            HandymanTeamSeeder::class,
            HandymanMaterialsSeeder::class,
            HandymanAssignmentsSeeder::class,
            MovingTeamsSeeder::class,
            MovingOrdersSeeder::class,
            MovingOrderItemsSeeder::class,
            MovingOrderPhotosSeeder::class,
            ClassifiedsSeeder::class,
        ]);

        if (app()->environment(['local', 'development', 'testing'])) {
            $this->call([
                RetailStoreNarvikSeeder::class,
                RestaurantNarvikSeeder::class,
                ProductsNarvikSeeder::class,
                PricingRulesNarvikSeeder::class,
                CouriersNarvikSeeder::class,
                DeliveryOrdersNarvikSeeder::class,
                ErrandCategoriesNarvikSeeder::class,
                ErrandWorkersNarvikSeeder::class,
                ErrandPricingRulesSeeder::class,
                ErrandTasksNarvikSeeder::class,
                ErrandTeamsSeeder::class,
                GeoZonesNarvikSeeder::class,
                NarvikRegionSeeder::class,
                NarvikGeoZonesMissingSeeder::class,
                NarvikServiceCategoriesSeeder::class,
                NarvikServiceTypesSeeder::class,
                NarvikPartnersSeeder::class,
                NarvikWorkersSeeder::class,
                NarvikPricingSeeder::class,
                DemoDeliveryOrdersSeeder::class,
                DemoScheduleSlotsSeeder::class,
                EcoProvidersNarvikSeeder::class,
                EcoVehiclesNarvikSeeder::class,
                EcoPricingRulesNarvikSeeder::class,
                EcoTeamsNarvikSeeder::class,
                EcoOrdersNarvikSeeder::class,
                RoadsidePartnersNarvikSeeder::class,
                RoadsideVehiclesNarvikSeeder::class,
                RoadsideHelpersNarvikSeeder::class,
                RoadsideJobTypesSeeder::class,
                RoadsideOrdersNarvikSeeder::class,
                RoadsideInspectionPresetsSeeder::class,
                SocialCareServiceTypesSeeder::class,
                SocialCarePlansNarvikSeeder::class,
                SocialCareHelpersNarvikSeeder::class,
                SocialCareTeamsSeeder::class,
                SocialCareOrdersNarvikSeeder::class,
                SocialCommunityPointsSeeder::class,
                BikubeDemoOrdersSeeder::class,
            ]);
        }
    }
}
