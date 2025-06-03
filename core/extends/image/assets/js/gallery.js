/**
 * Gallery Image Picker & Uploader Modal
 * jQuery functionality for image selection, upload, and preview
 */

$(document).ready(function() {
    // Global variables to store selected images
    let selectedGalleryImages = [];
    let uploadedImages = [];
    
    // Modal functionality
    const modal = $("#gallery-modal");
    const openModalBtn = $("#open-gallery-modal");
    const closeModalBtn = $(".close-modal");
    
    // Open modal when button is clicked
    openModalBtn.on("click", function() {
        modal.css("display", "block");
        $("body").css("overflow", "hidden"); // Prevent scrolling of background
    });
    
    // Close modal when X is clicked
    closeModalBtn.on("click", function() {
        closeModal();
    });
    
    // Close modal when clicking outside the modal content
    $(window).on("click", function(event) {
        if ($(event.target).is(modal)) {
            closeModal();
        }
    });
    
    // Close modal function
    function closeModal() {
        modal.css("display", "none");
        $("body").css("overflow", "auto"); // Re-enable scrolling
    }
    
    // Gallery image selection
    $("#image-gallery").on("click", ".gallery-item", function() {
        const $this = $(this);
        const imageId = $this.data("id");
        const imageSrc = $this.find("img").attr("src");
        
        // Toggle selection
        if ($this.hasClass("selected")) {
            $this.removeClass("selected");
            // Remove from selected images array
            selectedGalleryImages = selectedGalleryImages.filter(img => img.id !== imageId);
        } else {
            $this.addClass("selected");
            // Add to selected images array
            selectedGalleryImages.push({
                id: imageId,
                src: imageSrc
            });
        }
        
        updatePreviewArea();
    });
    
    // File upload functionality
    const uploadArea = $("#upload-area");
    const fileInput = $("#file-input");
    
    // Handle drag and drop events
    uploadArea.on("dragover", function(e) {
        e.preventDefault();
        $(this).addClass("dragover");
    });
    
    uploadArea.on("dragleave", function() {
        $(this).removeClass("dragover");
    });
    
    uploadArea.on("drop", function(e) {
        e.preventDefault();
        $(this).removeClass("dragover");
        
        const files = e.originalEvent.dataTransfer.files;
        handleFiles(files);
    });
    
    // Handle file input change
    fileInput.on("change", function() {
        handleFiles(this.files);
    });
    
    // Process uploaded files
    function handleFiles(files) {
        if (!files || files.length === 0) return;
        
        Array.from(files).forEach(file => {
            // Check if file is an image
            if (!file.type.match('image.*')) return;
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const imageSrc = e.target.result;
                // Generate a unique ID for the uploaded image
                const imageId = 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                
                // Add to uploaded images array
                uploadedImages.push({
                    id: imageId,
                    src: imageSrc,
                    file: file
                });
                
                updatePreviewArea();
            };
            
            reader.readAsDataURL(file);
        });
    }
    
    // Update preview area with selected and uploaded images
    function updatePreviewArea() {
        const previewArea = $("#preview-area");
        const allImages = [...selectedGalleryImages, ...uploadedImages];
        
        // Clear preview area
        previewArea.empty();
        
        if (allImages.length === 0) {
            previewArea.html('<p class="no-images">No images selected</p>');
            return;
        }
        
        // Add each image to preview area
        allImages.forEach(image => {
            const previewItem = $(`
                <div class="preview-item" data-id="${image.id}">
                    <img src="${image.src}" alt="Selected image">
                    <button class="remove-btn" data-id="${image.id}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
            
            previewArea.append(previewItem);
        });
        
        // Add remove functionality to preview items
        $(".remove-btn").on("click", function(e) {
            e.stopPropagation();
            const imageId = $(this).data("id");
            
            // Check if it's a gallery image or uploaded image
            if (imageId.toString().startsWith('upload_')) {
                // Remove from uploaded images
                uploadedImages = uploadedImages.filter(img => img.id !== imageId);
            } else {
                // Remove from selected gallery images
                selectedGalleryImages = selectedGalleryImages.filter(img => img.id !== imageId);
                // Remove selected class from gallery item
                $(`.gallery-item[data-id="${imageId}"]`).removeClass("selected");
            }
            
            updatePreviewArea();
        });
    }
    
    // Clear selection button
    $("#clear-selection").on("click", function() {
        // Clear arrays
        selectedGalleryImages = [];
        uploadedImages = [];
        
        // Remove selected class from all gallery items
        $(".gallery-item").removeClass("selected");
        
        // Update preview area
        updatePreviewArea();
    });
    
    // Submit selection button
    $("#submit-selection").on("click", function() {
        const allImages = [...selectedGalleryImages, ...uploadedImages];
        
        if (allImages.length === 0) {
            alert("Please select at least one image.");
            return;
        }
        
        // Display selected images in the main page
        const resultContainer = $("#result-container");
        resultContainer.empty();
        
        allImages.forEach(image => {
            const resultItem = $(`
                <div class="result-image-item">
                    <img src="${image.src}" alt="Selected image">
                </div>
            `);
            
            resultContainer.append(resultItem);
        });
        
        // Close modal
        closeModal();
        
        // Here you would typically handle form submission with the selected images
        console.log("Selected images:", allImages);
        
        // For a real application, you might want to:
        // 1. Convert images to FormData
        // 2. Send them to a server via AJAX
        // 3. Process the response
        
        // Example of how you might prepare form data:
        /*
        const formData = new FormData();
        
        // Add selected gallery images (by ID)
        selectedGalleryImages.forEach(image => {
            formData.append('gallery_images[]', image.id);
        });
        
        // Add uploaded files
        uploadedImages.forEach(image => {
            formData.append('uploaded_images[]', image.file);
        });
        
        // Send to server
        $.ajax({
            url: 'your-server-endpoint',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Success:', response);
            },
            error: function(error) {
                console.error('Error:', error);
            }
        });
        */
    });
    
    // Initialize with placeholder images for demo
    function initPlaceholderImages() {
        // In a real application, you would fetch these from a server
        // For now, we'll just use the placeholders already in the HTML
    }
    
    // Initialize the component
    initPlaceholderImages();
});
