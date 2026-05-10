# Virtual 2D Office - Pixel Agents System

## Overview

Virtual 2D Office is a comprehensive system for managing pixel agents in a virtual office environment. It provides a visual canvas where agents can be placed, moved, and managed, along with task assignment, messaging, and zone management capabilities.

## Features

### 🎨 Visual Canvas
- **2D Office Layout**: Interactive canvas with draggable agents
- **Zone Management**: Multiple office zones (workspace, meeting room, brainstorm, break room, cafeteria, lounge)
- **Real-time Updates**: Live agent status and position updates
- **Color-coded Categories**: Agents organized by category with distinct colors

### 👥 Agent Management
- **162 Pre-built Agents**: Imported from agency-agents directory
- **Status Tracking**: Active, Busy, Idle, Offline statuses
- **Skill Management**: Track agent skills and expertise
- **Category Organization**: Engineering, Design, Marketing, Sales, etc.

### 📋 Task Management
- **Task Creation**: Create and assign tasks to agents
- **Priority Levels**: Low, Medium, High, Urgent
- **Status Tracking**: Pending, In Progress, Completed, Cancelled
- **Due Dates**: Set and track task deadlines

### 💬 Messaging System
- **Agent Communication**: Send messages between agents
- **Zone Broadcasting**: Send messages to entire zones
- **Message Types**: Text, Task, Alert, Notification
- **Read Tracking**: Track message read status

### 📊 Dashboard & Analytics
- **Real-time Statistics**: Agent counts, task status, zone occupancy
- **Activity Feed**: Recent messages and updates
- **Status Overview**: Visual breakdown of agent and task statuses

## Architecture

### Database Schema

#### Categories Table
```sql
- id (bigint)
- name (string)
- slug (string, unique)
- color (string)
- description (text, nullable)
- timestamps
```

#### Office Zones Table
```sql
- id (bigint)
- name (string)
- slug (string, unique)
- description (text, nullable)
- color (string)
- x (integer)
- y (integer)
- width (integer)
- height (integer)
- timestamps
```

#### Agents Table
```sql
- id (bigint)
- name (string)
- slug (string, unique)
- role (string)
- description (text, nullable)
- category_id (bigint, foreign key)
- current_zone_id (bigint, foreign key, nullable)
- status (enum: active, busy, idle, offline)
- skills (json, nullable)
- position_x (integer, nullable)
- position_y (integer, nullable)
- metadata (json, nullable)
- timestamps
```

#### Tasks Table
```sql
- id (bigint)
- title (string)
- description (text, nullable)
- agent_id (bigint, foreign key)
- zone_id (bigint, foreign key, nullable)
- status (enum: pending, in_progress, completed, cancelled)
- priority (enum: low, medium, high, urgent)
- due_date (timestamp, nullable)
- completed_at (timestamp, nullable)
- timestamps
```

#### Messages Table
```sql
- id (bigint)
- sender_id (bigint, foreign key)
- receiver_id (bigint, foreign key, nullable)
- zone_id (bigint, foreign key, nullable)
- content (text)
- type (enum: text, task, alert, notification)
- read_at (timestamp, nullable)
- timestamps
```

### Models

#### Category Model
- Has many agents
- Color-coded for visual identification

#### OfficeZone Model
- Has many agents
- Defines office areas with position and dimensions

#### Agent Model
- Belongs to category
- Belongs to current zone (optional)
- Has many tasks
- Has many sent messages
- Has many received messages

#### Task Model
- Belongs to agent
- Belongs to zone (optional)
- Tracks status and priority

#### Message Model
- Belongs to sender (agent)
- Belongs to receiver (agent, optional)
- Belongs to zone (optional)
- Supports multiple message types

## Installation

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Database
```bash
# Seed with sample data
php artisan virtual-office:seed

# Or seed fresh (truncate tables first)
php artisan virtual-office:seed --fresh

# Customize seed data
php artisan virtual-office:seed --agents=50 --tasks=100 --messages=80
```

### 3. Import Agents from agency-agents
```bash
# Import all agents from agency-agents directory
php artisan virtual-office:import-agents

# Import from custom path
php artisan virtual-office:import-agents --path=custom/path/to/agents

# Force import (overwrite existing)
php artisan virtual-office:import-agents --force
```

## Usage

### Web Interface

#### Public Pages
- **Home**: `/virtual-office` - Main dashboard
- **Canvas**: `/virtual-office/canvas` - Interactive 2D canvas
- **Agents**: `/virtual-office/agents` - Agent listing
- **Tasks**: `/virtual-office/tasks` - Task management
- **Messages**: `/virtual-office/messages` - Message center
- **Zones**: `/virtual-office/zones` - Zone management

#### Admin Panel (Filament)
- **Dashboard**: `/admin/virtual-office` - Statistics and overview
- **Agents**: `/admin/virtual-office/agents` - Agent CRUD
- **Categories**: `/admin/virtual-office/categories` - Category management
- **Zones**: `/admin/virtual-office/zones` - Zone management
- **Tasks**: `/admin/virtual-office/tasks` - Task management
- **Messages**: `/admin/virtual-office/messages` - Message management

### API Endpoints

