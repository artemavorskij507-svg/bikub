# Agency Agents - Virtual 2D Office System

## Overview

The Agency Agents system is a multi-agent platform that initializes and manages 100+ AI agents in a virtual 2D office environment. Each agent is based on the roles defined in the `agency-agents` repository and operates within a pixel-art isometric office space.

## Features

### 🏢 Virtual 2D Office
- **Isometric Map**: 800x600 pixel office space with multiple zones
- **Pixel Avatars**: Each agent has a unique pixel-art avatar based on their category
- **Real-time Movement**: Agents move between zones based on their tasks
- **Status Indicators**: Visual indicators for agent status (active, busy, idle, offline)
- **Activity Icons**: Icons showing current activity (working, meeting, break, lunch, brainstorm)

### 📍 Office Zones

| Zone | Icon | Capacity | Description |
|------|------|----------|-------------|
| Workspace | 💼 | 50 | Main work area with desks and monitors |
| Meeting Room | 🤝 | 12 | Conference room for discussions |
| Brainstorm | 💡 | 15 | Creative zone with whiteboards |
| Break Room | 🛋️ | 20 | Relaxation area with sofas and plants |
| Cafeteria | 🍽️ | 30 | Dining area with tables and vending machines |
| Lounge | ☕ | 15 | Quiet area with coffee machine |

### 🤖 Agent Categories

- **Academic** 🎓 - Research and education specialists
- **Design** 🎨 - UI/UX and visual design
- **Engineering** ⚙️ - Software development
- **Game Development** 🎮 - Game design and development
- **Marketing** 📢 - Marketing and growth
- **Paid Media** 💰 - Advertising specialists
- **Product** 📦 - Product management
- **Project Management** 📋 - Project coordination
- **Sales** 💼 - Sales and business development
- **Specialized** 🔧 - Domain experts
- **Strategy** 🎯 - Strategic planning
- **Spatial Computing** 🖥️ - AR/VR technology

## Installation

### 1. Run Migrations

```bash
cd bikube
php artisan migrate
```

### 2. Initialize Agents

```bash
# Initialize all agents and zones
php artisan agency:initialize

# Initialize only zones
php artisan agency:initialize --zones

# Initialize specific category
php artisan agency:initialize --category=engineering

# Force reinitialize all agents
php artisan agency:initialize --force
```

### 3. Access the Office

Navigate to: `http://127.0.0.1:2244/admin/virtual-2d-office`

## Usage

### Admin Panel

#### Virtual 2D Office Page
- **Map View**: Interactive isometric office map
- **Agent List**: List view of all agents
- **Filters**: Filter by category, status, or zone
- **Heatmap**: Visual representation of agent density
- **Minimap**: Quick navigation overview

#### Dashboard Widget
- System overview statistics
- Zone occupancy
- Top performers
- Recent activity

### Console Commands

#### Initialize System
```bash
php artisan agency:initialize
```

#### Monitor System
```bash
# Show overview
php artisan agency:monitor

# Show zone statistics
php artisan agency:monitor --zones

# Show recent activities
php artisan agency:monitor --activities

# Show top performers
php artisan agency:monitor --top

# Check system health
php artisan agency:monitor --health

# Generate report
php artisan agency:monitor --report

# Monitor specific agent
php artisan agency:monitor --agent=1
```

### API Endpoints

#### System
- `GET /api/agency-agents/overview` - System overview
- `GET /api/agency-agents/health` - Health check
- `GET /api/agency-agents/report` - Generate report
- `GET /api/agency-agents/categories/stats` - Category statistics
- `GET /api/agency-agents/top-performers` - Top performers
- `GET /api/agency-agents/recent-activities` - Recent activities
- `GET /api/agency-agents/heatmap` - Heatmap data

#### Zones
- `GET /api/agency-agents/zones` - List all zones
- `GET /api/agency-agents/zones/{name}` - Zone details
- `GET /api/agency-agents/zones/stats` - Zone statistics

#### Agents
- `GET /api/agency-agents/agents` - List agents (filterable)
- `GET /api/agency-agents/agents/{id}` - Agent details
- `PUT /api/agency-agents/agents/{id}/position` - Update position
- `PUT /api/agency-agents/agents/{id}/status` - Update status
- `POST /api/agency-agents/agents/{id}/move` - Move to zone

#### Tasks
- `GET /api/agency-agents/agents/{id}/tasks` - Agent tasks
- `POST /api/agency-agents/agents/{id}/tasks` - Create task

#### Communications
- `GET /api/agency-agents/agents/{id}/communications` - Agent messages
- `POST /api/agency-agents/agents/{id}/messages` - Send message

#### Metrics
- `GET /api/agency-agents/agents/{id}/performance` - Performance metrics
- `GET /api/agency-agents/agents/{id}/metrics` - Agent metrics
- `GET /api/agency-agents/agents/{id}/activities` - Agent activities

## Agent Behavior

### Movement Logic
Agents automatically move between zones based on:
- **Task Type**: Development → Workspace, Discussion → Meeting Room
- **Status**: Active → Workspace, Break → Break Room/Cafeteria
- **Collaboration**: Agents gather in meeting rooms for discussions

### Status States
- **Active** 🟢 - Currently working
- **Busy** 🟡 - In a meeting or focused task
- **Idle** ⚪ - Available but not working
- **Offline** ⚫ - Not available

### Activities
- **Working** 💼 - At desk doing tasks
- **Meeting** 🤝 - In meeting room
- **Break** ☕ - Taking a break
- **Lunch** 🍽️ - Having lunch
- **Brainstorm** 💡 - Creative session

## Configuration

Edit `config/agency-agents.php`:

```php
'office_2d' => [
    'enabled' => true,
    'update_interval' => 3000, // milliseconds
    'max_agents_display' => 100,
    'avatar_size' => 32,
    'office_dimensions' => [
        'width' => 800,
        'height' => 600,
    ],
    'pixel_avatar_size' => 32,
    'movement_speed' => 2, // pixels per frame
    'enable_animations' => true,
    'enable_drag_drop' => true,
    'enable_heatmap' => true,
    'enable_minimap' => true,
],
```

## Database Schema

### agency_agents
- Agent information, position, status, performance

### agency_agent_tasks
- Task assignments and progress

### agency_agent_communications
- Messages between agents

### agency_agent_metrics
- Performance metrics

### agency_office_zones
- Office zone definitions

### agency_agent_activities
- Activity logs

## Monitoring

### Health Checks
- Low performance agents (< 50%)
- Stuck tasks (> 24 hours)
- Unread messages (> 10)
- Zone capacity (> 90%)

### Reports
- Daily/Weekly/Monthly summaries
- Task completion rates
- Communication statistics
- Zone occupancy

## Troubleshooting

### Agents not appearing
1. Run migrations: `php artisan migrate`
2. Initialize agents: `php artisan agency:initialize`
3. Check logs: `storage/logs/agency-agents/`

### Positions not updating
1. Check WebSocket connection
2. Verify API endpoints are accessible
3. Check browser console for errors

### Performance issues
1. Reduce `max_agents_display` in config
2. Increase `update_interval`
3. Disable heatmap if not needed

## License

Part of the Bikube project.
