// Namespace the job list functionality to prevent conflicts
var JobListModule = (function() {
    // Private variables
    var jobs = [];
    var currentJobView = 'active';  // Renamed to avoid conflicts
    var currentJobFilters = {
        search: '',
        quickFilters: [],
        valueRanges: {
            quote: '',
            contract: ''
        },
        advancedFilters: {}
    };

    // Initialize jobs tab
    function initJobList() {
        console.log('Initializing jobs list');
        loadJobData();
        initJobEventListeners();
    }

    // Load all jobs
    function loadJobData() {
        const year = $('#yearSelect').val() || new Date().getFullYear();
        const params = {
            year: year,
            view: currentJobView,
            filters: currentJobFilters
        };

        $.post('get_jobs.php', params, function(response) {
            console.log('Jobs response:', response);
            if (response.success) {
                jobs = response.jobs || [];
                renderJobList();
            } else {
                console.error('Error loading jobs:', response.message);
                $('#jobsTableBody').html(`
                    <tr>
                        <td colspan="14" style="text-align: center; padding: 2rem;">
                            Error loading jobs: ${response.message || 'Unknown error'}
                        </td>
                    </tr>
                `);
            }
        }).fail(function(error) {
            console.error('AJAX error:', error);
            $('#jobsTableBody').html(`
                <tr>
                    <td colspan="14" style="text-align: center; padding: 2rem;">
                        Failed to connect to server. Please try again.
                    </td>
                </tr>
            `);
        });
    }

    // Initialize event listeners
    function initJobEventListeners() {
        console.log('Setting up job event listeners');
        
        // Tab switching
        $('.job-tab-btn').off('click').on('click', function() {
            console.log('Job tab clicked:', $(this).data('view'));
            $('.job-tab-btn').removeClass('active');
            $(this).addClass('active');
            currentJobView = $(this).data('view');
            loadJobData();
        });

        // Year selection
        $('#yearSelect').off('change').on('change', function() {
            console.log('Year changed:', $(this).val());
            loadJobData();
        });

        // Quick filters
        $('.filter-btn').off('click').on('click', function() {
            console.log('Filter clicked:', $(this).data('filter'));
            $(this).toggleClass('active');
            updateJobFilters();
        });

        // Value range filters
        $('#quoteValueRange, #contractValueRange').off('change').on('change', function() {
            console.log('Value range changed');
            updateJobFilters();
        });

        // Search input with debounce
        let searchTimeout;
        $('#jobSearch').off('input').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                console.log('Search input:', $(this).val());
                currentJobFilters.search = $(this).val();
                loadJobData();
            }, 300);
        });

        // Advanced filters
        $('#showFilters').off('click').on('click', function() {
            console.log('Show filters clicked');
            loadAdvancedFilters();
            $('#filtersPanel').addClass('active');
        });

        $('.close-filters-btn').off('click').on('click', function() {
            console.log('Close filters clicked');
            $('#filtersPanel').removeClass('active');
        });

        $('.clear-filters-btn').off('click').on('click', function() {
            console.log('Clear filters clicked');
            clearAllJobFilters();
        });

        // Job form submission
        $('#jobForm').off('submit').on('submit', function(e) {
            e.preventDefault();
            console.log('Job form submitted');
            saveJob();
        });

        // Modal controls and buttons
        setupModalHandlers();
        
        // File upload
        setupFileUpload();

        // Select all checkbox
        $('#selectAll').off('change').on('change', function() {
            console.log('Select all changed:', $(this).prop('checked'));
            $('.job-checkbox').prop('checked', $(this).prop('checked'));
        });

        console.log('Job event listeners set up');
    }

    // Set up modal handlers
    function setupModalHandlers() {
        console.log('Setting up modal handlers');
        
        // Add job button
        $('.add-job-btn').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('Add job button clicked');
            openJobModal();
        });

        // Import jobs button
        $('.import-jobs-btn').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('Import jobs button clicked');
            openImportModal();
        });

        // Modal close buttons
        $('.modal .close, .modal .cancel-btn').off('click').on('click', function() {
            console.log('Modal close button clicked');
            $(this).closest('.modal').removeClass('active');
        });

        // Upload button
        $('.upload-btn').off('click').on('click', function() {
            console.log('Upload button clicked');
            uploadJob();
        });

        // Edit, archive, restore and delete buttons (using event delegation)
        $('#jobsTableBody').off('click', '.edit-btn').on('click', '.edit-btn', function() {
            console.log('Edit button clicked');
            const id = parseInt($(this).closest('tr').data('id'));
            editJob(id);
        });

        $('#jobsTableBody').off('click', '.archive-btn').on('click', '.archive-btn', function() {
            console.log('Archive/restore button clicked');
            const id = parseInt($(this).closest('tr').data('id'));
            if (currentJobView === 'active') {
                if (confirm('Are you sure you want to archive this job?')) {
                    archiveJob(id);
                }
            } else {
                if (confirm('Are you sure you want to restore this job?')) {
                    restoreJob(id);
                }
            }
        });

        $('#jobsTableBody').off('click', '.delete-btn').on('click', '.delete-btn', function() {
            console.log('Delete button clicked');
            const id = parseInt($(this).closest('tr').data('id'));
            if (confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
                deleteJob(id);
            }
        });
    }

    // Set up file upload
    function setupFileUpload() {
        const dropArea = $('#uploadArea');
        if (!dropArea.length) {
            console.warn('Upload area not found');
            return;
        }
        
        console.log('Setting up file upload');
        
        dropArea.off('click').on('click', function() {
            $('#fileInput').click();
        });

        dropArea.off('dragover dragenter').on('dragover dragenter', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });

        dropArea.off('dragleave dragend drop').on('dragleave dragend drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });

        dropArea.off('drop').on('drop', function(e) {
            e.preventDefault();
            const files = e.originalEvent.dataTransfer.files;
            if (files.length) {
                handleFiles(files);
            }
        });

        $('#fileInput').off('change').on('change', function(e) {
            handleFiles(this.files);
        });

        $('#downloadTemplate').off('click').on('click', function(e) {
            e.preventDefault();
            window.location.href = 'master_joblist_template.csv';
        });
    }

    // Handle files for upload
    function handleFiles(files) {
        if (files.length) {
            const file = files[0];
            if (validateFile(file)) {
                $('#uploadArea p').text(`File ready: ${file.name}`);
                $('.upload-btn').prop('disabled', false);
                window.fileToUpload = file;
            }
        }
    }

    // Validate file for upload
    function validateFile(file) {
        const validTypes = ['text/csv', 'application/vnd.ms-excel', 
                           'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!validTypes.includes(file.type)) {
            alert('Please upload a valid CSV or Excel file');
            return false;
        }
        return true;
    }

    // Update filters
    function updateJobFilters() {
        currentJobFilters.quickFilters = $('.filter-btn.active')
            .map(function() {
                return $(this).data('filter');
            }).get();

        currentJobFilters.valueRanges = {
            quote: $('#quoteValueRange').val(),
            contract: $('#contractValueRange').val()
        };

        loadJobData();
    }

    // Render jobs
    function renderJobList() {
        const tbody = $('#jobsTableBody');
        if (!tbody.length) {
            console.warn('Job table body not found');
            return;
        }
        
        tbody.empty();

        if (!jobs.length) {
            tbody.append(`
                <tr>
                    <td colspan="14" style="text-align: center; padding: 2rem;">
                        No jobs found
                    </td>
                </tr>
            `);
            return;
        }

        jobs.forEach(job => {
            // Add action buttons based on current view
            const actionButtons = currentJobView === 'active' ?
                `<button class="edit-btn" title="Edit"><i class="fas fa-edit"></i></button>
                 <button class="archive-btn" title="Archive"><i class="fas fa-archive"></i></button>` :
                `<button class="edit-btn" title="Edit"><i class="fas fa-edit"></i></button>
                 <button class="archive-btn" title="Restore"><i class="fas fa-trash-restore"></i></button>
                 <button class="delete-btn" title="Delete"><i class="fas fa-trash"></i></button>`;

            const row = `
                <tr data-id="${job.id}">
                    <td><input type="checkbox" class="job-checkbox"></td>
                    <td>
                        ${actionButtons}
                    </td>
                    <td>${job.job_number}</td>
                    <td>${job.division}</td>
                    <td>${job.union}</td>
                    <td>${job.title}</td>
                    <td>
                        <span class="status-badge status-${job.status ? job.status.toLowerCase().replace(/\s+/g, '') : 'unknown'}">${job.status || 'Unknown'}</span>
                    </td>
                    <td>${formatDate(job.close_date)}</td>
                    <td>${formatCurrency(job.quote_value)}</td>
                    <td>${formatCurrency(job.total_contract_value)}</td>
                    <td>${formatAddress(job)}</td>
                    <td>${job.distance_km || 0} km</td>
                    <td>${job.gc_name || '-'}</td>
                    <td>${formatDate(job.created_date)}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Format date
    function formatDate(dateString) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString();
    }

    // Format currency
    function formatCurrency(value) {
        if (!value) return '$0.00';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(value);
    }

    // Format address
    function formatAddress(job) {
        const parts = [job.street, job.city, job.province].filter(Boolean);
        return parts.join(', ') || '-';
    }

    // Open job modal
    function openJobModal(job = null) {
        console.log('Opening job modal', job);
        const modal = $('#jobModal');
        
        if (!modal.length) {
            console.error('Job modal not found in the DOM');
            alert('Error: Could not find the job form. Please refresh the page and try again.');
            return;
        }
        
        const form = $('#jobForm');
        
        // Reset form
        if (form.length) {
            form[0].reset();
        } else {
            console.error('Job form not found');
        }
        
        if (job) {
            $('#modalTitle').text(`Edit Job ${job.job_number}`);
            $('#job_id').val(job.id);
            form.find('[name="division"]').val(job.division);
            form.find('[name="union"]').val(job.union);
            form.find('[name="title"]').val(job.title);
            form.find('[name="status"]').val(job.status);
            form.find('[name="street"]').val(job.street);
            form.find('[name="city"]').val(job.city);
            form.find('[name="province"]').val(job.province);
            form.find('[name="postal_code"]').val(job.postal_code);
            form.find('[name="distance_km"]').val(job.distance_km);
            $('.save-btn').text('Save Changes');
        } else {
            $('#modalTitle').text('Create New Job');
            $('#job_id').val('');
            $('.save-btn').text('Create Job');
        }
        
        // Force display of modal
        modal.addClass('active').css('display', 'flex');
    }

    // Open import modal
    function openImportModal() {
        console.log('Opening import modal');
        const modal = $('#importModal');
        
        if (!modal.length) {
            console.error('Import modal not found');
            alert('Error: Import functionality is not available. Please refresh the page.');
            return;
        }
        
        // Reset upload area
        $('#uploadArea p').text('Drag and drop your file here or click to browse');
        $('.upload-btn').prop('disabled', true);
        window.fileToUpload = null;
        
        // Show modal
        modal.addClass('active').css('display', 'flex');
    }

    // Save job
    function saveJob() {
        const form = $('#jobForm');
        const formData = new FormData(form[0]);
        formData.append('action', $('#job_id').val() ? 'edit' : 'add');

        $.ajax({
            url: 'manage_job.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#jobModal').removeClass('active');
                    loadJobData();
                    // Show success message
                    alert(response.message);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Error saving job: ' + error);
            }
        });
    }

    // Restore job
    function restoreJob(id) {
        $.post('manage_job.php', {
            action: 'unarchive',
            job_id: id
        }, function(response) {
            if (response.success) {
                loadJobData();
            } else {
                alert('Error: ' + response.message);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', error);
            alert('Error restoring job: ' + error);
        });
    }

    // Delete job
    function deleteJob(id) {
        $.post('manage_job.php', {
            action: 'delete',
            job_id: id
        }, function(response) {
            if (response.success) {
                loadJobData();
            } else {
                alert('Error: ' + response.message);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', error);
            alert('Error deleting job: ' + error);
        });
    }

    // Load advanced filters
    function loadAdvancedFilters() {
        $.get('get_job_filters.php', function(response) {
            if (response.success) {
                renderAdvancedFilters(response.filters);
            } else {
                console.error('Error loading filters:', response.message);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', error);
        });
    }

    // Render advanced filters
    function renderAdvancedFilters(filters) {
        const container = $('.filters-content');
        container.empty();

        Object.entries(filters).forEach(([category, options]) => {
            const section = $(`
                <div class="filter-section">
                    <h4>${category}</h4>
                    <div class="filter-options">
                        ${Array.isArray(options) ? options.map(option => {
                            const optionValue = typeof option === 'object' ? option.value : option;
                            const optionLabel = typeof option === 'object' ? option.label : option;
                            return `
                                <label class="filter-checkbox">
                                    <input type="checkbox" 
                                           data-category="${category}" 
                                           value="${optionValue}"
                                           ${isFilterSelected(category, option) ? 'checked' : ''}>
                                    ${optionLabel}
                                </label>
                            `;
                        }).join('') : Object.entries(options).map(([value, label]) => `
                            <label class="filter-checkbox">
                                <input type="checkbox" 
                                       data-category="${category}" 
                                       value="${value}"
                                       ${isFilterSelected(category, value) ? 'checked' : ''}>
                                ${label}
                            </label>
                        `).join('')}
                    </div>
                </div>
            `);

            container.append(section);
        });

        // Add change handler for filter checkboxes
        $('.filter-checkbox input').on('change', function() {
            const category = $(this).data('category');
            const value = $(this).val();
            
            if (!currentJobFilters.advancedFilters[category]) {
                currentJobFilters.advancedFilters[category] = [];
            }

            if ($(this).prop('checked')) {
                currentJobFilters.advancedFilters[category].push(value);
            } else {
                currentJobFilters.advancedFilters[category] = currentJobFilters.advancedFilters[category]
                    .filter(item => item !== value);
            }

            loadJobData();
        });
    }

    // Check if filter is selected
    function isFilterSelected(category, option) {
        const value = typeof option === 'object' ? option.value : option;
        return currentJobFilters.advancedFilters[category]?.includes(value) || false;
    }

    // Clear all filters
    function clearAllJobFilters() {
        currentJobFilters = {
            search: '',
            quickFilters: [],
            valueRanges: {
                quote: '',
                contract: ''
            },
            advancedFilters: {}
        };

        // Reset UI elements
        $('#jobSearch').val('');
        $('.filter-btn').removeClass('active');
        $('#quoteValueRange, #contractValueRange').val('');
        $('.filter-checkbox input').prop('checked', false);
        $('#filtersPanel').removeClass('active');

        loadJobData();
    }

    // Edit job
    function editJob(id) {
        const job = jobs.find(j => j.id === id);
        if (job) {
            openJobModal(job);
        } else {
            console.error('Job not found with ID:', id);
        }
    }

    // Archive job
    function archiveJob(id) {
        $.post('manage_job.php', {
            action: 'archive',
            job_id: id
        }, function(response) {
            if (response.success) {
                loadJobData();
            } else {
                alert('Error: ' + response.message);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', error);
            alert('Error archiving job: ' + error);
        });
    }

    // Upload job from file
    function uploadJob() {
        if (!window.fileToUpload) {
            alert('Please select a file to upload');
            return;
        }

        const formData = new FormData();
        formData.append('file', window.fileToUpload);

        $.ajax({
            url: 'import_jobs.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#importModal').removeClass('active');
                    loadJobData();
                    alert(response.message);
                } else {
                    alert('Error: ' + response.message);
                    if (response.errors && response.errors.length) {
                        console.log('Import errors:', response.errors);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Error uploading file: ' + error);
            }
        });
    }

    // Public API
    return {
        init: function() {
            console.log('Initializing Job List Module');
            $(document).ready(function() {
                if ($('.master-job-list-container').length) {
                    console.log('Master job list container found, initializing...');
                    initJobList();
                }
            });
        },
        refresh: initJobList,
        openAddModal: function() {
            openJobModal(null);
        }
    };
})();

// Initialize the module
JobListModule.init();

// Define the global initJobs function that dashboard.js expects
function initJobs() {
    console.log('Global initJobs called');
    JobListModule.refresh();
}