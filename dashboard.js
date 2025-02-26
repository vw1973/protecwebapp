$(document).ready(function() {
    // Load initial tabs
    loadTabs();

    // Mobile menu toggle
    $('#mobile-menu-toggle').click(function() {
        $('.dashboard-nav').toggleClass('active');
    });

    // Close mobile menu when clicking outside
    $(document).click(function(event) {
        if (!$(event.target).closest('.dashboard-nav, #mobile-menu-toggle').length) {
            $('.dashboard-nav').removeClass('active');
        }
    });
});

function loadTabs() {
    $.post('load_dashboard_tabs.php', function(response) {
        $('#tab-container').html(response);
        
        // Add click handlers to tabs
        $('.nav-tab').click(function(e) {
            e.preventDefault();
            
            // Update active state
            $('.nav-tab').removeClass('active');
            $(this).addClass('active');
            
            // Load tab content
            const tabId = $(this).data('tab');
            loadTabContent(tabId);
            
            // Close mobile menu after selection
            $('.dashboard-nav').removeClass('active');
        });

        // Activate first tab by default
        $('.nav-tab:first').click();
    });
}

// Add this function to dashboard.js to ensure modals work across tab changes
function ensureModalsWork() {
    console.log('Ensuring modals work properly');
    
    // Direct binding for the add job button
    $(document).off('click', '.add-job-btn').on('click', '.add-job-btn', function(e) {
        console.log('Add job button clicked');
        e.preventDefault();
        $('#jobModal').addClass('active').css('display', 'flex');
    });
    
    // Direct binding for import button
    $(document).off('click', '.import-jobs-btn').on('click', '.import-jobs-btn', function(e) {
        console.log('Import button clicked');
        e.preventDefault();
        $('#importModal').addClass('active').css('display', 'flex');
    });
    
    // Direct binding for modal close buttons
    $(document).off('click', '.modal .close, .modal .cancel-btn').on('click', '.modal .close, .modal .cancel-btn', function() {
        console.log('Close button clicked');
        $(this).closest('.modal').removeClass('active').css('display', 'none');
    });
    
    // Direct binding for advanced filters button
    $(document).off('click', '#showFilters').on('click', '#showFilters', function() {
        console.log('Show filters clicked');
        $('#filtersPanel').addClass('active');
    });
    
    // Direct binding for closing filter panel
    $(document).off('click', '.close-filters-btn').on('click', '.close-filters-btn', function() {
        console.log('Close filters clicked');
        $('#filtersPanel').removeClass('active');
    });
}

// Modify the loadTabContent function to call ensureModalsWork after loading tab content
// Add this inside your existing loadTabContent function, just before the end of the function
function loadTabContent(tabId) {
    $('#tab-content').html('<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    
    if (tabId === 'contractors') {
        $.get('contractors_tab.php', function(response) {
            $('#tab-content').html(response);
            initContractors();
        });
    } else if (tabId === 'jobs') {
        $.get('master_job_list_tab.php', function(response) {
            $('#tab-content').html(response);
            initJobs();
            ensureModalsWork(); // Add this line to ensure modals work
        });
    } else {
        $('#tab-content').html(`<h2>${tabId} Content</h2><p>This is the ${tabId} tab content.</p>`);
    }
}