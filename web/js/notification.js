// notification.js
function showNotification(message, type) {
    // Remove any existing notifications first
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notification => notification.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'custom-notification';
    notification.style.position = 'fixed';
    notification.style.top = '19%';
    notification.style.left = '50%';
    notification.style.transform = 'translate(-50%, -50%)';
    notification.style.padding = '20px 30px';
    notification.style.borderRadius = '8px';
    notification.style.color = 'white';
    notification.style.zIndex = '10000';
    notification.style.fontWeight = 'bold';
    notification.style.textAlign = 'center';
    notification.style.boxShadow = '0 5px 15px rgba(0,0,0,0.3)';
    notification.style.minWidth = '300px';
    notification.style.maxWidth = '80%';
    notification.style.cursor = 'pointer';
    notification.style.transition = 'opacity 0.3s ease';

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
    icon.style.fontSize = '20px';

    if (type === 'success') {
        icon.textContent = '✓';
    } else {
        icon.textContent = '✗';
    }

    const text = document.createElement('span');
    text.textContent = message;

    notification.appendChild(icon);
    notification.appendChild(text);
    document.body.appendChild(notification);

    // Add click to remove functionality
    notification.addEventListener('click', function() {
        this.style.opacity = '0';
        setTimeout(() => {
            if (this.parentNode) {
                this.parentNode.removeChild(this);
            }
        }, 300); // Match transition duration
    });

    // Remove notification after 3 seconds
    const timeoutId = setTimeout(() => {
        notification.style.opacity = '0';
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
}