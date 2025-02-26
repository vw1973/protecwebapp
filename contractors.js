console.log('contractors.js loaded');

// Add this after your contractors array declaration
$(document).ajaxSuccess(function(event, xhr, settings) {
    if (settings.url === 'get_contractors.php') {
        console.log('Contractors loaded:', contractors);
    }
});

// Global state for contractors
let contractors = [];
let currentView = 'cards';

// Initialize contractors tab
function initContractors() {
    loadContractors();
    initEventListeners();
}

// Load all contractors
function loadContractors() {
    $.get('get_contractors.php', function(response) {
        contractors = response.contractors;
        renderContractors();
    });
}

// Initialize event listeners
function initEventListeners() {
    console.log('Initializing event listeners');

    // View switching
    $('.view-btn').on('click', function() {
        console.log('View button clicked');
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
        currentView = $(this).data('view');
        renderContractors();
    });

    // Add new contractor
    $('.add-gc-btn').on('click', function() {
        console.log('Add new GC button clicked');
        openContractorModal();
    });

    // Modal close button
    $('.modal .close, .modal .cancel-btn').on('click', function() {
        console.log('Close modal clicked');
        closeModal($(this).closest('.modal'));
    });

    // Add contact button
    $('.add-contact-btn').on('click', function() {
        console.log('Add contact clicked');
        addContactFields();
    });

    // Form submission
    $('#contractorForm').on('submit', function(e) {
        console.log('Form submitted');
        e.preventDefault();
        saveContractor();
    });

    // Close modal on outside click
    $('.modal').on('click', function(e) {
        if ($(e.target).hasClass('modal')) {
            closeModal($(this));
        }
    });

    // Edit button handler
    $('body').on('click', '.edit-btn', function(e) {
        console.log('Edit button clicked');
        e.preventDefault();
        e.stopPropagation();
        const card = $(this).closest('.contractor-card, tr');
        const id = parseInt(card.data('id'));
        console.log('Edit ID:', id);
        editContractor(id);
    });

    // Delete button handler
    $('body').on('click', '.delete-btn', function(e) {
        console.log('Delete button clicked');
        e.preventDefault();
        e.stopPropagation();
        const card = $(this).closest('.contractor-card, tr');
        const id = parseInt(card.data('id'));
        console.log('Delete ID:', id);
        confirmDelete(id);
    });

    console.log('Event listeners initialized');
}



// Open contractor modal for add/edit
function openContractorModal(contractor = null) {
    const modal = $('#contractorModal');
    const form = $('#contractorForm');
    
    // Reset form
    form[0].reset();
    $('#contacts-container').empty();
    
    if (contractor) {
        $('#modalTitle').text('Edit General Contractor');
        $('#contractor_id').val(contractor.id);
        form.find('[name="company_name"]').val(contractor.company_name);
        form.find('[name="street"]').val(contractor.street);
        form.find('[name="city"]').val(contractor.city);
        form.find('[name="province"]').val(contractor.province);
        form.find('[name="postal_code"]').val(contractor.postal_code);
        
        contractor.contacts.forEach(contact => addContactFields(contact));
    } else {
        $('#modalTitle').text('Add General Contractor');
        $('#contractor_id').val('');
        addContactFields();
    }
    
    modal.addClass('active');
}

// Close modal
function closeModal(modal) {
    modal.removeClass('active');
}

// Add contact fields to form
function addContactFields(contact = null) {
    const container = $('#contacts-container');
    const contactHtml = `
        <div class="contact-form">
            <div class="form-row">
                <input type="text" name="contact_name[]" placeholder="Name" 
                       value="${contact ? contact.name : ''}" required>
                <select name="contact_position[]" required>
                    <option value="">Select Position</option>
                    <option value="Project Manager" ${contact && contact.position === 'Project Manager' ? 'selected' : ''}>Project Manager</option>
                    <option value="Estimator" ${contact && contact.position === 'Estimator' ? 'selected' : ''}>Estimator</option>
                    <option value="Site Supervisor" ${contact && contact.position === 'Site Supervisor' ? 'selected' : ''}>Site Supervisor</option>
                    <option value="Accounting" ${contact && contact.position === 'Accounting' ? 'selected' : ''}>Accounting</option>
                    <option value="VP" ${contact && contact.position === 'VP' ? 'selected' : ''}>VP</option>
                    <option value="President" ${contact && contact.position === 'President' ? 'selected' : ''}>President</option>
                </select>
            </div>
            <div class="form-row">
                <input type="email" name="contact_email[]" placeholder="Email" 
                       value="${contact ? contact.email : ''}" required>
                <input type="tel" name="contact_phone[]" placeholder="Phone" 
                       value="${contact ? contact.phone : ''}" required>
            </div>
            <button type="button" class="remove-contact-btn" onclick="$(this).closest('.contact-form').remove()">
                <i class="fas fa-times"></i> Remove Contact
            </button>
        </div>
    `;
    container.append(contactHtml);
}

