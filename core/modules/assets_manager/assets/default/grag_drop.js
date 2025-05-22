(function(){
    $(document).ready(function() {

        const dropZoneContainer = $(`__UUID__`);

        if (dropZoneContainer.length === 0) {
            return;
        }

        // Variables to store selected files
        let selectedFiles = [];

        // Cache DOM elements
        const $dropZone = dropZoneContainer.find('#dropZone');
        const $fileInput = dropZoneContainer.find('#fileInput');
        const $fileList = dropZoneContainer.find('#fileList');
        const $uploadBtn = dropZoneContainer.find('#uploadBtn');
        const $clearBtn = dropZoneContainer.find('#clearBtn');
        const $progressContainer = dropZoneContainer.find('.upload-progress-container');
        const $progressBar = dropZoneContainer.find('#totalProgress');
        const $progressText = dropZoneContainer.find('.progress-text');
        const $emptyMessage = dropZoneContainer.find('.empty-message');

        // Maximum file size in bytes (10MB)
        const MAX_FILE_SIZE = 10 * 1024 * 1024;

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            $dropZone[0].addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight drop zone when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            $dropZone[0].addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            $dropZone[0].addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            $dropZone.addClass('highlight');
        }

        function unhighlight() {
            $dropZone.removeClass('highlight');
            $dropZone.removeClass('error');
        }

        // Handle dropped files
        $dropZone[0].addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        // Handle files from file input
        $fileInput.on('change', function() {
            handleFiles(this.files);
        });

        // Process the selected files
        function handleFiles(files) {
            if (files.length === 0) return;

            // Convert FileList to array and add to selectedFiles
            const newFiles = Array.from(files);

            // Check file sizes
            let hasLargeFile = false;
            newFiles.forEach(file => {
                if (file.size > MAX_FILE_SIZE) {
                    hasLargeFile = true;
                    showError(`File "${file.name}" exceeds the maximum size limit of 10MB.`);
                }
            });

            if (hasLargeFile) {
                $dropZone.addClass('error');
                return;
            }

            // Add valid files to selection
            selectedFiles = [...selectedFiles, ...newFiles.filter(file => file.size <= MAX_FILE_SIZE)];

            // Update UI
            updateFileList();
            updateButtons();
        }

        // Show an error message
        function showError(message) {
            // Create error message if it doesn't exist
            if (dropZoneContainer.find('.error-message').length === 0) {
                dropZoneContainer.find('<div class="error-message"></div>').insertAfter($dropZone);
            }

            const $errorMessage = dropZoneContainer.find('.error-message');
            $errorMessage.text(message).show();

            // Hide after 5 seconds
            setTimeout(() => {
                $errorMessage.hide();
                $dropZone.removeClass('error');
            }, 5000);
        }

        // Update the file list UI
        function updateFileList() {
            $fileList.empty();

            if (selectedFiles.length === 0) {
                $fileList.append('<p class="empty-message">No files selected</p>');
                return;
            }

            selectedFiles.forEach((file, index) => {
                const $fileItem = $('<div class="file-item"></div>');

                // Check if file is an image
                const isImage = file.type.startsWith('image/');

                if (isImage) {
                    // Create image preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const $preview = $('<img class="file-preview" alt="Preview">');
                        $preview.attr('src', e.target.result);
                        $fileItem.prepend($preview);
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Create file icon with extension
                    const extension = file.name.split('.').pop().toUpperCase();
                    const $fileIcon = $('<div class="file-icon"></div>').text(extension);
                    $fileItem.prepend($fileIcon);
                }

                // File details
                const $fileDetails = $('<div class="file-details"></div>');
                $fileDetails.append(`<div class="file-name">${file.name}</div>`);
                $fileDetails.append(`<div class="file-size">${formatFileSize(file.size)}</div>`);
                $fileItem.append($fileDetails);

                // Remove button
                const $removeBtn = $('<div class="file-remove">&times;</div>');
                $removeBtn.on('click', function() {
                    removeFile(index);
                });
                $fileItem.append($removeBtn);

                $fileList.append($fileItem);
            });
        }

        // Format file size to human-readable format
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';

            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));

            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Remove a file from the selection
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateFileList();
            updateButtons();
        }

        // Update button states
        function updateButtons() {
            if (selectedFiles.length > 0) {
                $uploadBtn.prop('disabled', false);
                $clearBtn.prop('disabled', false);
            } else {
                $uploadBtn.prop('disabled', true);
                $clearBtn.prop('disabled', true);
            }
        }

        // Clear all selected files
        $clearBtn.on('click', function() {
            selectedFiles = [];
            updateFileList();
            updateButtons();
            $fileInput.val('');
        });

        // Handle file upload
        $uploadBtn.on('click', function() {
            if (selectedFiles.length === 0) return;

            // In a real application, you would use FormData and AJAX to upload files
            // This is a simulation for demonstration purposes
            simulateUpload();
        });

        // Simulate file upload with progress
        function simulateUpload() {
            $progressContainer.show();
            $uploadBtn.prop('disabled', true);
            $clearBtn.prop('disabled', true);

            let progress = 0;
            const totalFiles = selectedFiles.length;

            console.log(selectedFiles);

            // Simulate progress updates
            const interval = setInterval(() => {
                progress += 5;
                updateProgress(progress);

                if (progress >= 100) {
                    clearInterval(interval);
                    uploadComplete();
                }
            }, 200);
        }

        // Update progress bar
        function updateProgress(percent) {
            $progressBar.css('width', percent + '%');
            $progressText.text(percent + '%');
        }

        // Handle upload completion
        function uploadComplete() {
            setTimeout(() => {
                alert('Upload completed successfully!');

                // Reset everything
                selectedFiles = [];
                updateFileList();
                updateButtons();
                $fileInput.val('');
                $progressContainer.hide();
                updateProgress(0);
            }, 500);
        }

        // Click on drop zone to trigger file input
        $dropZone.on('click', function() {
            $fileInput.trigger('click');
        });
    });
})();