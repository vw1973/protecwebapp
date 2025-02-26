$(document).ready(function(){


    $('#login-button').on('click', function(e){
        e.preventDefault(); // Prevent any default button behavior
        
        let username = $('#username').val();
        let password = $('#password').val();
        
        // Basic validation
        if(!username || !password) {
            $('#login-error').text('Please enter both username and password.');
            return;
        }

        $('#login-button').css('opacity', 0.5);
        
        $.post('login.php', {
            login: username,
            pass: password
        }, function(response) {
            if (response === 'success') {
                window.location.href = 'dashboard.php';
            } else {
                $('#login-error').text('Invalid login credentials. Please try again.');
                $('#login-button').css('opacity', 1);
            }
        }).fail(function() {
            $('#login-error').text('Connection error. Please try again.');
            $('#login-button').css('opacity', 1);
        });
    });

    // Also allow form submission on Enter key
    $('#username, #password').on('keypress', function(e) {
        if(e.which === 13) { // Enter key
            $('#login-button').click();
        }
    });
});

