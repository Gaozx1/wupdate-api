<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_API_Extended_Keys {
    
    private static $table_name = 'wp_api_extended_keys';
    
    /**
     * 创建数据库表
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        // 检查表是否已存在
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) == $table_name) {
            return true;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            api_key varchar(64) NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            UNIQUE KEY api_key (api_key),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        if ($wpdb->last_error) {
            error_log('WP API Extended: Database table creation failed: ' . $wpdb->last_error);
            return false;
        }
        
        return true;
    }
    
    /**
     * 生成临时访问令牌
     */
    public static function generate_temp_token($user_id) {
        $token = self::generate_random_key(32);
        $expires = time() + 3600; // 1小时有效期
        
        // 存储临时令牌
        set_transient('wp_api_temp_token_' . $token, array(
            'user_id' => $user_id,
            'expires' => $expires
        ), 3600);
        
        return $token;
    }
    
    /**
     * 验证临时令牌
     */
    public static function validate_temp_token($token) {
        // 从瞬态API获取令牌数据
        $token_data = get_transient('wp_api_temp_token_' . $token);
        
        if (!$token_data) {
            return false;
        }
        
        // 检查是否过期
        if (time() > $token_data['expires']) {
            delete_transient('wp_api_temp_token_' . $token);
            return false;
        }
        
        return $token_data['user_id'];
    }
    
    /**
     * 生成API密钥
     */
    public static function generate_api_key($user_id) {
        global $wpdb;
        
        // 验证用户ID有效性
        if (!is_numeric($user_id) || $user_id <= 0 || !get_user_by('id', $user_id)) {
            error_log('WP API Extended: Invalid user ID for generate: ' . $user_id);
            return false;
        }
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        // 确保表存在
        if (!$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name))) {
            $create_result = self::create_table();
            if (!$create_result) {
                error_log('WP API Extended: Failed to create table for generate key');
                return false;
            }
        }
        
        // 检查是否已存在活跃密钥
        $existing_key = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        
        // 如果存在活跃密钥，先将其状态改为 revoked
        if ($existing_key) {
            $revoke_result = $wpdb->update(
                $table_name,
                array('status' => 'revoked'),
                array('user_id' => $user_id, 'status' => 'active'),
                array('%s'),
                array('%d', '%s')
            );
            
            if ($revoke_result === false) {
                error_log('WP API Extended: Failed to revoke existing API key: ' . $wpdb->last_error);
                error_log('WP API Extended: SQL Error: ' . $wpdb->last_query);
                return false;
            }
        }
        
        // 生成新的密钥
        $api_key = self::generate_random_key();
        $hashed_key = self::hash_key($api_key);
        
        // 插入新密钥
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'api_key' => $hashed_key,
                'status' => 'active',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('WP API Extended: Failed to insert API key: ' . $wpdb->last_error);
            error_log('WP API Extended: SQL Error: ' . $wpdb->last_query);
            return false;
        }
        
        // 记录操作日志
        self::log_key_action($user_id, 'generate', $api_key);
        
        // 返回原始密钥（只显示一次）
        return $api_key;
    }
    
    /**
     * 强制生成API密钥（绕过所有检查）
     */
    public static function force_generate_api_key($user_id) {
        global $wpdb;
        
        // 1. 验证用户ID有效性
        if (!is_numeric($user_id) || $user_id <= 0 || !get_user_by('id', $user_id)) {
            error_log('WP API Extended: Invalid user ID for force generate: ' . $user_id);
            return false;
        }
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        // 2. 安全检查表是否存在
        $table_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
        );
        
        // 3. 表不存在时创建，并检查创建结果
        if (!$table_exists) {
            $create_result = self::create_table();
            if (!$create_result) {
                error_log('WP API Extended: Failed to create table for force generate');
                return false;
            }
        }
        
        // 4. 删除用户所有记录，并检查删除结果
        $delete_result = $wpdb->delete(
            $table_name,
            array('user_id' => $user_id),
            array('%d')
        );
        
        if ($delete_result === false) {
            error_log('WP API Extended: Failed to delete old keys: ' . $wpdb->last_error);
            error_log('WP API Extended: Delete query: ' . $wpdb->last_query);
            // 即使删除失败也尝试生成新密钥，但记录错误
        }
        
        // 5. 生成新密钥
        $api_key = self::generate_random_key();
        $hashed_key = self::hash_key($api_key);
        
        // 6. 插入新密钥，并检查插入结果
        $insert_result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'api_key' => $hashed_key,
                'status' => 'active',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if ($insert_result === false) {
            error_log('WP API Extended: Failed to insert new key: ' . $wpdb->last_error);
            error_log('WP API Extended: Insert query: ' . $wpdb->last_query);
            return false;
        }
        
        // 记录操作日志
        self::log_key_action($user_id, 'force_generate', $api_key);
        
        return $api_key;
    }
    
    /**
     * 撤销API密钥
     */
    public static function revoke_api_key($user_id) {
        global $wpdb;
        
        // 验证用户ID
        if (!is_numeric($user_id) || $user_id <= 0 || !get_user_by('id', $user_id)) {
            error_log('WP API Extended: Invalid user ID for revoke: ' . $user_id);
            return false;
        }
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 'revoked',
                'updated_at' => current_time('mysql')
            ),
            array('user_id' => $user_id, 'status' => 'active'),
            array('%s', '%s'),
            array('%d', '%s')
        );
        
        if ($result === false) {
            error_log('WP API Extended: Failed to revoke API key: ' . $wpdb->last_error);
            return false;
        }
        
        self::log_key_action($user_id, 'revoke');
        return $result;
    }
    
    /**
     * 获取用户的API密钥（仅用于显示）
     */
    public static function get_api_key($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $key = $wpdb->get_var($wpdb->prepare(
            "SELECT api_key FROM $table_name WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        
        return $key ? '********（密钥已安全存储）' : false;
    }
    
    /**
     * 验证API密钥
     */
    public static function validate_api_key($api_key) {
        global $wpdb;
        
        if (empty($api_key)) {
            return false;
        }
        
        // 先检查是否是临时令牌
        $temp_user_id = self::validate_temp_token($api_key);
        if ($temp_user_id) {
            return $temp_user_id;
        }
        
        // 检查常规API密钥
        $hashed_key = self::hash_key($api_key);
        $table_name = $wpdb->prefix . self::$table_name;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, status FROM $table_name WHERE api_key = %s",
            $hashed_key
        ));
        
        if ($result && $result->status === 'active') {
            return $result->user_id;
        }
        
        return false;
    }
    
    /**
     * 生成随机密钥
     */
    private static function generate_random_key($length = 32) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $key = '';
        
        // 使用 wp_rand 替代 random_int 保证兼容性
        for ($i = 0; $i < $length; $i++) {
            $key .= $chars[wp_rand(0, strlen($chars) - 1)];
        }
        
        return 'wpak_' . $key;
    }
    
    /**
     * 哈希处理密钥
     */
    private static function hash_key($key) {
        return hash('sha256', $key);
    }
    
    /**
     * 获取用户的所有密钥（管理用）
     */
    public static function get_user_keys($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, status, created_at, updated_at 
             FROM $table_name 
             WHERE user_id = %d 
             ORDER BY created_at DESC",
            $user_id
        ));
    }
    
    /**
     * 检查数据库表是否存在且结构正确
     */
    public static function check_table_exists() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) == $table_name;
    }
    
    /**
     * 重置所有API密钥（清理数据库）
     */
    public static function reset_all_keys() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        // 删除表中的所有记录
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        
        if ($result === false) {
            // 如果TRUNCATE失败，尝试DELETE
            $result = $wpdb->query("DELETE FROM $table_name");
        }
        
        if ($result !== false) {
            error_log('WP API Extended: All API keys have been reset');
        } else {
            error_log('WP API Extended: Failed to reset API keys: ' . $wpdb->last_error);
        }
        
        return $result !== false;
    }
    
    /**
     * 记录密钥操作日志
     */
    private static function log_key_action($user_id, $action, $api_key = '') {
        $log_entry = sprintf(
            'User %d performed %s action. Key: %s',
            $user_id,
            $action,
            $api_key ? substr($api_key, 0, 8) . '...' : 'N/A'
        );
        error_log('WP API Extended: ' . $log_entry);
    }
}