// Save contractor
function saveContractor() {
    const form = $('#contractorForm');
    const contactsData = [];
    
    form.find('.contact-form').each(function() {
        contactsData.push({
            name: $(this).find('[name="contact_name[]"]').val(),
            position: $(this).find('[name="contact_position[]"]').val(),
            email: $(this).find('[name="contact_email[]"]').val(),
            phone: $(this).find('[name="contact_phone[]"]').val()
        });
    });

    const formData = new FormData();
    formData.append('action', form.find('#contractor_id').val() ? 'edit' : 'add');
    if (form.find('#contractor_id').val()) {
        formData.append('id', form.find('#contractor_id').val());
    }
    formData.append('company_name', form.find('[name="company_name"]').val());
    formData.append('street', form.find('[name="street"]').val());
    formData.append('city', form.find('[name="city"]').val());
    formData.append('province', form.find('[name="province"]').val());
    formData.append('postal_code', form.find('[name="postal_code"]').val());
    formData.append('contacts', JSON.stringify(contactsData));

    $.ajax({
        url: 'manage_contractor.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                closeModal($('#contractorModal'));
                loadContractors();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error saving contractor');
        }
    });
}

// Edit contractor
function editContractor(id) {
    console.log('Editing contractor with ID:', id);
    console.log('Available contractors:', contractors);
    const contractor = contractors.find(c => parseInt(c.id) === id);
    console.log('Found contractor:', contractor);
    
    if (contractor) {
        console.log('Opening modal for contractor:', contractor);
        openContractorModal(contractor);
    } else {
        console.log('Contractor not found');
    }
}

// Confirm delete
function confirmDelete(id) {
    console.log('Confirming delete for ID:', id);
    console.log('Available contractors:', contractors);
    const contractor = contractors.find(c => parseInt(c.id) === id);
    console.log('Found contractor:', contractor);
    
    if (contractor) {
        const confirmResult = confirm(`Are you sure you want to delete ${contractor.company_name}?`);
        console.log('Confirm result:', confirmResult);
        
        if (confirmResult) {
            deleteContractor(id);
        }
    } else {
        console.log('Contractor not found for deletion');
    }
}


// Delete contractor
function deleteContractor(id) {
    $.ajax({
        url: 'manage_contractor.php',
        type: 'POST',
        data: {
            action: 'delete',
            id: id
        },
        success: function(response) {
            if (response.success) {
                loadContractors();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error deleting contractor');
        }
    });
}

// Format currency
function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(value);
}

// Render contractors based on current view
function renderContractors() {
    if (currentView === 'cards') {
        renderCardsView();
    } else {
        renderTableView();
    }
}

// Render cards view
function renderCardsView() {
    const container = $('.contractors-cards');
    container.empty();

    contractors.forEach(gc => {
        const card = createContractorCard(gc);
        container.append(card);
    });

    $('.contractors-cards').addClass('active');
    $('.contractors-table').removeClass('active');
}

// Create individual contractor card
function createContractorCard(gc) {
    return `
        <div class="contractor-card" data-id="${gc.id}">
            <div class="card-header">
                <h3 class="company-name">${gc.company_name}</h3>
                <div class="card-actions">
                    <button class="edit-btn">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="delete-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="company-address">
                ${gc.street}<br>
                ${gc.city}, ${gc.province} ${gc.postal_code}
            </div>
            
            <div class="contacts-section">
                <h4>Contacts</h4>
                ${gc.contacts.map(contact => `
                    <div class="contact-item">
                        <div class="contact-name">${contact.name}</div>
                        <div class="contact-position">${contact.position}</div>
                        <div class="contact-info">
                            ${contact.email}<br>
                            ${contact.phone}
                        </div>
                    </div>
                `).join('')}
            </div>
            
            <div class="stats-grid">
                <div class="stats-section job-stats">
                    <h4 class="stats-title">Job Statistics</h4>
                    <div class="stat-item">
                        <span>Jobs Quoted:</span>
                        <span>${gc.stats.jobs_quoted}</span>
                    </div>
                    <div class="stat-item">
                        <span>Jobs Awarded:</span>
                        <span>${gc.stats.jobs_awarded}</span>
                    </div>
                    <div class="stat-item">
                        <span>Close Ratio:</span>
                        <span>${gc.stats.close_ratio}%</span>
                    </div>
                </div>
                
                <div class="stats-section financial">
                    <h4 class="stats-title">Financial Overview</h4>
                    <div class="stat-item">
                        <span>Total Value:</span>
                        <span>${formatCurrency(gc.stats.total_value)}</span>
                    </div>
                    <div class="stat-item">
                        <span>YTD Value:</span>
                        <span>${formatCurrency(gc.stats.ytd_value)}</span>
                    </div>
                    <div class="stat-item">
                        <span>Avg. Project:</span>
                        <span>${formatCurrency(gc.stats.avg_project)}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Render table view
function renderTableView() {
    const tbody = $('.contractors-table tbody');
    tbody.empty();

    contractors.forEach(gc => {
        const row = `
            <tr data-id="${gc.id}">
                <td>
                    <div class="company-name">${gc.company_name}</div>
                    <div class="company-location">${gc.city}, ${gc.province}</div>
                </td>
                <td>
                    ${gc.contacts[0] ? `
                        <div class="contact-name">${gc.contacts[0].name}</div>
                        <div class="contact-info">${gc.contacts[0].email}</div>
                        <div class="contact-info">${gc.contacts[0].phone}</div>
                    ` : ''}
                </td>
                <td>${gc.stats.jobs_quoted}</td>
                <td>${gc.stats.close_ratio}%</td>
                <td>${formatCurrency(gc.stats.total_value)}</td>
                <td>
                    <button class="edit-btn">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="delete-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    $('.contractors-cards').removeClass('active');
    $('.contractors-table').addClass('active');
}

// Initialize when tab is loaded
$(document).ready(function() {
    if ($('.contractors-container').length) {
        initContractors();
    }
});