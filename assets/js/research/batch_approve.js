// filepath: d:\xampp\htdocs\NLNganh\assets\js\research\batch_approve.js

// Function to update batch button state
function updateBatchButton() {
    const selectedCount = $(".project-checkbox:checked").length;
    $("#batchApproveBtn").prop("disabled", selectedCount === 0);
    $("#selectedCount").text(selectedCount);
}

// Function to initialize batch approval functionality
function initBatchApproval() {
    console.log("Initializing batch approval functionality");
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $(".alert").fadeOut("slow");
    }, 5000);
    
    // Select all functionality
    $("#selectAll").change(function() {
        $(".project-checkbox").prop("checked", $(this).prop("checked"));
        updateBatchButton();
    });
    
    // Individual checkbox change
    $(".project-checkbox").change(function() {
        updateBatchButton();
        
        // If any checkbox is unchecked, uncheck "Select All"
        if (!$(this).prop("checked")) {
            $("#selectAll").prop("checked", false);
        }
        
        // If all checkboxes are checked, check "Select All"
        if ($(".project-checkbox:checked").length === $(".project-checkbox").length) {
            $("#selectAll").prop("checked", true);
        }
    });
    
    // Update batch action button state
    updateBatchButton();
    
    // Batch approve button
    $("#batchApproveBtn").click(function() {
        if ($(".project-checkbox:checked").length > 0) {
            $("#batchApproveModal").modal("show");
            $("#selectedCount").text($(".project-checkbox:checked").length);
        }
    });
    
    // Confirm batch approve
    $("#confirmBatchApprove").click(function() {
        // Save comments to hidden field
        $("#batchComment").val($("#batchApproveComments").val());
        
        // Submit the form
        $("#batchActionForm").submit();
    });
}

// Auto-initialize when document is ready
$(document).ready(function() {
    console.log("Ready state detected, batch approval script ready to be called");
    
    // Initialization will be called by the research_footer.php when needed
    // using the initBatchApproval() function
});
