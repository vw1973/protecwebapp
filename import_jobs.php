<?php
require_once('connect.php');

$response = ['success' => false, 'message' => '', 'errors' => []];

try {
    if (!isset($_FILES['file'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    if ($ext !== 'csv') {
        throw new Exception('Invalid file type. Please upload a CSV file.');
    }

    // Open the uploaded file
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        throw new Exception('Failed to open file');
    }
    
    // Read headers
    $headers = fgetcsv($handle);
    
    // Expected headers
    $expected_headers = ['Job Number', 'Division', 'Union', 'Title', 'Status', 'Street', 'City', 'Province', 'Postal Code', 'Distance (km)'];
    
    // Validate headers
    if (count(array_intersect($headers, $expected_headers)) < count($expected_headers)) {
        throw new Exception('Invalid file format. Headers do not match expected format.');
    }
    
    // Start transaction
    $db_connect->begin_transaction();
    
    $year = date('Y'); // Current year for job numbers
    
    // Get the next available job_id for this year
    $stmt = $db_connect->prepare("
        SELECT COALESCE(MAX(job_id), 0) as last_id 
        FROM jobs 
        WHERE job_year = ?
    ");
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $next_job_id = $result->fetch_assoc()['last_id'] + 1;
    
    // Prepare insert statement
    $insert_stmt = $db_connect->prepare("
        INSERT INTO jobs (
            job_year,
            job_id,
            division,
            union_type,
            title,
            status,
            street,
            city,
            province,
            postal_code,
            distance_km,
            created_date,
            is_archived
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)
    ");
    
    $successful_imports = 0;
    $errors = [];
    $row_number = 1; // Start from row 1 (after headers)

    while (($row = fgetcsv($handle)) !== false) {
        $row_number++;
        
        try {
            // Skip empty rows
            if (empty($row) || count($row) < 3) {
                continue;
            }
            
            // Validate required fields (Division, Union, Title)
            if (empty($row[1]) || empty($row[2]) || empty($row[3])) {
                throw new Exception("Missing required fields");
            }
            
            // Validate division
            $valid_divisions = ['Flooring', 'Concrete', 'Blasting', 'Fireproofing'];
            if (!in_array($row[1], $valid_divisions)) {
                throw new Exception("Invalid division: must be one of " . implode(", ", $valid_divisions));
            }
            
            // Validate union
            $valid_unions = ['Local 506', 'Local 837'];
            if (!in_array($row[2], $valid_unions)) {
                throw new Exception("Invalid union: must be one of " . implode(", ", $valid_unions));
            }
            
            // Validate status if provided
            if (!empty($row[4])) {
                $valid_statuses = ['Quoting', 'Quote Issued', 'Current PO', 'Completed', 'Unsuccessful', 'No Bid'];
                if (!in_array($row[4], $valid_statuses)) {
                    throw new Exception("Invalid status: must be one of " . implode(", ", $valid_statuses));
                }
            }
            
            // Insert job
            $insert_stmt->bind_param('iissssssssd',
                $year,
                $next_job_id,
                $row[1], // Division
                $row[2], // Union
                $row[3], // Title
                $row[4] ?? 'Quoting', // Status
                $row[5] ?? '', // Street
                $row[6] ?? '', // City
                $row[7] ?? '', // Province
                $row[8] ?? '', // Postal Code
                floatval($row[9] ?? 0) // Distance
            );
            
            if ($insert_stmt->execute()) {
                $successful_imports++;
                $next_job_id++;
            } else {
                throw new Exception("Failed to insert row: " . $db_connect->error);
            }
            
        } catch (Exception $e) {
            $errors[] = "Row " . $row_number . ": " . $e->getMessage();
        }
    }
    
    fclose($handle);
    
    if ($successful_imports > 0) {
        $db_connect->commit();
        $response['success'] = true;
        $response['message'] = "Successfully imported $successful_imports jobs";
    } else {
        $db_connect->rollback();
        throw new Exception("No jobs were imported");
    }
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    
} catch (Exception $e) {
    if ($db_connect->inTransaction()) {
        $db_connect->rollback();
    }
    $response['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>