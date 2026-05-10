# Logistics Topic Scan (GitHub `logistics`)

## Role
- TopicScanner
- Date: 2026-03-31
- Scope: analyze the GitHub `logistics` topic landscape, classify OSS trends, and deliver a feature comparison matrix for Bikube logistics planning.

## Inputs
- Source: https://github.com/topics/logistics
- Source: GitHub REST API search snapshot (`q=topic:logistics`, sorted by stars).
- Dataset window: top 200 repositories by stars (captured 2026-03-31).
- Representative matrix sample (11 repos):
  - `fleetbase/fleetbase`
  - `ever-co/ever-demand`
  - `VROOM-Project/vroom`
  - `openwms/org.openwms`
  - `microsoft/maro`
  - `wms2/mywms`
  - `googlemaps/js-route-optimization-app`
  - `IATA-Cargo/ONE-Record`
  - `microsoft/Bing-Maps-Fleet-Tracker`
  - `samirsaci/picking-route`
  - `KevinFasusi/supplychainpy`

## Findings
### 1) Market Shape: high long-tail, concentrated influence
- Topic size reported by GitHub search: ~1,288 repositories.
- In top-200 snapshot:
  - Median stars: 9
  - 75th percentile: 21
  - 90th percentile: 114
  - Top-10 repos hold ~67.3% of all stars in this top-200 slice.
  - Top-20 repos hold ~80.7% of all stars in this top-200 slice.
- Interpretation: ecosystem discovery is broad, but practical reference implementations are concentrated in a small leader set.

### 2) Maintenance signal is healthy
- Updated in last 90 days: 100/200.
- Updated in last 365 days: 150/200.
- Archived in top-200: 7 repos.
- Interpretation: topic is active enough for reuse and benchmarking; still requires repo-by-repo quality filtering.

### 3) Trend clusters (multi-label classification across top-200)
- `SupplyChainPlanning`: 56 repos
- `GISMapping`: 49 repos
- `RoutingOptimization`: 46 repos
- `FleetTrackingDispatch`: 39 repos
- `WarehouseInventoryWMS`: 29 repos
- `SimulationDigitalTwin`: 12 repos
- Interpretation: strongest open-source gravity is around planning + optimization + map-enabled operations; full end-to-end logistics suites are fewer.

### 4) Stack and governance patterns
- Top languages in top-200: Python (36), JavaScript (30), Jupyter Notebook (28), C# (13), PHP (12), TypeScript (10), Java (10), C++ (7).
- License metadata quality varies:
  - `NONE`: 78 repos
  - `MIT`: 56
  - `Apache-2.0`: 21
  - `GPL-3.0`: 12
  - `AGPL-3.0`: 9
- Interpretation: many projects are analysis/prototype oriented; licensing diligence is required before production reuse.

## Feature Comparison Matrix
Legend: `Yes` = clear first-class capability, `Partial` = present but not core/deep, `No` = not a stated focus.

| Project | Stars | Primary Type | Fleet/Dispatch | Route Optimization | Warehouse/Inventory | Simulation/Analytics | API/Extensibility | License | Activity Signal |
|---|---:|---|---|---|---|---|---|---|---|
| [fleetbase/fleetbase](https://github.com/fleetbase/fleetbase) | 1797 | Modular logistics platform (LSOS) | Yes | Yes | Yes | Partial | Yes | AGPL-3.0 | Updated 2026-03-31 |
| [ever-co/ever-demand](https://github.com/ever-co/ever-demand) | 1834 | Commerce + logistics platform | Yes | Partial | Yes | Partial | Yes | AGPL-3.0 | Updated 2026-03-29 |
| [VROOM-Project/vroom](https://github.com/VROOM-Project/vroom) | 1695 | VRP optimization engine | No | Yes | No | No | Partial | BSD-2-Clause | Updated 2026-03-29 |
| [openwms/org.openwms](https://github.com/openwms/org.openwms) | 671 | WMS/MFC microservice suite | Partial | No | Yes | No | Yes | Apache-2.0 | Updated 2026-03-25 |
| [microsoft/maro](https://github.com/microsoft/maro) | 909 | RL optimization/simulation platform | Partial | Partial | Partial | Yes | Yes | MIT | Updated 2026-03-17 |
| [wms2/mywms](https://github.com/wms2/mywms) | 124 | Warehouse management system | No | No | Yes | No | Partial | NOASSERTION | Updated 2026-03-22 |
| [googlemaps/js-route-optimization-app](https://github.com/googlemaps/js-route-optimization-app) | 136 | Route optimization reference app | Partial | Yes | No | No | Yes | NOASSERTION | Updated 2026-03-26 |
| [IATA-Cargo/ONE-Record](https://github.com/IATA-Cargo/ONE-Record) | 128 | Data/API interoperability standard | No | No | No | No | Yes | MIT | Updated 2026-03-26 |
| [microsoft/Bing-Maps-Fleet-Tracker](https://github.com/microsoft/Bing-Maps-Fleet-Tracker) | 228 | Fleet tracking solution sample | Yes | Partial | No | No | Partial | MIT | Updated 2026-02-13 (deprecated) |
| [samirsaci/picking-route](https://github.com/samirsaci/picking-route) | 135 | Warehouse picking optimization model | No | Yes | Partial | Yes | No | MIT | Updated 2026-03-06 |
| [KevinFasusi/supplychainpy](https://github.com/KevinFasusi/supplychainpy) | 316 | Supply chain analytics library | No | No | Partial | Yes | Partial | BSD-3-Clause | Updated 2026-03-30 |

## Decisions
- Bikube should target a hybrid architecture: operational platform core plus pluggable optimization engines.
- Prioritize three capability pillars for MVP+: dispatch/fleet orchestration, routing optimization, and warehouse/inventory workflows.
- Add explicit interoperability layer (ONE Record-style API contracts and ontology-compatible mapping) early to reduce partner integration cost.
- Enforce strict dependency policy on license compatibility (AGPL/GPL components isolated or reimplemented where needed).

## Risks
- GitHub topic tags are noisy and self-reported; some repos are only loosely logistics-related.
- Star counts bias toward older/publicized projects, not necessarily best technical fit.
- `NOASSERTION`/missing license in many repos raises legal uncertainty for direct reuse.
- Some high-visibility samples are deprecated or provider-specific (e.g., map vendor lock-in risk).

## Handoff
- **DatabaseArchitect**: model entities to support dispatch + routing + warehouse in one schema (orders, shipments, stops, vehicles, hubs, inventory states).
- **APIArchitect**: define external/internal contracts with versioning, webhook events, and extensibility for carrier adapters.
- **MapArchitect**: design provider-agnostic routing/map abstraction to avoid single-vendor dependency.
- **IntegrationArchitect**: specify connector strategy for standards and ecosystems (including ONE Record alignment where relevant).

## Notes
- Snapshot timestamp: 2026-03-31 UTC.
- Raw analysis artifacts retained temporarily under `docs/logistics/audit/.tmp-logistics-*.json`.
