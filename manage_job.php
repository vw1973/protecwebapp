<?php
require_once('connect.php');

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        // Sanitize inputs
        function sanitize($input) {
            return htmlspecialchars(strip_tags(trim($input)));
        }
        
        if ($action === 'add' || $action === 'edit') {
            $db_connect->begin_transaction();
            
            try {
                $division = sanitize($_POST['division']);
                $union = sanitize($_POST['union']);
                $title = sanitize($_POST['title']);
                $status = sanitize($_POST['status']);
                $street = sanitize($_POST['street']);
                $city = sanitize($_POST['city']);
                $province = sanitize($_POST['province']);
                $postal_code = sanitize($_POST['postal_code']);
                $distance_km = floatval($_POST['distance_km']);
                
                if ($action === 'add') {
                    // Generate new job number
                    $year = date('Y'); // Current year
                    
                    // Get the next available job_id for this year
                    $stmt = $db_connect->prepare("
                        SELECT COALESCE(MAX(job_id), 0) + 1 as next_id 
                        FROM jobs 
                        WHERE job_year = ?
                    ");
                    $stmt->bind_param('i', $year);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $job_id = $result->fetch_assoc()['next_id'];
                    
                    // Insert new job
                    $stmt = $db_connect->prepare("
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
                    
                    $stmt->bind_param('iissssssssd',
                        $year,
                        $job_id,
                        $division,
                        $union,
                        $title,
                        $status,
                        $street,
                        $city,
                        $province,
                        $postal_code,
                        $distance_km
                    );
                    
                    $stmt->execute();
                    $inserted_id = $db_connect->insert_id;
                    
                    $response['message'] = "Job created successfully";
                    $response['job_number'] = sprintf("%d-%04d", $year, $job_id);
                    
                } else { // Edit existing job
                    $job_id = intval($_POST['job_id']);
                    
                    $stmt = $db_connect->prepare("
                        UPDATE jobs SET 
                            division = ?,
                            union_type = ?,
                            title = ?,
                            status = ?,
                            street = ?,
                            city = ?,
                            province = ?,
                            postal_code = ?,
                            distance_km = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->bind_param('ssssssssdi',
                        $division,
                        $union,
                        $title,
                        $status,
                        $street,
                        $city,
                        $province,
                        $postal_code,
                        $distance_km,
                        $job_id
                    );
                    
                    $stmt->execute();
                    
                    $response['message'] = "Job updated successfully";
                }
                
                $db_connect->commit();
                $response['success'] = true;
                
            } catch (Exception $e) {
                $db_connect->rollback();
                throw $e;
            }
        }
        // Archive/Unarchive job
        else if ($action === 'archive' || $action === 'unarchive') {
            $job_id = intval($_POST['job_id']);
            $is_archived = ($action === 'archive') ? 1 : 0;
            
            $stmt = $db_connect->prepare("
                UPDATE jobs 
                SET is_archived = ? 
                WHERE id = ?
            ");
            
            $stmt->bind_param('ii', $is_archived, $job_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = $action === 'archive' ? 
                    "Job archived successfully" : 
                    "Job restored successfully";
            } else {
                throw new Exception("Failed to {$action} job");
            }
        }
        // Delete job
        else if ($action === 'delete') {
            $job_id = intval($_POST['job_id']);
            
            $stmt = $db_connect->prepare("DELETE FROM jobs WHERE id = ?");
            $stmt->bind_param('i', $job_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Job deleted successfully";
            } else {
                throw new Exception("Failed to delete job");
            }
        }
        else {
            throw new Exception("Invalid action");
        }
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>