#### Agents
```
GET    /api/virtual-office/agents              - List all agents
POST   /api/virtual-office/agents              - Create agent
GET    /api/virtual-office/agents/{id}         - Get agent
PUT    /api/virtual-office/agents/{id}         - Update agent
DELETE /api/virtual-office/agents/{id}         - Delete agent
PATCH  /api/virtual-office/agents/{id}/status  - Update agent status
PATCH  /api/virtual-office/agents/{id}/position - Update agent position
```

#### Tasks
```
GET    /api/virtual-office/tasks               - List all tasks
POST   /api/virtual-office/tasks               - Create task
GET    /api/virtual-office/tasks/{id}          - Get task
PUT    /api/virtual-office/tasks/{id}          - Update task
DELETE /api/virtual-office/tasks/{id}          - Delete task
PATCH  /api/virtual-office/tasks/{id}/status   - Update task status
```

#### Messages
```
GET    /api/virtual-office/messages            - List all messages
POST   /api/virtual-office/messages            - Create message
GET    /api/virtual-office/messages/{id}       - Get message
DELETE /api/virtual-office/messages/{id}       - Delete message
PATCH  /api/virtual-office/messages/{id}/read  - Mark as read
```

#### Zones
```
GET    /api/virtual-office/zones               - List all zones
POST   /api/virtual-office/zones               - Create zone
GET    /api/virtual-office/zones/{id}          - Get zone
PUT    /api/virtual-office/zones/{id}          - Update zone
DELETE /api/virtual-office/zones/{id}          - Delete zone
```

#### Categories
```
GET    /api/virtual-office/categories          - List all categories
POST   /api/virtual-office/categories          - Create category
GET    /api/virtual-office/categories/{id}     - Get category
PUT    /api/virtual-office/categories/{id}     - Update category
DELETE /api/virtual-office/categories/{id}     - Delete category
```

## Livewire Components

### OfficeCanvas Component
Main interactive canvas component with:
- Agent display and positioning
- Zone visualization
- Real-time filtering
- Agent selection and details
- Task creation
- Message sending

**Usage:**
```blade
<livewire:virtual-office.office-canvas />
```

## Filament Resources

### AgentResource
Full CRUD interface for managing agents with:
- Name, slug, role, description
- Category selection
- Zone assignment
- Status management
- Skills and metadata
- Position coordinates

### VirtualOfficeStatsWidget
Dashboard widget showing:
- Total agents count
- Active/Busy/Idle/Offline breakdown
- Task statistics
- Zone occupancy
- Message counts

## Configuration

### Virtual Office Config
Edit `config/virtual-office.php` to customize:
- Default canvas dimensions
- Agent status options
- Task priority levels
- Message types
- Zone colors

## Development

### Adding New Agent Categories
1. Create category in database:
```php
Category::create([
    'name' => 'New Category',
    'slug' => 'new-category',
    'color' => '#FF5733',
    'description' => 'Description of new category',
]);
```

2. Add agents to category:
```php
Agent::create([
    'name' => 'Agent Name',
    'slug' => 'agent-name',
    'role' => 'Agent Role',
    'category_id' => $category->id,
    // ... other fields
]);
```

### Creating Custom Zones
```php
OfficeZone::create([
    'name' => 'Custom Zone',
    'slug' => 'custom-zone',
    'description' => 'Custom zone description',
    'color' => '#3B82F6',
    'x' => 100,
    'y' => 100,
    'width' => 200,
    'height' => 150,
]);
```

### Extending Agent Capabilities
Add custom fields to agents:
```php
// In migration
$table->json('custom_field')->nullable();

// In model
protected $casts = [
    'custom_field' => 'array',
];
```

## Troubleshooting

### Agents Not Showing on Canvas
1. Check if agents have `position_x` and `position_y` set
2. Verify agents are assigned to zones
3. Check agent status is not 'offline'

### Tasks Not Updating
1. Verify agent exists and is active
2. Check task status transitions are valid
3. Ensure due dates are in the future

### Messages Not Sending
1. Verify sender agent exists
2. Check receiver agent exists (if specified)
3. Ensure zone exists (if specified)

## Performance Optimization

### Database Indexes
All foreign keys and frequently queried fields are indexed:
- `agents.category_id`
- `agents.current_zone_id`
- `agents.status`
- `tasks.agent_id`
- `tasks.zone_id`
- `tasks.status`
- `messages.sender_id`
- `messages.receiver_id`
- `messages.zone_id`

### Caching
Consider caching:
- Agent counts by status
- Zone occupancy
- Task statistics
- Category lists

### Query Optimization
Use eager loading to prevent N+1 queries:
```php
Agent::with(['category', 'currentZone', 'tasks'])->get();
```

## Security

### Authentication
- Web routes can be protected with `auth` middleware
- API routes use Laravel Sanctum for authentication
- Admin routes require Filament authentication

### Authorization
Implement policies for:
- Agent management
- Task assignment
- Message sending
- Zone configuration

## Contributing

1. Fork the repository
2. Create feature branch
3. Make changes
4. Add tests
5. Submit pull request

## License

This project is proprietary software. All rights reserved.

## Support

For support, contact the development team or create an issue in the project repository.
