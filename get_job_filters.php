<?php
require_once('connect.php');

$response = ['success' => false, 'filters' => [], 'message' => ''];

try {
    // Get all tags grouped by category
    $query = "
        SELECT DISTINCT 
            category,
            GROUP_CONCAT(DISTINCT tag ORDER BY tag ASC) as tags
        FROM job_tags 
        GROUP BY category 
        ORDER BY category ASC
    ";
    
    $result = $db_connect->query($query);
    
    if ($result) {
        $filters = [];
        
        while ($row = $result->fetch_assoc()) {
            // Split the concatenated tags back into an array
            $tags = explode(',', $row['category']);
            $tagList = explode(',', $row['tags']);
            
            // Add to filters array
            $filters[$row['category']] = $tagList;
        }
        
        // Add static filters that aren't in the job_tags table
        $filters['Division'] = ['Flooring', 'Concrete', 'Blasting', 'Fireproofing'];
        $filters['Union'] = ['Local 506', 'Local 837'];
        $filters['Status'] = [
            'Quoting',
            'Quote Issued',
            'Current PO',
            'Completed',
            'Unsuccessful',
            'No Bid'
        ];
        
        // Additional value range filters if needed
        $filters['Quote Value'] = [
            '0-50000' => '$0 - $50,000',
            '50000-100000' => '$50,000 - $100,000',
            '100000+' => '$100,000+'
        ];
        
        $filters['Contract Value'] = [
            '0-50000' => '$0 - $50,000',
            '50000-100000' => '$50,000 - $100,000',
            '100000+' => '$100,000+'
        ];
        
        $response['success'] = true;
        $response['filters'] = $filters;
    } else {
        throw new Exception("Failed to fetch filters");
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>