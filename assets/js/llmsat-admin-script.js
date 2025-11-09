
jQuery(document).ready(function(){
    if ( llmsat_admin.block_editor_active == "yes" ) {
        jQuery('#search-submit').on('click', function() {	
            var href = window.location.href.substring(0, window.location.href.indexOf('?'));
            var qs = window.location.href.substring(window.location.href.indexOf('?') + 1, window.location.href.length);
            var newParam = "s" + '=' +  jQuery('#students-search-input').val();

            if (qs.indexOf('s'+ '=') == -1) {
                if (qs == '') {
                    qs = '?'
                } else {
                    qs = qs + '&'
                }
                qs = qs + newParam;

            } else {
                var start = qs.indexOf('s'+ "=");
                var end = qs.indexOf("&", start);
                if (end == -1) {
                    end = qs.length;
                }
                var curParam = qs.substring(start, end);
                qs = qs.replace(curParam, newParam);
            }
            window.location.replace(href + '?' + qs);
        });
    }
    
    jQuery(document).ready(function(){
        var uri = window.location.toString();
        if ( uri.indexOf( "&s=" ) > 0 ) {
            var clean_uri = uri.substring( 0, uri.indexOf( "&s=" ) );
            window.history.replaceState( {}, document.title, clean_uri );
            
        }
    });
    
    // Instructor attendance marking functionality
    jQuery(document).on('click', '.llmsat-instructor-mark-present', function(e) {
        e.preventDefault();
        
        var button = jQuery(this);
        var user_id = button.data('user-id');
        var course_id = button.data('course-id');
        
        // Disable button to prevent double-clicking
        button.prop('disabled', true);
        button.text('Marking...');
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'llmsat_instructor_mark_attendance',
                user_id: user_id,
                course_id: course_id,
                nonce: llmsat_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Replace button with success message
                    button.replaceWith('<span class="llmsat-attendance-marked" style="color: green; font-weight: bold;">✓ Marked</span>');
                    
                    // Show success message
                    if (typeof response.data.message !== 'undefined') {
                        alert(response.data.message);
                    }
                    
                    // Optionally refresh the page to update attendance counts
                    // window.location.reload();
                } else {
                    // Show error message
                    alert(response.data.message || 'Failed to mark attendance');
                    
                    // Re-enable button
                    button.prop('disabled', false);
                    button.text('Mark Present');
                }
            },
            error: function() {
                alert('An error occurred while marking attendance');
                
                // Re-enable button
                button.prop('disabled', false);
                button.text('Mark Present');
            }
        });
    });
    
    return;
});