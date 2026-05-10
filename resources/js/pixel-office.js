/**
 * Pixel Office - Virtual 2D Office System
 * Retro pixel art style with animations and interactivity
 */

class PixelOffice {
    constructor() {
        this.agents = new Map();
        this.rooms = new Map();
        this.furniture = new Map();
        this.selectedAgent = null;
        this.selectedRoom = null;
        this.animationFrameId = null;
        this.websocket = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        
        this.init();
    }

    /**
     * Initialize the pixel office
     */
    init() {
        this.setupEventListeners();
        this.setupWebSocket();
        this.startAnimationLoop();
        this.loadAgents();
        this.loadRooms();
        this.loadFurniture();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Agent click events
        document.addEventListener('click', (e) => {
            const agentElement = e.target.closest('.pixel-agent');
            if (agentElement) {
                const agentId = agentElement.dataset.agentId;
                this.selectAgent(agentId);
            }

            const roomElement = e.target.closest('.pixel-room');
            if (roomElement) {
                const roomName = roomElement.dataset.roomName;
                this.selectRoom(roomName);
            }
        });

        // Minimap click events
        const minimap = document.querySelector('.pixel-minimap');
        if (minimap) {
            minimap.addEventListener('click', (e) => {
                this.handleMinimapClick(e);
            });
        }

        // Filter events
        const categoryFilter = document.getElementById('filter-category');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', (e) => {
                this.filterByCategory(e.target.value);
            });
        }

        const statusFilter = document.getElementById('filter-status');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.filterByStatus(e.target.value);
            });
        }

        const zoneFilter = document.getElementById('filter-zone');
        if (zoneFilter) {
            zoneFilter.addEventListener('change', (e) => {
                this.filterByZone(e.target.value);
            });
        }

        // Chat send event
        const chatInput = document.getElementById('chat-input');
        if (chatInput) {
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.sendMessage();
                }
            });
        }

        const chatSendBtn = document.getElementById('chat-send');
        if (chatSendBtn) {
            chatSendBtn.addEventListener('click', () => {
                this.sendMessage();
            });
        }
    }

    /**
     * Setup WebSocket connection
     */
    setupWebSocket() {
        const wsUrl = `ws://${window.location.hostname}:6001/ws`;
        
        try {
            this.websocket = new WebSocket(wsUrl);
            
            this.websocket.onopen = () => {
                console.log('WebSocket connected');
                this.reconnectAttempts = 0;
                this.subscribeToChannels();
            };
            
            this.websocket.onmessage = (event) => {
                this.handleWebSocketMessage(event);
            };
            
            this.websocket.onclose = () => {
                console.log('WebSocket disconnected');
                this.reconnect();
            };
            
            this.websocket.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
        } catch (error) {
            console.error('Failed to setup WebSocket:', error);
        }
    }

    /**
     * Reconnect to WebSocket
     */
    reconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Reconnecting... Attempt ${this.reconnectAttempts}`);
            setTimeout(() => {
                this.setupWebSocket();
            }, 2000 * this.reconnectAttempts);
        }
    }

    /**
     * Subscribe to WebSocket channels
     */
    subscribeToChannels() {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            // Subscribe to office updates channel
            this.websocket.send(JSON.stringify({
                event: 'subscribe',
                channel: 'office-updates'
            }));
            
            // Subscribe to chat channel
            this.websocket.send(JSON.stringify({
                event: 'subscribe',
                channel: 'office-chat'
            }));
        }
    }

    /**
     * Handle WebSocket messages
     */
    handleWebSocketMessage(event) {
        try {
            const data = JSON.parse(event.data);
            
            switch (data.event) {
                case 'agent-moved':
                    this.updateAgentPosition(data.agent);
                    break;
                case 'agent-status-changed':
                    this.updateAgentStatus(data.agent);
                    break;
                case 'message-sent':
                    this.addChatMessage(data.message);
                    break;
                case 'task-completed':
                    this.showNotification(data.task);
                    break;
                case 'agent-activity':
                    this.updateAgentActivity(data.activity);
                    break;
                default:
                    console.log('Unknown event:', data.event);
            }
        } catch (error) {
            console.error('Failed to parse WebSocket message:', error);
        }
    }

    /**
     * Load agents from API
     */
    async loadAgents() {
        try {
            const response = await fetch('/api/agency-agents/agents');
            const data = await response.json();
            
            if (data.success) {
                data.data.forEach(agent => {
                    this.addAgent(agent);
                });
            }
        } catch (error) {
            console.error('Failed to load agents:', error);
        }
    }

    /**
     * Add agent to the office
     */
    addAgent(agentData) {
        const agentElement = this.createAgentElement(agentData);
        const canvas = document.getElementById('office-canvas');
        
        if (canvas) {
            canvas.appendChild(agentElement);
            this.agents.set(agentData.id, {
                element: agentElement,
                data: agentData
            });
        }
    }

    /**
     * Create agent element
     */
    createAgentElement(agentData) {
        const agent = document.createElement('div');
        agent.className = 'pixel-agent';
        agent.dataset.agentId = agentData.id;
        agent.style.left = `${agentData.position_x}px`;
        agent.style.top = `${agentData.position_y}px`;
        
        // Agent sprite
        const sprite = document.createElement('div');
        sprite.className = 'pixel-agent-sprite';
        sprite.style.backgroundImage = `url(/pixel-sprites/agents/${agentData.category}.svg)`;
        sprite.style.backgroundSize = '128px 128px';
        sprite.style.backgroundPosition = '0 0';
        
        // Status indicator
        const status = document.createElement('div');
        status.className = `pixel-agent-status pixel-agent-status-${agentData.status}`;
        
        // Name label
        const nameLabel = document.createElement('div');
        nameLabel.className = 'pixel-agent-name';
        nameLabel.textContent = agentData.name;
        
        agent.appendChild(sprite);
        agent.appendChild(status);
        agent.appendChild(nameLabel);
        
        // Add animation based on activity
        this.setAgentAnimation(agent, agentData.current_activity);
        
        return agent;
    }

    /**
     * Set agent animation
     */
    setAgentAnimation(agentElement, activity) {
        const sprite = agentElement.querySelector('.pixel-agent-sprite');
        
        // Remove existing animations
        sprite.style.animation = '';
        
        switch (activity) {
            case 'walking':
                sprite.style.animation = 'walk-down 0.5s steps(4) infinite';
                break;
            case 'typing':
                sprite.style.animation = 'typing 0.3s steps(2) infinite';
                break;
            case 'talking':
                sprite.style.animation = 'talking 0.5s steps(2) infinite';
                break;
            case 'idle':
            default:
                sprite.style.animation = 'idle 2s ease-in-out infinite';
                break;
        }
    }

    /**
     * Update agent position
     */
    updateAgentPosition(agentData) {
        const agent = this.agents.get(agentData.id);
        if (agent) {
            agent.element.style.left = `${agentData.position_x}px`;
            agent.element.style.top = `${agentData.position_y}px`;
            agent.data = { ...agent.data, ...agentData };
            
            // Update animation if moving
            if (agentData.is_moving) {
                this.setAgentAnimation(agent.element, 'walking');
            } else {
                this.setAgentAnimation(agent.element, agentData.current_activity);
            }
        }
    }

    /**
     * Update agent status
     */
    updateAgentStatus(agentData) {
        const agent = this.agents.get(agentData.id);
        if (agent) {
            const statusElement = agent.element.querySelector('.pixel-agent-status');
            statusElement.className = `pixel-agent-status pixel-agent-status-${agentData.status}`;
            agent.data = { ...agent.data, ...agentData };
        }
    }

    /**
     * Update agent activity
     */
    updateAgentActivity(activityData) {
        const agent = this.agents.get(activityData.agent_id);
        if (agent) {
            this.setAgentAnimation(agent.element, activityData.activity_type);
            agent.data.current_activity = activityData.activity_type;
        }
    }

    /**
     * Select agent
     */
    selectAgent(agentId) {
        // Deselect previous agent
        if (this.selectedAgent) {
            this.selectedAgent.element.classList.remove('selected');
        }
        
        const agent = this.agents.get(agentId);
        if (agent) {
            this.selectedAgent = agent;
            agent.element.classList.add('selected');
            this.showAgentDetails(agent.data);
        }
    }

    /**
     * Show agent details
     */
    showAgentDetails(agentData) {
        const detailsPanel = document.getElementById('agent-details');
        if (detailsPanel) {
            detailsPanel.innerHTML = `
                <div class="pixel-card p-4">
                    <h3 class="pixel-font text-lg mb-2">${agentData.name}</h3>
                    <div class="space-y-2 text-sm">
                        <p><strong>Category:</strong> ${agentData.category}</p>
                        <p><strong>Status:</strong> <span class="pixel-agent-status-${agentData.status}">${agentData.status}</span></p>
                        <p><strong>Zone:</strong> ${agentData.current_zone}</p>
                        <p><strong>Activity:</strong> ${agentData.current_activity}</p>
                        <p><strong>Tasks Completed:</strong> ${agentData.tasks_completed}</p>
                        <p><strong>Performance:</strong> ${agentData.performance_score}%</p>
                        ${agentData.status_message ? `<p><strong>Message:</strong> ${agentData.status_message}</p>` : ''}
                    </div>
                </div>
            `;
        }
    }

    /**
     * Load rooms from API
     */
    async loadRooms() {
        try {
            const response = await fetch('/api/agency-agents/zones');
            const data = await response.json();
            
            if (data.success) {
                data.data.forEach(room => {
                    this.addRoom(room);
                });
            }
        } catch (error) {
            console.error('Failed to load rooms:', error);
        }
    }

    /**
     * Add room to the office
     */
    addRoom(roomData) {
        const roomElement = this.createRoomElement(roomData);
        const canvas = document.getElementById('office-canvas');
        
        if (canvas) {
            canvas.appendChild(roomElement);
            this.rooms.set(roomData.name, {
                element: roomElement,
                data: roomData
            });
        }
    }

    /**
     * Create room element
     */
    createRoomElement(roomData) {
        const room = document.createElement('div');
        room.className = `pixel-room pixel-room-${roomData.name}`;
        room.dataset.roomName = roomData.name;
        room.style.left = `${roomData.bounds.x_min}px`;
        room.style.top = `${roomData.bounds.y_min}px`;
        room.style.width = `${roomData.bounds.x_max - roomData.bounds.x_min}px`;
        room.style.height = `${roomData.bounds.y_max - roomData.bounds.y_min}px`;
        
        // Room label
        const label = document.createElement('div');
        label.className = 'pixel-room-label pixel-font-sm';
        label.textContent = roomData.display_name;
        
        // Room occupancy
        const occupancy = document.createElement('div');
        occupancy.className = 'pixel-room-occupancy pixel-font-sm';
        occupancy.textContent = `${roomData.current_occupancy}/${roomData.capacity}`;
        
        room.appendChild(label);
        room.appendChild(occupancy);
        
        return room;
    }

    /**
     * Select room
     */
    selectRoom(roomName) {
        // Deselect previous room
        if (this.selectedRoom) {
            this.selectedRoom.element.classList.remove('selected');
        }
        
        const room = this.rooms.get(roomName);
        if (room) {
            this.selectedRoom = room;
            room.element.classList.add('selected');
            this.showRoomDetails(room.data);
        }
    }

    /**
     * Show room details
     */
    showRoomDetails(roomData) {
        const detailsPanel = document.getElementById('room-details');
        if (detailsPanel) {
            detailsPanel.innerHTML = `
                <div class="pixel-card p-4">
                    <h3 class="pixel-font text-lg mb-2">${roomData.display_name}</h3>
                    <div class="space-y-2 text-sm">
                        <p><strong>Occupancy:</strong> ${roomData.current_occupancy}/${roomData.capacity}</p>
                        <p><strong>Percentage:</strong> ${roomData.occupancy_percentage}%</p>
                        <p><strong>Amenities:</strong> ${roomData.amenities.join(', ')}</p>
                    </div>
                </div>
            `;
        }
    }

    /**
     * Load furniture
     */
    loadFurniture() {
        // This would load furniture from a configuration file
        // For now, we'll create some sample furniture
        const furnitureConfig = [
            { type: 'desk', room: 'development', x: 50, y: 50 },
            { type: 'monitor', room: 'development', x: 60, y: 50 },
            { type: 'chair', room: 'development', x: 50, y: 70 },
            { type: 'server-rack', room: 'server', x: 650, y: 50 },
            { type: 'whiteboard', room: 'meeting', x: 650, y: 250 },
            { type: 'conference-table', room: 'meeting', x: 680, y: 280 },
        ];
        
        furnitureConfig.forEach(config => {
            this.addFurniture(config);
        });
    }

    /**
     * Add furniture to the office
     */
    addFurniture(furnitureData) {
        const furnitureElement = this.createFurnitureElement(furnitureData);
        const canvas = document.getElementById('office-canvas');
        
        if (canvas) {
            canvas.appendChild(furnitureElement);
            this.furniture.set(`${furnitureData.type}-${furnitureData.x}-${furnitureData.y}`, {
                element: furnitureElement,
                data: furnitureData
            });
        }
    }

    /**
     * Create furniture element
     */
    createFurnitureElement(furnitureData) {
        const furniture = document.createElement('div');
        furniture.className = 'pixel-furniture';
        furniture.style.left = `${furnitureData.x}px`;
        furniture.style.top = `${furnitureData.y}px`;
        furniture.style.backgroundImage = `url(/pixel-sprites/furniture/${furnitureData.type}.svg)`;
        furniture.style.backgroundSize = 'contain';
        furniture.style.backgroundRepeat = 'no-repeat';
        
        // Set size based on furniture type
        const sizes = {
            'desk': { width: 32, height: 32 },
            'monitor': { width: 16, height: 16 },
            'chair': { width: 16, height: 16 },
            'server-rack': { width: 32, height: 64 },
            'whiteboard': { width: 64, height: 32 },
            'conference-table': { width: 64, height: 32 },
        };
        
        const size = sizes[furnitureData.type] || { width: 32, height: 32 };
        furniture.style.width = `${size.width}px`;
        furniture.style.height = `${size.height}px`;
        
        return furniture;
    }

    /**
     * Filter agents by category
     */
    filterByCategory(category) {
        this.agents.forEach((agent, id) => {
            if (category === 'all' || agent.data.category === category) {
                agent.element.style.display = 'block';
            } else {
                agent.element.style.display = 'none';
            }
        });
    }

    /**
     * Filter agents by status
     */
    filterByStatus(status) {
        this.agents.forEach((agent, id) => {
            if (status === 'all' || agent.data.status === status) {
                agent.element.style.display = 'block';
            } else {
                agent.element.style.display = 'none';
            }
        });
    }

    /**
     * Filter agents by zone
     */
    filterByZone(zone) {
        this.agents.forEach((agent, id) => {
            if (zone === 'all' || agent.data.current_zone === zone) {
                agent.element.style.display = 'block';
            } else {
                agent.element.style.display = 'none';
            }
        });
    }

    /**
     * Handle minimap click
     */
    handleMinimapClick(event) {
        const minimap = event.currentTarget;
        const rect = minimap.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        
        // Scale to main canvas
        const scale = 4; // Minimap is 1/4 size
        const canvasX = x * scale;
        const canvasY = y * scale;
        
        // Scroll to position
        const canvas = document.getElementById('office-canvas');
        if (canvas) {
            canvas.scrollTo({
                left: canvasX - canvas.clientWidth / 2,
                top: canvasY - canvas.clientHeight / 2,
                behavior: 'smooth'
            });
        }
    }

    /**
     * Send chat message
     */
    sendMessage() {
        const input = document.getElementById('chat-input');
        const message = input.value.trim();
        
        if (message && this.selectedAgent) {
            // Send via WebSocket
            if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                this.websocket.send(JSON.stringify({
                    event: 'send-message',
                    data: {
                        receiver_id: this.selectedAgent.data.id,
                        content: message,
                        type: 'message'
                    }
                }));
            }
            
            // Clear input
            input.value = '';
        }
    }

    /**
     * Add chat message to UI
     */
    addChatMessage(messageData) {
        const chatContainer = document.getElementById('chat-messages');
        if (chatContainer) {
            const messageElement = document.createElement('div');
            messageElement.className = `pixel-chat-message pixel-chat-message-${messageData.type}`;
            messageElement.innerHTML = `
                <div class="font-semibold text-sm">${messageData.from} → ${messageData.to}</div>
                <div class="text-sm mt-1">${messageData.content}</div>
                <div class="text-xs text-gray-500 mt-1">${messageData.created_at}</div>
            `;
            
            chatContainer.appendChild(messageElement);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    }

    /**
     * Show notification
     */
    showNotification(data) {
        const notificationContainer = document.getElementById('notifications');
        if (notificationContainer) {
            const notification = document.createElement('div');
            notification.className = `pixel-notification pixel-notification-${data.type}`;
            notification.innerHTML = `
                <div class="font-semibold">${data.title}</div>
                <div class="text-sm mt-1">${data.text}</div>
            `;
            
            notificationContainer.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    }

    /**
     * Start animation loop
     */
    startAnimationLoop() {
        const animate = () => {
            // Update agent animations
            this.agents.forEach((agent) => {
                if (agent.data.is_moving) {
                    // Update walking animation
                    const sprite = agent.element.querySelector('.pixel-agent-sprite');
                    const currentPos = sprite.style.backgroundPosition;
                    const [x, y] = currentPos.split(' ').map(p => parseInt(p));
                    
                    // Cycle through frames
                    const newX = (x - 32) % -128;
                    sprite.style.backgroundPosition = `${newX}px ${y}px`;
                }
            });
            
            // Update minimap
            this.updateMinimap();
            
            this.animationFrameId = requestAnimationFrame(animate);
        };
        
        animate();
    }

    /**
     * Update minimap
     */
    updateMinimap() {
        const minimap = document.querySelector('.pixel-minimap');
        if (!minimap) return;
        
        // Clear existing agents
        minimap.querySelectorAll('.pixel-minimap-agent').forEach(el => el.remove());
        
        // Add agent dots
        this.agents.forEach((agent) => {
            const dot = document.createElement('div');
            dot.className = 'pixel-minimap-agent';
            dot.style.left = `${agent.data.position_x * 0.1}px`;
            dot.style.top = `${agent.data.position_y * 0.1}px`;
            minimap.appendChild(dot);
        });
    }

    /**
     * Stop animation loop
     */
    stopAnimationLoop() {
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
            this.animationFrameId = null;
        }
    }

    /**
     * Destroy the pixel office
     */
    destroy() {
        this.stopAnimationLoop();
        if (this.websocket) {
            this.websocket.close();
        }
        this.agents.clear();
        this.rooms.clear();
        this.furniture.clear();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.pixelOffice = new PixelOffice();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PixelOffice;
}
