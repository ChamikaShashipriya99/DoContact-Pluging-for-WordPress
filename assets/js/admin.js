/* Admin JS for DoContact submissions management */
(function($){
    'use strict';
    
    // Wait for DOM to be ready
    $(document).ready(function(){
        
        // Debug: Check if script loaded
        console.log('DoContact Admin JS loaded');
        
        // Check if DoContactAdmin is defined
        if (typeof DoContactAdmin === 'undefined') {
            console.error('DoContactAdmin is not defined. Make sure the script is properly localized.');
            // Fallback to WordPress default ajaxurl if available
            if (typeof ajaxurl === 'undefined') {
                console.error('ajaxurl is also not defined. AJAX functionality will not work.');
                return;
            }
            // Create fallback DoContactAdmin object
            window.DoContactAdmin = {
                ajax_url: ajaxurl,
                nonce: '',
                confirm: 'Are you sure you want to delete this submission?'
            };
            console.warn('Using fallback DoContactAdmin object. Nonce verification may fail.');
        } else {
            console.log('DoContactAdmin object found:', DoContactAdmin);
        }
        
        // Debug: Check if delete buttons exist
        var $deleteButtons = $('.docontact-delete-btn');
        console.log('Found ' + $deleteButtons.length + ' delete button(s)');
        
        // Handle delete button clicks using event delegation
        $(document).on('click', '.docontact-delete-btn', function(e){
            e.preventDefault();
            e.stopPropagation();
            
            var $button = $(this);
            var submissionId = $button.data('id');
            var submissionName = $button.data('name') || 'this submission';
            var $row = $button.closest('tr');
            
            // Validate we have an ID
            if (!submissionId) {
                alert('Error: Submission ID is missing.');
                return;
            }
            
            // Confirm deletion
            var confirmMessage = DoContactAdmin.confirm || 'Are you sure you want to delete this submission?';
            if (!confirm(confirmMessage + ' (' + submissionName + ')')) {
                return;
            }
            
            // Disable button and show loading state
            $button.prop('disabled', true).text('Deleting...');
            
            // Validate nonce exists
            if (!DoContactAdmin.nonce || DoContactAdmin.nonce === '') {
                console.error('Nonce is missing or empty!');
                alert('Security error: Nonce is missing. Please refresh the page and try again.');
                $button.prop('disabled', false).text('Delete');
                return;
            }
            
            // Prepare AJAX data
            var ajaxData = {
                action: 'docontact_delete',
                submission_id: submissionId,
                docontact_delete_nonce: DoContactAdmin.nonce
            };
            
            console.log('Sending AJAX request with data:', {
                action: ajaxData.action,
                submission_id: ajaxData.submission_id,
                nonce_length: ajaxData.docontact_delete_nonce ? ajaxData.docontact_delete_nonce.length : 0
            });
            
            // Send AJAX request
            $.ajax({
                url: DoContactAdmin.ajax_url || ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: ajaxData,
                success: function(response){
                    if (response && response.success) {
                        // Fade out and remove the row
                        $row.fadeOut(300, function(){
                            $(this).remove();
                            
                            // Check if table is now empty
                            var $tbody = $('.widefat tbody');
                            if ($tbody.find('tr').length === 0) {
                                $tbody.html('<tr><td colspan="9">No submissions found.</td></tr>');
                            }
                            
                            // Show success message
                            showAdminNotice('Submission deleted successfully.', 'success');
                        });
                    } else {
                        // Show error message
                        var errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Failed to delete submission.';
                        showAdminNotice(errorMsg, 'error');
                        $button.prop('disabled', false).text('Delete');
                    }
                },
                error: function(xhr, status, error){
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    
                    // Show error message
                    var errorMsg = 'An error occurred while deleting the submission.';
                    if (xhr.responseText) {
                        try {
                            var errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.data && errorResponse.data.message) {
                                errorMsg = errorResponse.data.message;
                            }
                        } catch(e) {
                            // Ignore JSON parse errors
                        }
                    }
                    showAdminNotice(errorMsg, 'error');
                    $button.prop('disabled', false).text('Delete');
                }
            });
        });
        
        // Select All checkbox functionality
        $('#cb-select-all').on('change', function(){
            $('.docontact-checkbox').prop('checked', $(this).prop('checked'));
            updateBulkActionButton();
        });
        
        // Individual checkbox change
        $(document).on('change', '.docontact-checkbox', function(){
            var totalCheckboxes = $('.docontact-checkbox').length;
            var checkedCheckboxes = $('.docontact-checkbox:checked').length;
            $('#cb-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
            updateBulkActionButton();
        });
        
        // Update bulk action button state
        function updateBulkActionButton(){
            var checkedCount = $('.docontact-checkbox:checked').length;
            $('#doaction').prop('disabled', checkedCount === 0);
        }
        
        // Initialize button state
        updateBulkActionButton();
        
        // Bulk action form submission
        $('#doaction').on('click', function(e){
            e.preventDefault();
            
            var action = $('#bulk-action-selector-top').val();
            if (action === '-1' || action === '') {
                alert('Please select a bulk action.');
                return;
            }
            
            var checkedBoxes = $('.docontact-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Please select at least one submission.');
                return;
            }
            
            if (action === 'delete') {
                handleBulkDelete(checkedBoxes);
            }
        });
        
        // Handle bulk delete
        function handleBulkDelete($checkedBoxes){
            var ids = [];
            var names = [];
            
            $checkedBoxes.each(function(){
                var $row = $(this).closest('tr');
                var id = $(this).val();
                var name = $row.find('td:nth-child(3)').text().trim() || 'submission';
                ids.push(id);
                names.push(name);
            });
            
            var count = ids.length;
            var confirmMessage = DoContactAdmin.confirm || 'Are you sure you want to delete this submission?';
            var bulkConfirm = 'Are you sure you want to delete ' + count + ' selected submission(s)?\n\nThis action cannot be undone.';
            
            if (!confirm(bulkConfirm)) {
                return;
            }
            
            // Disable button and show loading
            $('#doaction').prop('disabled', true).val('Deleting...');
            $checkedBoxes.prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: DoContactAdmin.ajax_url || ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'docontact_bulk_delete',
                    submission_ids: ids,
                    docontact_delete_nonce: DoContactAdmin.nonce
                },
                success: function(response){
                    if (response && response.success) {
                        // Remove all checked rows
                        var $rows = $checkedBoxes.closest('tr');
                        $rows.fadeOut(300, function(){
                            $(this).remove();
                            
                            // Check if table is now empty
                            var $tbody = $('.widefat tbody');
                            if ($tbody.find('tr').length === 0) {
                                $tbody.html('<tr><td colspan="10">No submissions found.</td></tr>');
                            }
                            
                            // Reset select all checkbox
                            $('#cb-select-all').prop('checked', false);
                            updateBulkActionButton();
                            
                            // Show success message
                            var message = response.data.message || count + ' submission(s) deleted successfully.';
                            showAdminNotice(message, 'success');
                        });
                    } else {
                        var errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Failed to delete submissions.';
                        showAdminNotice(errorMsg, 'error');
                        $('#doaction').prop('disabled', false).val('Apply');
                        $checkedBoxes.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error){
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    
                    var errorMsg = 'An error occurred while deleting submissions.';
                    if (xhr.responseText) {
                        try {
                            var errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.data && errorResponse.data.message) {
                                errorMsg = errorResponse.data.message;
                            }
                        } catch(e) {
                            // Ignore JSON parse errors
                        }
                    }
                    showAdminNotice(errorMsg, 'error');
                    $('#doaction').prop('disabled', false).val('Apply');
                    $checkedBoxes.prop('disabled', false);
                }
            });
        }
        
        // Helper function to show admin notices
        function showAdminNotice(message, type){
            type = type || 'info';
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
            $('.wrap h1').first().after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function(){
                $notice.fadeOut(300, function(){
                    $(this).remove();
                });
            }, 5000);
            
            // Make dismissible
            $notice.on('click', '.notice-dismiss', function(){
                $notice.fadeOut(300, function(){
                    $(this).remove();
                });
            });
        }
    });
})(jQuery);
