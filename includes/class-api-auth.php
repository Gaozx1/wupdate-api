<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_API_Extended_Auth {
    
    /**
     * 验证API请求
     */
    public static function authenticate_request($request) {
        // 从请求头获取API密钥
        $api_key = self::get_api_key_from_header($request);
        
        if (!$api_key) {
            return new WP_Error(
                'missing_api_key',
                __('Missing API key', 'wp-api-extended'),
                array('status' => 401)
            );
        }
        
        // 检查必要的类是否已加载
        if (!class_exists('WP_API_Extended_Keys')) {
            return new WP_Error(
                'internal_error',
                __('Authentication service unavailable', 'wp-api-extended'),
                array('status' => 500)
            );
        }
        
        // 验证API密钥
        $user_id = WP_API_Extended_Keys::validate_api_key($api_key);
        
        if (!$user_id) {
            return new WP_Error(
                'invalid_api_key',
                __('Invalid API key', 'wp-api-extended'),
                array('status' => 401)
            );
        }
        
        // 设置当前用户
        wp_set_current_user($user_id);
        
        return true;
    }
    
    /**
     * 从请求头获取API密钥
     */
    private static function get_api_key_from_header($request) {
        // 尝试从不同的头信息中获取密钥
        $api_key = $request->get_header('X-API-Key');
        
        if (!$api_key) {
            $api_key = $request->get_header('Authorization');
            if ($api_key && strpos($api_key, 'Bearer ') === 0) {
                $api_key = substr($api_key, 7);
            }
        }
        
        return sanitize_text_field($api_key);
    }
    
    /**
     * 权限回调函数
     */
    public static function permission_callback($request) {
        return self::authenticate_request($request);
    }
}