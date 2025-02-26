<?php
require_once('connect.php');
?>
<div class="master-job-list-container">
    <div class="job-list-header">
        <div class="view-controls">
            <button class="job-tab-btn active" data-view="active">Active Jobs</button>
            <button class="job-tab-btn" data-view="archived">Archived Jobs</button>
        </div>
        <div class="header-controls">
            <select id="yearSelect" class="year-select">
                <?php
                $currentYear = date('Y');
                for($year = $currentYear; $year >= $currentYear - 5; $year--) {
                    echo "<option value='$year'>$year</option>";
                }
                ?>
            </select>
            <button class="sort-btn" id="sortBtn">
                <i class="fas fa-sort"></i>
            </button>
            <button class="import-jobs-btn">
                <i class="fas fa-file-import"></i> Import Jobs
            </button>
            <button class="add-job-btn">
                <i class="fas fa-plus"></i> New Job
            </button>
        </div>
    </div>

    <div class="quick-filters">
        <button class="filter-btn" data-filter="closing-soon">
            <i class="far fa-clock"></i> Closing Soon
        </button>
        <button class="filter-btn" data-filter="flooring">Flooring</button>
        <button class="filter-btn" data-filter="concrete">Concrete</button>
        <button class="filter-btn" data-filter="blasting">Blasting</button>
        <button class="filter-btn" data-filter="fireproofing">Fireproofing</button>
        
        <div class="value-range-filter">
            <select id="quoteValueRange">
                <option value="">Quote Value</option>
                <option value="0-50000">$0 - $50,000</option>
                <option value="50000-100000">$50,000 - $100,000</option>
                <option value="100000+">$100,000+</option>
            </select>
        </div>
        <div class="value-range-filter">
            <select id="contractValueRange">
                <option value="">Contract Value</option>
                <option value="0-50000">$0 - $50,000</option>
                <option value="50000-100000">$50,000 - $100,000</option>
                <option value="100000+">$100,000+</option>
            </select>
        </div>
    </div>

    <div class="search-section">
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="jobSearch" placeholder="Search jobs..." class="search-input">
        </div>
        <button class="advanced-filter-btn" id="showFilters">
            <i class="fas fa-filter"></i> Filters
        </button>
    </div>

    <div class="jobs-content">
        <div class="jobs-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                        <th style="width: 80px;">ACTIONS</th>
                        <th>JOB NUMBER</th>
                        <th>DIVISION</th>
                        <th>UNION</th>
                        <th>TITLE</th>
                        <th>STATUS</th>
                        <th>CLOSE DATE</th>
                        <th>QUOTE VALUE</th>
                        <th>TOTAL CONTRACT VALUE</th>
                        <th>ADDRESS</th>
                        <th>TRAVEL</th>
                        <th>GC</th>
                        <th>CREATED</th>
                    </tr>
                </thead>
                <tbody id="jobsTableBody">
                    <!-- Jobs will be loaded here dynamically -->
                    <tr>
                        <td colspan="14" style="text-align: center; padding: 2rem;">
                            Loading jobs...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- New Job Modal -->
    <div class="modal" id="jobModal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Create New Job</h2>
            <form id="jobForm">
                <input type="hidden" name="job_id" id="job_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Division</label>
                        <select name="division" required>
                            <option value="">Select Division</option>
                            <option value="Flooring">Flooring</option>
                            <option value="Concrete">Concrete</option>
                            <option value="Blasting">Blasting</option>
                            <option value="Fireproofing">Fireproofing</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Union</label>
                        <select name="union" required>
                            <option value="">Select Union</option>
                            <option value="Local 506">Local 506</option>
                            <option value="Local 837">Local 837</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="Quoting">Quoting</option>
                        <option value="Quote Issued">Quote Issued</option>
                        <option value="Current PO">Current PO</option>
                        <option value="Completed">Completed</option>
                        <option value="Unsuccessful">Unsuccessful</option>
                        <option value="No Bid">No Bid</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Street Address</label>
                    <input type="text" name="street" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" required>
                    </div>
                    <div class="form-group">
                        <label>Province</label>
                        <input type="text" name="province" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" required>
                    </div>
                    <div class="form-group">
                        <label>Distance from Shop (km)</label>
                        <input type="number" name="distance_km" step="0.1" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="save-btn">Create Job</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Jobs Modal -->
    <div class="modal" id="importModal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Bulk Import Jobs</h2>
            
            <div class="import-section">
                <button id="downloadTemplate" class="download-template-btn">
                    <i class="fas fa-download"></i> Download Import Template
                </button>
                
                <div class="upload-area" id="uploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag and drop your file here or click to browse</p>
                    <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display: none">
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="cancel-btn">Cancel</button>
                <button type="button" class="upload-btn" disabled>Upload</button>
            </div>
        </div>
    </div>

    <!-- Advanced Filters Panel -->
    <div class="filters-panel" id="filtersPanel">
        <div class="filters-header">
            <h3>Filter Jobs</h3>
            <div class="filters-controls">
                <button class="clear-filters-btn">Clear All</button>
                <button class="close-filters-btn">&times;</button>
            </div>
        </div>
        
        <div class="filters-content">
            <!-- Filter sections will be loaded dynamically -->
        </div>
    </div>

    <script>
    // Immediate execution fix for modals
    $(function() {
        // Move modals to body for proper z-index stacking
        $('#jobModal, #importModal, #filtersPanel').appendTo('body');
        
        // Add specific CSS to ensure modals display correctly
        $("<style>")
            .prop("type", "text/css")
            .html(`
                .modal.active { 
                    display: flex !important; 
                    z-index: 10000 !important;
                }
                .modal-content {
                    background: white !important;
                    border-radius: 8px !important;
                    padding: 2rem !important;
                    width: 90% !important;
                    max-width: 600px !important;
                }
                .add-job-btn, .import-jobs-btn {
                    cursor: pointer !important;
                }
            `)
            .appendTo("head");
        
        // Direct binding of critical buttons
        $('.add-job-btn').on('click', function(e) {
            e.preventDefault();
            console.log('Add job button clicked (inline handler)');
            $('#jobModal').addClass('active').css('display', 'flex');
        });
        
        $('.import-jobs-btn').on('click', function(e) {
            e.preventDefault();
            console.log('Import button clicked (inline handler)');
            $('#importModal').addClass('active').css('display', 'flex');
        });
        
        $('.modal .close, .modal .cancel-btn').on('click', function() {
            console.log('Close button clicked (inline handler)');
            $(this).closest('.modal').removeClass('active').css('display', 'none');
        });

        // Forcibly check if modal exists in DOM
        console.log('Job modal exists:', $('#jobModal').length);
        console.log('Import modal exists:', $('#importModal').length);
        
        // Alert if any modal is missing
        if (!$('#jobModal').length || !$('#importModal').length) {
            console.error('Modal elements not found!');
        }
    });
    </script>
</div>