<?php
require_once('connect.php');

// Get all contractors with their contacts and statistics
$query = "
    SELECT 
        gc.*,
        GROUP_CONCAT(
            DISTINCT CONCAT_WS('|',
                gcc.name,
                gcc.position,
                gcc.email,
                gcc.phone
            )
            SEPARATOR '||'
        ) as contacts,
        COUNT(DISTINCT jq.id) as jobs_quoted,
        COUNT(DISTINCT ja.id) as jobs_awarded,
        COALESCE(SUM(DISTINCT si.quote_amount), 0) as total_value
    FROM general_contractors gc
    LEFT JOIN general_contractors_contacts gcc ON gc.id = gcc.general_contractor
    LEFT JOIN jobs_quoted jq ON gc.id = jq.general_contractor
    LEFT JOIN jobs_awarded ja ON gc.id = ja.general_contractor
    LEFT JOIN jobs j ON (ja.job_year = j.job_year AND ja.job_id = j.job_id)
    LEFT JOIN scope_items si ON (j.job_year = si.job_year AND j.job_id = si.job_id)
    GROUP BY gc.id";

$result = $db_connect->query($query);
$contractors = [];

while ($row = $result->fetch_assoc()) {
    // Process contacts
    $contacts = [];
    if ($row['contacts']) {
        $contactsList = explode('||', $row['contacts']);
        foreach ($contactsList as $contact) {
            list($name, $position, $email, $phone) = explode('|', $contact);
            $contacts[] = [
                'name' => $name,
                'position' => $position,
                'email' => $email,
                'phone' => $phone
            ];
        }
    }
    
    // Calculate YTD value
    $ytd_query = "
        SELECT COALESCE(SUM(si.quote_amount), 0) as ytd_value
        FROM jobs_awarded ja
        JOIN jobs j ON (ja.job_year = j.job_year AND ja.job_id = j.job_id)
        JOIN scope_items si ON (j.job_year = si.job_year AND j.job_id = si.job_id)
        WHERE ja.general_contractor = ? 
        AND YEAR(j.close_date) = YEAR(CURRENT_DATE)
        AND j.status IN ('Current PO', 'Completed')";
    
    $stmt = $db_connect->prepare($ytd_query);
    $stmt->bind_param('i', $row['id']);
    $stmt->execute();
    $ytd_result = $stmt->get_result()->fetch_assoc();
    
    // Calculate close ratio
    $ratio_query = "
        SELECT 
            (SELECT COUNT(*) 
             FROM jobs j
             JOIN jobs_awarded ja ON (j.job_year = ja.job_year AND j.job_id = ja.job_id)
             WHERE ja.general_contractor = ? 
             AND j.status IN ('Current PO', 'Completed')) / 
            (SELECT COUNT(*) 
             FROM jobs j
             JOIN jobs_quoted jq ON (j.job_year = jq.job_year AND j.job_id = jq.job_id)
             WHERE jq.general_contractor = ? 
             AND j.status != 'No Bid') * 100 as close_ratio";
    
    $stmt = $db_connect->prepare($ratio_query);
    $stmt->bind_param('ii', $row['id'], $row['id']);
    $stmt->execute();
    $ratio_result = $stmt->get_result()->fetch_assoc();
    
    // Calculate average project value
    $avg_value = $row['jobs_awarded'] > 0 ? $row['total_value'] / $row['jobs_awarded'] : 0;
    
    $contractors[] = [
        'id' => $row['id'],
        'company_name' => $row['company_name'],
        'street' => $row['street'],
        'city' => $row['city'],
        'province' => $row['province'],
        'postal_code' => $row['postal_code'],
        'contacts' => $contacts,
        'stats' => [
            'jobs_quoted' => $row['jobs_quoted'],
            'jobs_awarded' => $row['jobs_awarded'],
            'close_ratio' => number_format($ratio_result['close_ratio'] ?? 0, 1),
            'total_value' => number_format($row['total_value'], 2),
            'ytd_value' => number_format($ytd_result['ytd_value'], 2),
            'avg_project' => number_format($avg_value, 2)
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode(['contractors' => $contractors]);
?>