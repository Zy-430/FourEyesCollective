// notification.js
function showNotification(message, type) {
    // Remove any existing notifications first
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notification => notification.remove());

    // Calculate header height for positioning below header
    const header = document.querySelector('header');
    const headerHeight = header ? header.offsetHeight : 0;
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'custom-notification';
    notification.style.position = 'fixed';
    notification.style.top = `${headerHeight + 20}px`; // 20px below header
    notification.style.left = '50%';
    notification.style.transform = 'translateX(-50%)';
    notification.style.padding = '15px 25px';
    notification.style.borderRadius = '8px';
    notification.style.color = 'white';
    notification.style.zIndex = '10000';
    notification.style.fontWeight = 'bold';
    notification.style.textAlign = 'center';
    notification.style.boxShadow = '0 5px 15px rgba(0,0,0,0.3)';
    notification.style.minWidth = '300px';
    notification.style.maxWidth = '80%';
    notification.style.cursor = 'pointer';
    notification.style.transition = 'all 0.3s ease';
    notification.style.opacity = '0';
    notification.style.animation = 'slideDown 0.3s ease forwards';

    if (type === 'success') {
        notification.style.background = 'linear-gradient(135deg, #27ae60, #2ecc71)';
        notification.style.borderLeft = '5px solid #229954';
    } else {
        notification.style.background = 'linear-gradient(135deg, #e74c3c, #c0392b)';
        notification.style.borderLeft = '5px solid #922b21';
    }

    // Add icon
    const icon = document.createElement('span');
    icon.style.marginRight = '10px';
    icon.style.fontSize = '18px';
    icon.style.verticalAlign = 'middle';

    if (type === 'success') {
        icon.textContent = '✓';
    } else {
        icon.textContent = '✗';
    }

    const text = document.createElement('span');
    text.textContent = message;
    text.style.verticalAlign = 'middle';

    notification.appendChild(icon);
    notification.appendChild(text);
    document.body.appendChild(notification);

    // Trigger animation
    setTimeout(() => {
        notification.style.opacity = '1';
    }, 10);

    // Add click to remove functionality
    notification.addEventListener('click', function() {
        this.style.opacity = '0';
        this.style.transform = 'translateX(-50%) translateY(-10px)';
        setTimeout(() => {
            if (this.parentNode) {
                this.parentNode.removeChild(this);
            }
        }, 300);
    });

    // Remove notification after 3 seconds
    const timeoutId = setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(-50%) translateY(-10px)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);

    // Clear timeout if notification is clicked
    notification.addEventListener('click', function() {
        clearTimeout(timeoutId);
    });

    // Add animation keyframes dynamically
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateX(-50%) translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(-50%) translateY(0);
                }
            }
            
            @media (max-width: 768px) {
                .custom-notification {
                    min-width: 250px !important;
                    max-width: 90% !important;
                    padding: 12px 20px !important;
                    font-size: 14px !important;
                    top: ${headerHeight + 10}px !important;
                }
            }
            
            @media (max-width: 480px) {
                .custom-notification {
                    min-width: 200px !important;
                    padding: 10px 15px !important;
                    font-size: 13px !important;
                    top: ${headerHeight + 5}px !important;
                }
            }
        `;
        document.head.appendChild(style);
    }
}