<?php
require_once('connect.php');

// Initialize response array
$response = ['success' => false, 'jobs' => [], 'message' => ''];

try {
    // Get parameters
    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    $view = isset($_POST['view']) ? $_POST['view'] : 'active';
    $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
    
    // Start building the query
    $query = "
        SELECT 
            j.id,
            j.job_year,
            j.job_id,
            CONCAT(j.job_year, '-', LPAD(j.job_id, 4, '0')) as job_number,
            j.division,
            j.union_type as union_name,
            j.title,
            j.status,
            j.close_date,
            j.street,
            j.city,
            j.province,
            j.postal_code,
            j.distance_km,
            COALESCE(j.main_contract_amount, 0) as main_contract_amount,
            j.created_date,
            j.is_archived,
            gc.company_name as gc_name,
            gc.id as gc_id
        FROM jobs j
        LEFT JOIN general_contractors gc ON j.assigned_general_contractor = gc.id
        WHERE j.job_year = ? AND j.is_archived = ?
    ";
    
    $params = [$year, ($view === 'archived' ? 1 : 0)];
    $types = "ii";  // Two integers: year and is_archived
    
    // Add search filter if provided
    if (!empty($filters['search'])) {
        $searchTerm = "%" . $filters['search'] . "%";
        $query .= " AND (
            CONCAT(j.job_year, '-', LPAD(j.job_id, 4, '0')) LIKE ? OR 
            j.division LIKE ? OR 
            j.title LIKE ? OR 
            j.status LIKE ? OR 
            j.street LIKE ? OR 
            j.city LIKE ? OR 
            gc.company_name LIKE ?
        )";
        $params = array_merge($params, array_fill(0, 7, $searchTerm));
        $types .= str_repeat("s", 7);
    }
    
    // Add quick filters
    if (!empty($filters['quickFilters'])) {
        foreach ($filters['quickFilters'] as $filter) {
            if ($filter === 'closing-soon') {
                $query .= " AND j.close_date >= CURRENT_DATE() AND j.close_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)";
            } else {
                $query .= " AND j.division = ?";
                $params[] = ucfirst($filter);
                $types .= "s";
            }
        }
    }
    
    // Prepare and execute the query
    $stmt = $db_connect->prepare($query);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate quote value (sum of scope items)
        $quote_value = 0;
        
        // Check if scope_items table exists in the database and has data for this job
        $scope_check_query = "SHOW TABLES LIKE 'scope_items'";
        $scope_check_result = $db_connect->query($scope_check_query);
        
        if ($scope_check_result->num_rows > 0) {
            $quote_query = "
                SELECT COALESCE(SUM(quote_amount), 0) as quote_value 
                FROM scope_items 
                WHERE job_year = ? AND job_id = ?
            ";
            $quote_stmt = $db_connect->prepare($quote_query);
            $quote_stmt->bind_param("ii", $row['job_year'], $row['job_id']);
            $quote_stmt->execute();
            $quote_result = $quote_stmt->get_result();
            $quote_value = $quote_result->fetch_assoc()['quote_value'];
        }
        
        // Initialize change orders and time management values to 0
        $co_value = 0;
        $tm_total = 0;
        
        // Check if change_orders table exists in the database
        $co_check_query = "SHOW TABLES LIKE 'change_orders'";
        $co_check_result = $db_connect->query($co_check_query);
        
        if ($co_check_result->num_rows > 0) {
            // Calculate change orders total
            $co_query = "
                SELECT COALESCE(SUM(value), 0) as co_value 
                FROM change_orders 
                WHERE job_id = ?
            ";
            $co_stmt = $db_connect->prepare($co_query);
            $co_stmt->bind_param("i", $row['id']);
            $co_stmt->execute();
            $co_result = $co_stmt->get_result();
            $co_value = $co_result->fetch_assoc()['co_value'];
        }
        
        // Check if time_management_reports table exists in the database
        $tm_check_query = "SHOW TABLES LIKE 'time_management_reports'";
        $tm_check_result = $db_connect->query($tm_check_query);
        
        if ($tm_check_result->num_rows > 0) {
            // Calculate T&M reports total
            $tm_query = "
                SELECT 
                    tmr.id,
                    tmr.overhead,
                    tmr.labour_markup,
                    tmr.cost_markup,
                    (
                        SELECT COALESCE(SUM(
                            (friday_hours + saturday_hours + sunday_hours + monday_hours + 
                             tuesday_hours + wednesday_hours + thursday_hours) * rate
                        ), 0)
                        FROM time_management_labour_entries
                        WHERE time_management_report = tmr.id
                    ) as labour_total,
                    (
                        SELECT COALESCE(SUM(quantity * unit_cost), 0)
                        FROM time_management_cost_entries
                        WHERE time_management_report = tmr.id
                    ) as cost_total
                FROM time_management_reports tmr
                WHERE tmr.job_id = ? AND tmr.status = 'approved'
            ";
            $tm_stmt = $db_connect->prepare($tm_query);
            $tm_stmt->bind_param("i", $row['id']);
            $tm_stmt->execute();
            $tm_result = $tm_stmt->get_result();
            
            while ($tm_row = $tm_result->fetch_assoc()) {
                $labour_with_markup = $tm_row['labour_total'] * (1 + ($tm_row['labour_markup'] / 100));
                $cost_with_markup = $tm_row['cost_total'] * (1 + ($tm_row['cost_markup'] / 100));
                $tm_total += $labour_with_markup + $cost_with_markup + $tm_row['overhead'];
            }
        }
        
        // Calculate total contract value
        $total_contract_value = $row['main_contract_amount'] + $co_value + $tm_total;
        
        // Add calculated values to the row
        $row['quote_value'] = $quote_value;
        $row['total_contract_value'] = $total_contract_value;
        
        $jobs[] = $row;
    }
    
    $response['success'] = true;
    $response['jobs'] = $jobs;
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>