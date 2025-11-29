jQuery(document).ready(function($) {
    // Generate API Key - 修改后的版本
    $('#generate-api-key').on('click', function() {
        var $button = $(this);
        var $result = $('#api-result');
        
        if (!confirm(wpApiExtendedSettings.i18n.confirm_generate)) {
            return;
        }
        
        $button.prop('disabled', true).text(wpApiExtendedSettings.i18n.generating);
        $result.hide().empty();
        
        $.ajax({
            url: wpApiExtendedSettings.ajaxurl,
            method: 'POST',
            data: {
                action: 'generate_api_key',
                nonce: wpApiExtendedSettings.nonce
            }
        })
        .done(function(response) {
            if (response.success) {
                // 不再自动刷新，直接显示新密钥
                $result.html(
                    '<div class="notice notice-success">' +
                    '<h4>✅ ' + response.data.message + '</h4>' +
                    '<p><strong>您的API密钥：</strong></p>' +
                    '<code class="api-key-display">' + response.data.api_key + '</code>' +
                    '<p><strong>⚠️ 重要：请立即保存此密钥，关闭页面后将无法再次查看！</strong></p>' +
                    '<button id="copy-api-key" class="button">复制密钥</button>' +
                    '<button id="continue-after-save" class="button button-primary">我已保存，继续</button>' +
                    '</div>'
                ).show();
                
                // 复制功能
                $('#copy-api-key').on('click', function() {
                    navigator.clipboard.writeText(response.data.api_key).then(function() {
                        alert('API密钥已复制到剪贴板！');
                    });
                });
                
                // 继续按钮
                $('#continue-after-save').on('click', function() {
                    location.reload();
                });
                
            } else {
                $result.html(
                    '<div class="notice notice-error">' +
                    '<p>❌ 生成失败: ' + response.data + '</p>' +
                    '</div>'
                ).show();
                $button.prop('disabled', false).text('Generate New API Key');
            }
        })
        .fail(function(xhr) {
            var errorMessage = '请求失败: ';
            
            if (xhr.status === 500) {
                errorMessage += '服务器内部错误 (500) - 这通常是由于PHP代码错误导致的';
                // 显示详细错误信息用于调试
                if (xhr.responseText) {
                    errorMessage += '<br><br><strong>详细错误：</strong><br>' + xhr.responseText;
                }
            } else if (xhr.responseJSON && xhr.responseJSON.data) {
                errorMessage += xhr.responseJSON.data;
            } else {
                errorMessage += xhr.statusText || '未知错误';
            }
            
            $result.html(
                '<div class="notice notice-error">' +
                '<p>❌ ' + errorMessage + '</p>' +
                '</div>'
            ).show();
            $button.prop('disabled', false).text('Generate New API Key');
        });
    });
    
    // Force Generate API Key
    $('#force-generate-api-key').on('click', function() {
        var $button = $(this);
        var $result = $('#api-result');
        
        if (!confirm('This will forcefully generate a new API key, deleting any existing records. Continue?')) {
            return;
        }
        
        $button.prop('disabled', true).text('Force Generating...');
        $result.hide().empty();
        
        $.ajax({
            url: wpApiExtendedSettings.ajaxurl,
            method: 'POST',
            data: {
                action: 'force_generate_api_key',
                nonce: wpApiExtendedSettings.nonce
            }
        })
        .done(function(response) {
            if (response.success) {
                $result.html(
                    '<div class="notice notice-success">' +
                    '<h4>✅ ' + response.data.message + '</h4>' +
                    '<p><strong>您的API密钥：</strong></p>' +
                    '<code class="api-key-display">' + response.data.api_key + '</code>' +
                    '<p><strong>⚠️ 重要：请立即保存此密钥，关闭页面后将无法再次查看！</strong></p>' +
                    '<button id="copy-api-key" class="button">复制密钥</button>' +
                    '<button id="continue-after-save" class="button button-primary">我已保存，继续</button>' +
                    '</div>'
                ).show();
                
                $('#copy-api-key').on('click', function() {
                    navigator.clipboard.writeText(response.data.api_key).then(function() {
                        alert('API密钥已复制到剪贴板！');
                    });
                });
                
                $('#continue-after-save').on('click', function() {
                    location.reload();
                });
            } else {
                $result.html(
                    '<div class="notice notice-error">' +
                    '<p>❌ 强制生成失败: ' + response.data + '</p>' +
                    '</div>'
                ).show();
            }
        })
        .fail(function(xhr) {
            $result.html(
                '<div class="notice notice-error">' +
                '<p>❌ 请求失败: ' + (xhr.responseJSON ? xhr.responseJSON.data : xhr.statusText) + '</p>' +
                '</div>'
            ).show();
        })
        .always(function() {
            $button.prop('disabled', false).text('Force Generate Key');
        });
    });
    
    // Reset All Keys
    $('#reset-all-keys').on('click', function() {
        var $button = $(this);
        
        if (!confirm('⚠️ DANGER: This will delete ALL API keys for ALL users. This action cannot be undone. Continue?')) {
            return;
        }
        
        if (!confirm('Are you absolutely sure? All API keys will be permanently deleted.')) {
            return;
        }
        
        $button.prop('disabled', true).text('Resetting...');
        
        $.ajax({
            url: wpApiExtendedSettings.ajaxurl,
            method: 'POST',
            data: {
                action: 'reset_api_keys',
                nonce: wpApiExtendedSettings.nonce
            }
        })
        .done(function(response) {
            if (response.success) {
                alert('All API keys have been reset successfully. The page will now reload.');
                location.reload();
            } else {
                alert('Reset failed: ' + response.data);
                $button.prop('disabled', false).text('Reset All Keys');
            }
        })
        .fail(function(xhr) {
            alert('Request failed: ' + (xhr.responseJSON ? xhr.responseJSON.data : xhr.statusText));
            $button.prop('disabled', false).text('Reset All Keys');
        });
    });
    
    // Revoke API Key
    $('#revoke-api-key').on('click', function() {
        var $button = $(this);
        
        if (!confirm(wpApiExtendedSettings.i18n.confirm_revoke)) {
            return;
        }
        
        $button.prop('disabled', true).text(wpApiExtendedSettings.i18n.revoking);
        
        $.ajax({
            url: wpApiExtendedSettings.ajaxurl,
            method: 'POST',
            data: {
                action: 'revoke_api_key',
                nonce: wpApiExtendedSettings.nonce
            }
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('撤销失败: ' + response.data);
                $button.prop('disabled', false).text('Revoke Current Key');
            }
        })
        .fail(function(xhr) {
            alert('请求失败: ' + (xhr.responseJSON ? xhr.responseJSON.data : xhr.statusText));
            $button.prop('disabled', false).text('Revoke Current Key');
        });
    });
    
    // Password Verification
    $('#verify-password').on('click', function() {
        var $button = $(this);
        var $result = $('#password-result');
        var password = $('#user-password').val();
        
        if (!password) {
            alert(wpApiExtendedSettings.i18n.enter_password);
            return;
        }
        
        $button.prop('disabled', true).text(wpApiExtendedSettings.i18n.verifying);
        $result.hide().empty();
        
        $.ajax({
            url: wpApiExtendedSettings.ajaxurl,
            method: 'POST',
            data: {
                action: 'verify_password',
                nonce: wpApiExtendedSettings.nonce,
                password: password
            }
        })
        .done(function(response) {
            if (response.success) {
                $result.html(
                    '<div class="notice notice-success">' +
                    '<p>' + response.data.message + '</p>' +
                    '<p><strong>Temporary Token:</strong> ' + response.data.temp_token + '</p>' +
                    '<p><strong>Expires In:</strong> ' + response.data.expires_in + ' seconds</p>' +
                    '</div>'
                ).show();
                $('#user-password').val(''); // Clear password field
            } else {
                $result.html(
                    '<div class="notice notice-error">' +
                    '<p>' + response.data + '</p>' +
                    '</div>'
                ).show();
            }
        })
        .fail(function(error) {
            $result.html(
                '<div class="notice notice-error">' +
                '<p>Request failed: ' + error.responseText + '</p>' +
                '</div>'
            ).show();
        })
        .always(function() {
            $button.prop('disabled', false).text('Verify Password');
        });
    });
    
    // Test API
    $('#test-api').on('click', function() {
        var $button = $(this);
        var $result = $('#api-result');
        
        $button.prop('disabled', true).text(wpApiExtendedSettings.i18n.requesting);
        $result.hide().empty();
        
        // Send API request
        $.ajax({
            url: wpApiExtendedSettings.root + 'wp-api-extended/v1/posts',
            method: 'GET',
            beforeSend: function(xhr) {
                // User needs to manually enter API key for testing
                var apiKey = prompt(wpApiExtendedSettings.i18n.enter_api_key);
                if (apiKey) {
                    if (apiKey.startsWith('wpak_')) {
                        xhr.setRequestHeader('X-API-Key', apiKey);
                    } else {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + apiKey);
                    }
                }
            },
            data: {
                per_page: 3
            }
        })
        .done(function(response) {
            $result.show().html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
        })
        .fail(function(error) {
            $result.show().html(
                '<div class="notice notice-error">' +
                '<p>Request failed: ' + (error.responseJSON ? error.responseJSON.message : error.responseText) + '</p>' +
                '</div>'
            );
        })
        .always(function() {
            $button.prop('disabled', false).text('Test Get Posts List');
        });
    });
    
    // Enter key triggers password verification
    $('#user-password').on('keypress', function(e) {
        if (e.which === 13) {
            $('#verify-password').click();
        }
    });
    
    // Content Management - Test Get Posts
    $('#get-posts-test').on('click', function() {
        var $button = $(this);
        var $result = $('#content-test-result');
        
        $button.prop('disabled', true);
        $result.hide().html('<div class="notice notice-info">Loading...</div>').show();
        
        $.ajax({
            url: wpApiExtendedSettings.root + 'wp-api-extended/v1/posts',
            method: 'GET',
            beforeSend: function(xhr) {
                var apiKey = prompt(wpApiExtendedSettings.i18n.enter_api_key);
                if (apiKey) {
                    if (apiKey.startsWith('wpak_')) {
                        xhr.setRequestHeader('X-API-Key', apiKey);
                    } else {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + apiKey);
                    }
                }
            },
            data: {
                per_page: 5
            }
        })
        .done(function(response) {
            $result.html(
                '<div class="notice notice-success">' +
                '<h4>Posts Retrieved Successfully</h4>' +
                '<pre>' + JSON.stringify(response, null, 2) + '</pre>' +
                '</div>'
            ).show();
        })
        .fail(function(error) {
            $result.html(
                '<div class="notice notice-error">' +
                '<p>Request failed: ' + (error.responseJSON ? error.responseJSON.message : error.responseText) + '</p>' +
                '</div>'
            ).show();
        })
        .always(function() {
            $button.prop('disabled', false);
        });
    });
    
    // Content Management - Test Get Categories
    $('#get-categories-test').on('click', function() {
        var $button = $(this);
        var $result = $('#content-test-result');
        
        $button.prop('disabled', true);
        $result.hide().html('<div class="notice notice-info">Loading...</div>').show();
        
        $.ajax({
            url: wpApiExtendedSettings.root + 'wp-api-extended/v1/categories',
            method: 'GET',
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
            $result.html(
                '<div class="notice notice-success">' +
                '<h4>Categories Retrieved Successfully</h4>' +
                '<pre>' + JSON.stringify(response, null, 2) + '</pre>' +
                '</div>'
            ).show();
        })
        .fail(function(error) {
            $result.html(
                '<div class="notice notice-error">' +
                '<p>Request failed: ' + (error.responseJSON ? error.responseJSON.message : error.responseText) + '</p>' +
                '</div>'
            ).show();
        })
        .always(function() {
            $button.prop('disabled', false);
        });
    });
    
    // Content Management - Test Search
    $('#search-posts-test').on('click', function() {
        var $button = $(this);
        var $result = $('#content-test-result');
        var searchTerm = prompt('Enter search term:');
        
        if (!searchTerm) {
            return;
        }
        
        $button.prop('disabled', true);
        $result.hide().html('<div class="notice notice-info">Searching...</div>').show();
        
        $.ajax({
            url: wpApiExtendedSettings.root + 'wp-api-extended/v1/search',
            method: 'GET',
            beforeSend: function(xhr) {
                var apiKey = prompt(wpApiExtendedSettings.i18n.enter_api_key);
                if (apiKey) {
                    if (apiKey.startsWith('wpak_')) {
                        xhr.setRequestHeader('X-API-Key', apiKey);
                    } else {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + apiKey);
                    }
                }
            },
            data: {
                s: searchTerm
            }
        })
        .done(function(response) {
            $result.html(
                '<div class="notice notice-success">' +
                '<h4>Search Results</h4>' +
                '<pre>' + JSON.stringify(response, null, 2) + '</pre>' +
                '</div>'
            ).show();
        })
        .fail(function(error) {
            $result.html(
                '<div class="notice notice-error">' +
                '<p>Request failed: ' + (error.responseJSON ? error.responseJSON.message : error.responseText) + '</p>' +
                '</div>'
            ).show();
        })
        .always(function() {
            $button.prop('disabled', false);
        });
    });
    
    // Media Management - Test Get Media
    $('#get-media-test').on('click', function() {
        var $button = $(this);
        var $result = $('#media-test-result');
        
        $button.prop('disabled', true);
        $result.hide().html('<div class="notice notice-info">Loading media...</div>').show();
        
        $.ajax({
            url: wpApiExtendedSettings.root + 'wp-api-extended/v1/media',
            method: 'GET',
            beforeSend: function(xhr) {
                var apiKey = prompt(wpApiExtendedSettings.i18n.enter_api_key);
                if (apiKey) {
                    if (apiKey.startsWith('wpak_')) {
                        xhr.setRequestHeader('X-API-Key', apiKey);
                    } else {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + apiKey);
                    }
                }
            },
            data: {
                per_page: 12
            }
        })
        .done(function(response) {
            if (response.success && response.data.media) {
                displayMediaLibrary(response.data.media);
                $result.html(
                    '<div class="notice notice-success">' +
                    '<p>Media loaded successfully. Found ' + response.data.media.length + ' items.</p>' +
                    '</div>'
                ).show();
            } else {
                $result.html(
                    '<div class="notice notice-error">' +
                    '<p>Failed to load media</p>' +
                    '</div>'
                ).show();
            }
        })
        .fail(function(error) {
            $result.html(
                '<div class="notice notice-error">' +
                '<p>Request failed: ' + (error.responseJSON ? error.responseJSON.message : error.responseText) + '</p>' +
                '</div>'
            ).show();
        })
        .always(function() {
            $button.prop('disabled', false);
        });
    });
    
    // Refresh Media Library
    $('#refresh-media').on('click', function() {
        $('#get-media-test').click();
    });
    
    // Display media library
    function displayMediaLibrary(mediaItems) {
        var $library = $('#media-library');
        
        if (!mediaItems || mediaItems.length === 0) {
            $library.html('<div class="notice notice-info">No media items found.</div>');
            return;
        }
        
        var html = '';
        mediaItems.forEach(function(media) {
            var thumbnailUrl = media.url;
            if (media.sizes && media.sizes.thumbnail) {
                thumbnailUrl = media.sizes.thumbnail.url;
            }
            
            html += '<div class="media-item">';
            if (media.mime_type && media.mime_type.startsWith('image/')) {
                html += '<img src="' + thumbnailUrl + '" alt="' + media.title + '">';
            } else {
                html += '<div style="font-size: 3em;">📄</div>';
            }
            html += '<div class="media-title">' + media.title + '</div>';
            html += '<div class="media-id">ID: ' + media.id + '</div>';
            html += '</div>';
        });
        
        $library.html(html);
    }
    
    // Initialize media library on page load
    if ($('#media-library').length) {
        $('#refresh-media').click();
    }
});
