<?php
require_once('connect.php');

$response = ['success' => false, 'message' => ''];

// Validate and sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $db_connect->begin_transaction();
        
        try {
            $company_name = sanitizeInput($_POST['company_name']);
            $street = sanitizeInput($_POST['street']);
            $city = sanitizeInput($_POST['city']);
            $province = sanitizeInput($_POST['province']);
            $postal_code = sanitizeInput($_POST['postal_code']);
            
            if ($action === 'add') {
                $stmt = $db_connect->prepare("INSERT INTO general_contractors (company_name, street, city, province, postal_code) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('sssss', $company_name, $street, $city, $province, $postal_code);
                $stmt->execute();
                $contractor_id = $db_connect->insert_id;
            } else {
                $contractor_id = intval($_POST['id']);
                $stmt = $db_connect->prepare("UPDATE general_contractors SET company_name=?, street=?, city=?, province=?, postal_code=? WHERE id=?");
                $stmt->bind_param('sssssi', $company_name, $street, $city, $province, $postal_code, $contractor_id);
                $stmt->execute();
                
                // Delete existing contacts to replace with new ones
                $db_connect->query("DELETE FROM general_contractors_contacts WHERE general_contractor = $contractor_id");
            }
            
            // Process contacts
            $contacts = json_decode($_POST['contacts'], true);
            $contact_stmt = $db_connect->prepare("INSERT INTO general_contractors_contacts (general_contractor, name, position, email, phone) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($contacts as $contact) {
                $name = sanitizeInput($contact['name']);
                $position = sanitizeInput($contact['position']);
                $email = sanitizeInput($contact['email']);
                $phone = sanitizeInput($contact['phone']);
                
                $contact_stmt->bind_param('issss', $contractor_id, $name, $position, $email, $phone);
                $contact_stmt->execute();
            }
            
            $db_connect->commit();
            $response = ['success' => true, 'message' => $action === 'add' ? 'Contractor added successfully' : 'Contractor updated successfully'];
            
        } catch (Exception $e) {
            $db_connect->rollback();
            $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    } elseif ($action === 'delete') {
        try {
            $contractor_id = intval($_POST['id']);
            $stmt = $db_connect->prepare("DELETE FROM general_contractors WHERE id = ?");
            $stmt->bind_param('i', $contractor_id);
            $stmt->execute();
            
            $response = ['success' => true, 'message' => 'Contractor deleted successfully'];
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>