jQuery(document).ready(function($) {
    // Media Upload Form
    $('#upload-media-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $result = $('#upload-result');
        
        var formData = new FormData(this);
        
        $button.prop('disabled', true).text(wpApiExtendedSettings.i18n.uploading);
        $result.hide().empty();
        
        $.ajax({
            url: wpApiExtendedSettings.root + 'wp-api-extended/v1/media',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                var apiKey = prompt(wpApiExtendedSettings.i18n.enter_api_key);
                if (apiKey) {
                    if (apiKey.startsWith('wpak_')) {
                        xhr.setRequestHeader('X-API-Key', apiKey);
                    } else {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + apiKey);
                    }
                }
            }
        })
        .done(function(response) {
            if (response.success) {
                $result.html(
                    '<div class="notice notice-success">' +
                    '<h4>Media Uploaded Successfully!</h4>' +
                    '<p><strong>File:</strong> ' + response.data.title + '</p>' +
                    '<p><strong>URL:</strong> <a href="' + response.data.url + '" target="_blank">' + response.data.url + '</a></p>' +
                    '<p><strong>ID:</strong> ' + response.data.id + '</p>' +
                    '</div>'
                ).show();
                
                // Reset form
                $form[0].reset();
                
                // Refresh media library
                $('#refresh-media').click();
            } else {
                $result.html(
                    '<div class="notice notice-error">' +
                    '<p>Upload failed: ' + (response.data || 'Unknown error') + '</p>' +
                    '</div>'
                ).show();
            }
        })
        .fail(function(error) {
            var errorMessage = 'Upload failed: ';
            if (error.responseJSON && error.responseJSON.message) {
                errorMessage += error.responseJSON.message;
            } else {
                errorMessage += error.responseText || 'Unknown error';
            }
            
            $result.html(
                '<div class="notice notice-error">' +
                '<p>' + errorMessage + '</p>' +
                '</div>'
            ).show();
        })
        .always(function() {
            $button.prop('disabled', false).text('Upload Media');
        });
    });
    
    // Test Upload
    $('#test-upload').on('click', function() {
        var $button = $(this);
        var $result = $('#upload-result');
        
        $button.prop('disabled', true);
        $result.hide().html('<div class="notice notice-info">Testing upload...</div>').show();
        
        $.ajax({
            url: wpApiExtendedSettings.ajaxurl,
            method: 'POST',
            data: {
                action: 'upload_media_test',
                nonce: wpApiExtendedSettings.nonce
            }
        })
        .done(function(response) {
            if (response.success) {
                $result.html(
                    '<div class="notice notice-success">' +
                    '<h4>Upload Test Successful</h4>' +
                    '<p><strong>Max Upload Size:</strong> ' + response.data.test_data.max_upload_size + '</p>' +
                    '<p><strong>Allowed Types:</strong> ' + Object.keys(response.data.test_data.allowed_types).join(', ') + '</p>' +
                    '</div>'
                ).show();
            } else {
                $result.html(
                    '<div class="notice notice-error">' +
                    '<p>Test failed: ' + response.data + '</p>' +
                    '</div>'
                ).show();
            }
        })
        .fail(function(error) {
            $result.html(
                '<div class="notice notice-error">' +
                '<p>Test failed: ' + error.responseText + '</p>' +
                '</div>'
            ).show();
        })
        .always(function() {
            $button.prop('disabled', false);
        });
    });
    
    // Create Post Form
    $('#create-post-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $result = $('#create-post-result');
        
        var formData = {
            title: $('#post-title').val(),
            content: $('#post-content').val(),
            excerpt: $('#post-excerpt').val(),
            status: $('#post-status').val(),
            featured_media: $('#featured-media').val() || ''
        };
        
        $button.prop('disabled', true).text(wpApiExtendedSettings.i18n.creating);
        $result.hide().empty();
        
        $.ajax({
            url: wpApiExtendedSettings.root + 'wp-api-extended/v1/posts',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            beforeSend: function(xhr) {
                var apiKey = prompt(wpApiExtendedSettings.i18n.enter_api_key);
                if (apiKey) {
                    if (apiKey.startsWith('wpak_')) {
                        xhr.setRequestHeader('X-API-Key', apiKey);
                    } else {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + apiKey);
                    }
                }
                xhr.setRequestHeader('Content-Type', 'application/json');
            }
        })
        .done(function(response) {
            if (response.success) {
                $result.html(
                    '<div class="notice notice-success">' +
                    '<h4>Post Created Successfully!</h4>' +
                    '<p><strong>Title:</strong> ' + response.data.title + '</p>' +
                    '<p><strong>ID:</strong> ' + response.data.id + '</p>' +
                    '<p><strong>Status:</strong> ' + response.data.status + '</p>' +
                    '<p><strong>Edit URL:</strong> <a href="' + response.data.edit_url + '" target="_blank">Edit Post</a></p>' +
                    '</div>'
                ).show();
                
                // Reset form
                $form[0].reset();
                $('#featured-media-preview').empty();
            } else {
                $result.html(
                    '<div class="notice notice-error">' +
                    '<p>Creation failed: ' + (response.data || 'Unknown error') + '</p>' +
                    '</div>'
                ).show();
            }
        })
        .fail(function(error) {
            var errorMessage = 'Creation failed: ';
            if (error.responseJSON && error.responseJSON.message) {
                errorMessage += error.responseJSON.message;
            } else {
                errorMessage += error.responseText || 'Unknown error';
            }
            
            $result.html(
                '<div class="notice notice-error">' +
                '<p>' + errorMessage + '</p>' +
                '</div>'
            ).show();
        })
        .always(function() {
            $button.prop('disabled', false).text('Create Post');
        });
    });
    
    // Test Create Post
    $('#test-create-post').on('click', function() {
        var $button = $(this);
        var $result = $('#create-post-result');
        
        $button.prop('disabled', true);
        $result.hide().html('<div class="notice notice-info">Creating test post...</div>').show();
        
        $.ajax({
            url: wpApiExtendedSettings.ajaxurl,
            method: 'POST',
            data: {
                action: 'create_post_test',
                nonce: wpApiExtendedSettings.nonce
            }
        })
        .done(function(response) {
            if (response.success) {
                $result.html(
                    '<div class="notice notice-success">' +
                    '<h4>Test Post Created Successfully!</h4>' +
                    '<p><strong>Post ID:</strong> ' + response.data.post_id + '</p>' +
                    '<p><strong>Edit URL:</strong> <a href="' + response.data.edit_url + '" target="_blank">Edit Test Post</a></p>' +
                    '</div>'
                ).show();
            } else {
                $result.html(
                    '<div class="notice notice-error">' +
                    '<p>Test failed: ' + response.data + '</p>' +
                    '</div>'
                ).show();
            }
        })
        .fail(function(error) {
            $result.html(
                '<div class="notice notice-error">' +
                '<p>Test failed: ' + error.responseText + '</p>' +
                '</div>'
            ).show();
        })
        .always(function() {
            $button.prop('disabled', false);
        });
    });
    
    // Featured Media Selector
    $('#select-featured-media').on('click', function(e) {
        e.preventDefault();
        
        var frame = wp.media({
            title: 'Select Featured Image',
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#featured-media').val(attachment.id);
            $('#featured-media-preview').html(
                '<img src="' + attachment.url + '" style="max-width: 100px; max-height: 100px;">' +
                '<br><small>' + attachment.title + '</small>'
            );
        });
        
        frame.open();
    });
    
    // File input change handler
    $('#media-file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $('#media-title').val(fileName.replace(/\.[^/.]+$/, "")); // Remove extension
        }
    });
});