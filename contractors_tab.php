<?php
require_once('connect.php');
?>
<div class="contractors-container">
    <div class="contractors-header">
        <div class="view-controls">
            <button class="view-btn active" data-view="cards">
                <i class="fas fa-th-large"></i> Cards
            </button>
            <button class="view-btn" data-view="table">
                <i class="fas fa-table"></i> Table
            </button>
        </div>
        <button class="add-gc-btn">
            <i class="fas fa-plus"></i> Add New GC
        </button>
    </div>
    
    <div class="contractors-content">
        <!-- Cards View -->
        <div class="contractors-cards active">
            <!-- Cards will be loaded here dynamically -->
        </div>
        
        <!-- Table View -->
        <div class="contractors-table">
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Contact Info</th>
                        <th>Jobs Quoted</th>
                        <th>Close Ratio</th>
                        <th>Total Value</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Table rows will be loaded here dynamically -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit/Add Contractor Modal -->
<div class="modal" id="contractorModal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle">Add General Contractor</h2>
        <form id="contractorForm">
            <input type="hidden" name="contractor_id" id="contractor_id">
            
            <div class="form-group">
                <label>Company Name</label>
                <input type="text" name="company_name" required>
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="street" placeholder="Street" required>
                <div class="form-row">
                    <input type="text" name="city" placeholder="City" required>
                    <input type="text" name="province" placeholder="Province" required>
                </div>
                <input type="text" name="postal_code" placeholder="Postal Code" required>
            </div>
            
            <div class="form-group">
                <label>Contacts</label>
                <div id="contacts-container">
                    <!-- Contact fields will be added here -->
                </div>
                <button type="button" class="add-contact-btn">
                    <i class="fas fa-plus"></i> Add Contact
                </button>
            </div>
            
            <div class="form-actions">
                <button type="button" class="cancel-btn">Cancel</button>
                <button type="submit" class="save-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-content">
        <h2>Delete Contractor?</h2>
        <p>Are you sure you want to delete this contractor? This action cannot be undone.</p>
        <div class="form-actions">
            <button class="cancel-btn">Cancel</button>
            <button class="delete-btn">Delete</button>
        </div>
    </div>
</div>
