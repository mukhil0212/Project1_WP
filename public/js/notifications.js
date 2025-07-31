/**
 * Real-time notification system for Choose Your Path RPG
 */
class NotificationSystem {
    constructor() {
        this.container = null;
        this.notifications = [];
        this.maxNotifications = 5;
        this.init();
    }

    init() {
        // Create notification container
        this.container = document.createElement('div');
        this.container.className = 'notification-container';
        this.container.innerHTML = `
            <style>
                .notification-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    pointer-events: none;
                }
                
                .notification {
                    background: rgba(255, 255, 255, 0.95);
                    border-radius: 12px;
                    padding: 16px 20px;
                    margin-bottom: 12px;
                    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255,255,255,0.2);
                    min-width: 300px;
                    max-width: 400px;
                    pointer-events: auto;
                    transform: translateX(100%);
                    opacity: 0;
                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                    position: relative;
                    overflow: hidden;
                }
                
                .notification.show {
                    transform: translateX(0);
                    opacity: 1;
                }
                
                .notification.hide {
                    transform: translateX(100%);
                    opacity: 0;
                }
                
                .notification::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 4px;
                    height: 100%;
                    background: var(--notification-color, #667eea);
                }
                
                .notification-header {
                    display: flex;
                    align-items: center;
                    margin-bottom: 8px;
                }
                
                .notification-icon {
                    font-size: 24px;
                    margin-right: 12px;
                }
                
                .notification-title {
                    font-weight: 600;
                    color: #2c3e50;
                    font-size: 16px;
                    flex: 1;
                }
                
                .notification-close {
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    color: #7f8c8d;
                    padding: 0;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: all 0.2s ease;
                }
                
                .notification-close:hover {
                    background: rgba(0,0,0,0.1);
                    color: #2c3e50;
                }
                
                .notification-content {
                    color: #34495e;
                    font-size: 14px;
                    line-height: 1.5;
                }
                
                .notification.achievement {
                    --notification-color: #f39c12;
                    background: linear-gradient(135deg, rgba(243,156,18,0.1), rgba(255,255,255,0.95));
                }
                
                .notification.success {
                    --notification-color: #27ae60;
                    background: linear-gradient(135deg, rgba(39,174,96,0.1), rgba(255,255,255,0.95));
                }
                
                .notification.info {
                    --notification-color: #3498db;
                    background: linear-gradient(135deg, rgba(52,152,219,0.1), rgba(255,255,255,0.95));
                }
                
                .notification.warning {
                    --notification-color: #e67e22;
                    background: linear-gradient(135deg, rgba(230,126,34,0.1), rgba(255,255,255,0.95));
                }
                
                .notification.error {
                    --notification-color: #e74c3c;
                    background: linear-gradient(135deg, rgba(231,76,60,0.1), rgba(255,255,255,0.95));
                }
                
                .notification-progress {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    height: 3px;
                    background: var(--notification-color, #667eea);
                    transition: width linear;
                }
                
                @media (max-width: 768px) {
                    .notification-container {
                        top: 10px;
                        right: 10px;
                        left: 10px;
                    }
                    
                    .notification {
                        min-width: auto;
                        max-width: none;
                    }
                }
            </style>
        `;
        
        document.body.appendChild(this.container);
    }

    show(options) {
        const {
            title,
            message,
            type = 'info',
            icon = this.getDefaultIcon(type),
            duration = 5000,
            persistent = false
        } = options;

        // Remove oldest notification if at max capacity
        if (this.notifications.length >= this.maxNotifications) {
            this.hide(this.notifications[0].id);
        }

        const id = Date.now() + Math.random();
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.dataset.id = id;

        notification.innerHTML = `
            <div class="notification-header">
                <span class="notification-icon">${icon}</span>
                <span class="notification-title">${title}</span>
                <button class="notification-close" onclick="notifications.hide('${id}')">&times;</button>
            </div>
            <div class="notification-content">${message}</div>
            ${!persistent ? '<div class="notification-progress"></div>' : ''}
        `;

        this.container.appendChild(notification);
        this.notifications.push({ id, element: notification, persistent });

        // Trigger animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Auto-hide if not persistent
        if (!persistent && duration > 0) {
            const progressBar = notification.querySelector('.notification-progress');
            if (progressBar) {
                progressBar.style.width = '100%';
                progressBar.style.transitionDuration = duration + 'ms';
                setTimeout(() => {
                    progressBar.style.width = '0%';
                }, 10);
            }

            setTimeout(() => {
                this.hide(id);
            }, duration);
        }

        return id;
    }

    hide(id) {
        const notification = this.notifications.find(n => n.id == id);
        if (!notification) return;

        notification.element.classList.add('hide');
        
        setTimeout(() => {
            if (notification.element.parentNode) {
                notification.element.parentNode.removeChild(notification.element);
            }
            this.notifications = this.notifications.filter(n => n.id !== id);
        }, 400);
    }

    getDefaultIcon(type) {
        const icons = {
            achievement: '🏆',
            success: '✅',
            info: 'ℹ️',
            warning: '⚠️',
            error: '❌'
        };
        return icons[type] || 'ℹ️';
    }

    // Specific notification types
    achievement(title, message, icon = '🏆') {
        return this.show({
            title,
            message,
            type: 'achievement',
            icon,
            duration: 8000
        });
    }

    success(title, message) {
        return this.show({
            title,
            message,
            type: 'success',
            duration: 4000
        });
    }

    info(title, message) {
        return this.show({
            title,
            message,
            type: 'info',
            duration: 5000
        });
    }

    warning(title, message) {
        return this.show({
            title,
            message,
            type: 'warning',
            duration: 6000
        });
    }

    error(title, message) {
        return this.show({
            title,
            message,
            type: 'error',
            duration: 7000
        });
    }

    // Game-specific notifications
    itemFound(itemName, itemIcon = '📦') {
        return this.achievement(
            'Item Found!',
            `You discovered: ${itemName}`,
            itemIcon
        );
    }

    hpChange(change, current) {
        const type = change > 0 ? 'success' : 'warning';
        const icon = change > 0 ? '💚' : '💔';
        const message = change > 0 
            ? `Gained ${change} HP (${current}/100)`
            : `Lost ${Math.abs(change)} HP (${current}/100)`;
        
        return this.show({
            title: 'Health Update',
            message,
            type,
            icon,
            duration: 3000
        });
    }

    sceneTransition(sceneName) {
        return this.info(
            'New Location',
            `Entering: ${sceneName}`,
            '🗺️'
        );
    }

    gameComplete(ending, stats) {
        const endingIcons = {
            victory: '👑',
            defeat: '💀',
            peaceful_rest: '🕊️'
        };
        
        return this.show({
            title: 'Game Complete!',
            message: `Ending: ${ending}<br>Final HP: ${stats.hp}<br>Choices: ${stats.choices}`,
            type: 'achievement',
            icon: endingIcons[ending] || '🏁',
            duration: 10000
        });
    }
}

// Initialize global notification system
const notifications = new NotificationSystem();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationSystem;
}
