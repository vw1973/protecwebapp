<?php
require_once('connect.php');

// Define all possible tabs with their properties
$all_tabs = [
    'overview' => [
        'name' => 'Dashboard',
        'icon' => 'fas fa-chart-line',
        'element' => 'overview_tab',
        'roles' => ['ADMIN']
    ],
    'jobs' => [
        'name' => 'Master Job List',
        'icon' => 'fas fa-clipboard-list',
        'element' => 'jobs_tab',
        'roles' => ['*'] // Available to all roles
    ],
    'schedule' => [
        'name' => 'Master Schedule',
        'icon' => 'fas fa-calendar-alt',
        'element' => 'schedule_tab',
        'roles' => ['*']
    ],
    'contractors' => [
        'name' => 'General Contractors',
        'icon' => 'fas fa-hard-hat',
        'element' => 'contractors_tab',
        'roles' => ['*']
    ],
    'equipment' => [
        'name' => 'Vehicle & Equipment',
        'icon' => 'fas fa-truck',
        'element' => 'equipment_tab',
        'roles' => ['*']
    ],
    'settings' => [
        'name' => 'Settings',
        'icon' => 'fas fa-cog',
        'element' => 'settings_tab',
        'roles' => ['ADMIN']
    ]
];

// Get user's role
$stmt = $db_connect->prepare("SELECT role FROM roles WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user_role = $result->fetch_assoc()['role'];

// Get user's permissions
$stmt = $db_connect->prepare("SELECT element FROM role_permissions WHERE role = ?");
$stmt->bind_param("s", $user_role);
$stmt->execute();
$result = $stmt->get_result();
$permissions = [];
while ($row = $result->fetch_assoc()) {
    $permissions[] = $row['element'];
}

// Build HTML for authorized tabs
$html = '';
foreach ($all_tabs as $tab_id => $tab) {
    // Check if user has permission for this tab
    if (in_array($tab['element'], $permissions) || in_array('*', $tab['roles'])) {
        $html .= sprintf(
            '<div class="nav-tab" data-tab="%s">
                <i class="%s"></i>
                %s
            </div>',
            htmlspecialchars($tab_id),
            htmlspecialchars($tab['icon']),
            htmlspecialchars($tab['name'])
        );
    }
}

echo $html;