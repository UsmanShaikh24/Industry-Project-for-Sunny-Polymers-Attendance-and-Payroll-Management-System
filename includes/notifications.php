<?php
// Notification component for navigation

function getNotificationBadge($user_id) {
    $count = getUnreadNotificationCount($user_id);
    if ($count > 0) {
        return '<span class="notification-badge">' . $count . '</span>';
    }
    return '';
}

function getNotificationDropdown($user_id) {
    $notifications = getNotifications($user_id, 5);
    $unread_count = getUnreadNotificationCount($user_id);
    
    $html = '<div class="notification-dropdown">';
    $html .= '<div class="notification-header">';
    $html .= '<h4>Notifications</h4>';
    if ($unread_count > 0) {
        $html .= '<button class="btn btn-sm btn-primary mark-all-read" onclick="markAllNotificationsAsRead()">Mark All Read</button>';
    }
    $html .= '</div>';
    
    if ($notifications->num_rows > 0) {
        $html .= '<div class="notification-list">';
        while ($notification = $notifications->fetch_assoc()) {
            $read_class = $notification['is_read'] ? 'read' : 'unread';
            $type_class = 'notification-' . $notification['type'];
            
            $html .= '<div class="notification-item ' . $read_class . ' ' . $type_class . '" data-id="' . $notification['id'] . '">';
            $html .= '<div class="notification-content">';
            $html .= '<div class="notification-title">' . htmlspecialchars($notification['title']) . '</div>';
            $html .= '<div class="notification-message">' . htmlspecialchars($notification['message']) . '</div>';
            $html .= '<div class="notification-time">' . date('M j, Y g:i A', strtotime($notification['created_at'])) . '</div>';
            $html .= '</div>';
            if (!$notification['is_read']) {
                $html .= '<button class="mark-read-btn" onclick="markNotificationAsRead(' . $notification['id'] . ')"><i class="fas fa-check"></i></button>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
    } else {
        $html .= '<div class="no-notifications">No notifications</div>';
    }
    
    $html .= '</div>';
    return $html;
}

function getNotificationStyles() {
    return '<style>
.notification-container {
    position: relative;
    display: inline-block;
}

.notification-icon {
    position: relative;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: background-color 0.3s;
}

.notification-icon:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 350px;
    max-height: 400px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    display: none;
    overflow: hidden;
}

.notification-dropdown.show {
    display: block;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #eee;
    background-color: #f8f9fa;
}

.notification-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
    position: relative;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #f0f8ff;
    border-left: 3px solid #007bff;
}

.notification-item.read {
    opacity: 0.7;
}

.notification-content {
    flex: 1;
    margin-right: 10px;
}

.notification-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
    color: #333;
}

.notification-message {
    font-size: 13px;
    color: #666;
    line-height: 1.4;
    margin-bottom: 4px;
}

.notification-time {
    font-size: 11px;
    color: #999;
}

.mark-read-btn {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    padding: 4px;
    border-radius: 3px;
    transition: background-color 0.2s;
}

.mark-read-btn:hover {
    background-color: #e3f2fd;
}

.no-notifications {
    padding: 20px;
    text-align: center;
    color: #666;
    font-style: italic;
}

.notification-info {
    border-left-color: #17a2b8;
}

.notification-success {
    border-left-color: #28a745;
}

.notification-warning {
    border-left-color: #ffc107;
}

.notification-error {
    border-left-color: #dc3545;
}

@media (max-width: 768px) {
    .notification-dropdown {
        width: 300px;
        right: -50px;
    }
}
</style>';
}

function getNotificationScripts() {
    return '<script>
function toggleNotifications() {
    const dropdown = document.querySelector(".notification-dropdown");
    dropdown.classList.toggle("show");
}

function markNotificationAsRead(notificationId) {
    fetch("mark_notification_read.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: "notification_id=" + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the unread styling
            const notificationItem = document.querySelector(`[data-id="${notificationId}"]`);
            if (notificationItem) {
                notificationItem.classList.remove("unread");
                notificationItem.classList.add("read");
                
                // Remove the mark read button
                const markReadBtn = notificationItem.querySelector(".mark-read-btn");
                if (markReadBtn) {
                    markReadBtn.remove();
                }
            }
            
            // Update the badge count
            updateNotificationBadge();
        }
    })
    .catch(error => console.error("Error:", error));
}

function markAllNotificationsAsRead() {
    fetch("mark_all_notifications_read.php", {
        method: "POST"
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mark all notifications as read
            const unreadItems = document.querySelectorAll(".notification-item.unread");
            unreadItems.forEach(item => {
                item.classList.remove("unread");
                item.classList.add("read");
                
                const markReadBtn = item.querySelector(".mark-read-btn");
                if (markReadBtn) {
                    markReadBtn.remove();
                }
            });
            
            // Update the badge count
            updateNotificationBadge();
        }
    })
    .catch(error => console.error("Error:", error));
}

function updateNotificationBadge() {
    fetch("get_notification_count.php")
    .then(response => response.json())
    .then(data => {
        const badge = document.querySelector(".notification-badge");
        if (data.count > 0) {
            if (badge) {
                badge.textContent = data.count;
            } else {
                // Create new badge if it\'s not exist
                const icon = document.querySelector(".notification-icon");
                if (icon) {
                    const newBadge = document.createElement("span");
                    newBadge.className = "notification-badge";
                    newBadge.textContent = data.count;
                    icon.appendChild(newBadge);
                }
            }
        } else {
            if (badge) {
                badge.remove();
            }
        }
    })
    .catch(error => console.error("Error:", error));
}

// Close dropdown when clicking outside
document.addEventListener("click", function(event) {
    const notificationContainer = document.querySelector(".notification-container");
    const dropdown = document.querySelector(".notification-dropdown");
    
    if (notificationContainer && !notificationContainer.contains(event.target)) {
        dropdown.classList.remove("show");
    }
});

// Auto-refresh notifications every 30 seconds
setInterval(updateNotificationBadge, 30000);
</script>';
}
?> 