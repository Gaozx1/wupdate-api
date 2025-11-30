jQuery(document).ready(function($) {
    // Generate API Key
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
                // 显示新密钥
                $result.html(
                    '<div class="notice notice-success">' +
                    '<h4>✅ ' + response.data.message + '</h4>' +
                    '<p><strong>' + wpApiExtendedSettings.i18n.your_api_key + '</strong></p>' +
                    '<code class="api-key-display">' + response.data.api_key + '</code>' +
                    '<p><strong>⚠️ ' + wpApiExtendedSettings.i18n.important_save_key + '</strong></p>' +
                    '<button id="copy-api-key" class="button">' + wpApiExtendedSettings.i18n.copy_key + '</button>' +
                    '<button id="continue-after-save" class="button button-primary">' + wpApiExtendedSettings.i18n.continue_after_save + '</button>' +
                    '</div>'
                ).show();
                
                // 复制功能
                $('#copy-api-key').on('click', function() {
                    navigator.clipboard.writeText(response.data.api_key).then(function() {
                        alert(wpApiExtendedSettings.i18n.key_copied);
                    });
                });
                
                // 继续按钮
                $('#continue-after-save').on('click', function() {
                    location.reload();
                });
                
            } else {
                $result.html(
                    '<div class="notice notice-error">' +
                    '<p>❌ ' + wpApiExtendedSettings.i18n.generate_failed + ': ' + response.data + '</p>' +
                    '</div>'
                ).show();
                $button.prop('disabled', false).text(wpApiExtendedSettings.i18n.generate_button);
            }
        })
        .fail(function(xhr) {
            var errorMessage = wpApiExtendedSettings.i18n.request_failed + ': ';
            
            if (xhr.status === 500) {
                errorMessage += wpApiExtendedSettings.i18n.server_error + ' (500) - ' + wpApiExtendedSettings.i18n.php_error_hint;
                // 显示详细错误信息用于调试
                if (xhr.responseText) {
                    errorMessage += '<br><br><strong>' + wpApiExtendedSettings.i18n.detail_error + '</strong><br>' + xhr.responseText;
                }
            } else if (xhr.responseJSON && xhr.responseJSON.data) {
                errorMessage += xhr.responseJSON.data;
            } else {
                errorMessage += xhr.statusText || wpApiExtendedSettings.i18n.unknown_error;
            }
            
            $result.html(
                '<div class="notice notice-error">' +
                '<p>❌ ' + errorMessage + '</p>' +
                '</div>'
            ).show();
            $button.prop('disabled', false).text(wpApiExtendedSettings.i18n.generate_button);
        });
    });
    
    // Force Generate API Key
    $('#force-generate-api-key').on('click', function() {
        var $button = $(this);
        var $result = $('#api-result');
        
        if (!confirm(wpApiExtendedSettings.i18n.confirm_force_generate)) {
            return;
        }
        
        $button.prop('disabled', true).text(wpApiExtendedSettings.i18n.force_generating);
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
                    '<p><strong>' + wpApiExtendedSettings.i18n.your_api_key + '</strong></p>' +
                    '<code class="api-key-display">' + response.data.api_key + '</code>' +
                    '<p><strong>⚠️ ' + wpApiExtendedSettings.i18n.important_save_key + '</strong></p>' +
                    '<button id="copy-api-key" class="button">' + wpApiExtendedSettings.i18n.copy_key + '</button>' +
                    '<button id="continue-after-save" class="button button-primary">' + wpApiExtendedSettings.i18n.continue_after_save + '</button>' +
                    '</div>'
                ).show();
                
                $('#copy-api-key').on('click', function() {
                    navigator.clipboard.writeText(response.data.api_key).then(function() {
                        alert(wpApiExtendedSettings.i18n.key_copied);
                    });
                });
                
                $('#continue-after-save').on('click', function() {
                    location.reload();
                });
            } else {
                $result.html(
                    '<div class="notice notice-error">' +
                    '<p>❌ ' + wpApiExtendedSettings.i18n.force_generate_failed + ': ' + response.data + '</p>' +
                    '</div>'
                ).show();
            }
        })
        .fail(function(xhr) {
            $result.html(
                '<div class="notice notice-error">' +
                '<p>❌ ' + wpApiExtendedSettings.i18n.request_failed + ': ' + (xhr.responseJSON ? xhr.responseJSON.data : xhr.statusText) + '</p>' +
                '</div>'
            ).show();
        })
        .always(function() {
            $button.prop('disabled', false).text(wpApiExtendedSettings.i18n.force_generate_button);
        });
    });
    
    // Reset All Keys
    $('#reset-all-keys').on('click', function() {
        var $button = $(this);
        
        if (!confirm(wpApiExtendedSettings.i18n.confirm_reset_first)) {
            return;
        }
        
        if (!confirm(wpApiExtendedSettings.i18n.confirm_reset_second)) {
            return;
        }
        
        $button.prop('disabled', true).text(wpApiExtendedSettings.i18n.resetting);
        
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
                alert(wpApiExtendedSettings.i18n.reset_success);
                location.reload();
            } else {
                alert(wpApiExtendedSettings.i18n.reset_failed + ': ' + response.data);
                $button.prop('disabled', false).text(wpApiExtendedSettings.i18n.reset_button);
            }
        })
        .fail(function(xhr) {
            alert(wpApiExtendedSettings.i18n.request_failed + ': ' + (xhr.responseJSON ? xhr.responseJSON.data : xhr.statusText));
            $button.prop('disabled', false).text(wpApiExtendedSettings.i18n.reset_button);
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
                alert(wpApiExtendedSettings.i18n.revoke_failed + ': ' + response.data);
                $button.prop('disabled', false).text(wpApiExtendedSettings.i18n.revoke_button);
            }
        })
        .fail(function(xhr) {
            alert(wpApiExtendedSettings.i18n.request_failed + ': ' + (xhr.responseJSON ? xhr.responseJSON.data : xhr.statusText));
            $button.prop('disabled', false).text(wpApiExtendedSettings.i18n.revoke_button);
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
                    '<p><strong>' + wpApiExtendedSettings.i18n.temp_token + '</strong> ' + response.data.temp_token + '</p>' +
                    '<p><strong>' + wpApiExtendedSettings.i18n.expires_in + '</strong> ' + response.data.expires_in + ' ' + wpApiExtendedSettings.i18n.seconds + '</p>' +
                    '</div>'
                ).show();
                $('#user-password').val(''); // 清空密码字段
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
                '<p>' + wpApiExtendedSettings.i18n.verify_failed + ': ' + error.responseText + '</p>' +
                '</div>'
            ).show();
        })
        .always(function() {
            $button.prop('disabled', false).text(wpApiExtendedSettings.i18n.verify_button);
        });
    });
});