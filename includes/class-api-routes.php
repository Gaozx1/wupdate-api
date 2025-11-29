<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_API_Extended_Routes {
    
    public static function register_routes() {
        // Posts endpoints
        register_rest_route('wp-api-extended/v1', '/posts', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array('WP_API_Extended_Functions', 'get_posts'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request'),
                'args' => self::get_posts_collection_params()
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array('WP_API_Extended_Functions', 'create_post'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request'),
                'args' => self::get_post_create_params()
            )
        ));
        
        // Single post endpoints
        register_rest_route('wp-api-extended/v1', '/posts/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array('WP_API_Extended_Functions', 'get_post'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param);
                        }
                    )
                )
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array('WP_API_Extended_Functions', 'update_post'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request'),
                'args' => self::get_post_update_params()
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array('WP_API_Extended_Functions', 'delete_post'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param);
                        }
                    )
                )
            )
        ));
        
        // Media endpoints
        register_rest_route('wp-api-extended/v1', '/media', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array('WP_API_Extended_Functions', 'get_media'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request'),
                'args' => self::get_media_collection_params()
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array('WP_API_Extended_Functions', 'upload_media'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request')
            )
        ));
        
        // Single media endpoints
        register_rest_route('wp-api-extended/v1', '/media/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array('WP_API_Extended_Functions', 'get_media_item'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request')
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array('WP_API_Extended_Functions', 'delete_media'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request')
            )
        ));
        
        // Search posts
        register_rest_route('wp-api-extended/v1', '/search', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array('WP_API_Extended_Functions', 'search_posts'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request'),
                'args' => array(
                    's' => array(
                        'required' => true,
                        'validate_callback' => function($param, $request, $key) {
                            return !empty($param);
                        }
                    )
                )
            )
        ));
        
        // System information
        register_rest_route('wp-api-extended/v1', '/system-info', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array('WP_API_Extended_Functions', 'get_system_info'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request')
            )
        ));
        
        // Categories
        register_rest_route('wp-api-extended/v1', '/categories', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array('WP_API_Extended_Functions', 'get_categories'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request')
            )
        ));
        
        // Tags
        register_rest_route('wp-api-extended/v1', '/tags', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array('WP_API_Extended_Functions', 'get_tags'),
                'permission_callback' => array('WP_API_Extended_Auth', 'authenticate_request')
            )
        ));
    }
    
    /**
     * Get posts collection parameters
     */
    public static function get_posts_collection_params() {
        return array(
            'page' => array(
                'default' => 1,
                'sanitize_callback' => 'absint'
            ),
            'per_page' => array(
                'default' => 10,
                'sanitize_callback' => 'absint'
            ),
            'category' => array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'tag' => array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'search' => array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'orderby' => array(
                'default' => 'date',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'order' => array(
                'default' => 'desc',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }
    
    /**
     * Get post create parameters
     */
    public static function get_post_create_params() {
        return array(
            'title' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return !empty($param);
                }
            ),
            'content' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return !empty($param);
                }
            ),
            'excerpt' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'status' => array(
                'default' => 'draft',
                'validate_callback' => function($param, $request, $key) {
                    return in_array($param, array('draft', 'publish', 'pending', 'private'));
                }
            ),
            'categories' => array(
                'required' => false,
                'sanitize_callback' => function($param) {
                    if (is_array($param)) {
                        return array_map('absint', $param);
                    }
                    return array();
                }
            ),
            'tags' => array(
                'required' => false,
                'sanitize_callback' => function($param) {
                    if (is_array($param)) {
                        return array_map('sanitize_text_field', $param);
                    }
                    return array();
                }
            ),
            'featured_media' => array(
                'required' => false,
                'sanitize_callback' => 'absint'
            )
        );
    }
    
    /**
     * Get post update parameters
     */
    public static function get_post_update_params() {
        $params = self::get_post_create_params();
        $params['title']['required'] = false;
        $params['content']['required'] = false;
        return $params;
    }
    
    /**
     * Get media collection parameters
     */
    public static function get_media_collection_params() {
        return array(
            'page' => array(
                'default' => 1,
                'sanitize_callback' => 'absint'
            ),
            'per_page' => array(
                'default' => 20,
                'sanitize_callback' => 'absint'
            ),
            'orderby' => array(
                'default' => 'date',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'order' => array(
                'default' => 'desc',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }
}