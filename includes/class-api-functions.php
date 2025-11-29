<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_API_Extended_Functions {
    
    /**
     * Get posts list
     */
    public static function get_posts($request) {
        $params = $request->get_params();
        
        // Set default parameters
        $defaults = array(
            'page' => 1,
            'per_page' => 10,
            'category' => '',
            'tag' => '',
            'search' => '',
            'orderby' => 'date',
            'order' => 'desc'
        );
        
        $args = wp_parse_args($params, $defaults);
        
        // Build query parameters
        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => intval($args['per_page']),
            'paged' => intval($args['page']),
            'orderby' => sanitize_text_field($args['orderby']),
            'order' => sanitize_text_field($args['order']),
            's' => sanitize_text_field($args['search'])
        );
        
        // Category filter
        if (!empty($args['category'])) {
            $query_args['category_name'] = sanitize_text_field($args['category']);
        }
        
        // Tag filter
        if (!empty($args['tag'])) {
            $query_args['tag'] = sanitize_text_field($args['tag']);
        }
        
        // Execute query
        $posts_query = new WP_Query($query_args);
        $posts = array();
        
        if ($posts_query->have_posts()) {
            while ($posts_query->have_posts()) {
                $posts_query->the_post();
                global $post;
                
                $posts[] = self::format_post_data($post);
            }
            wp_reset_postdata();
        }
        
        // Return results
        $response = array(
            'success' => true,
            'data' => array(
                'posts' => $posts,
                'pagination' => array(
                    'current_page' => intval($args['page']),
                    'per_page' => intval($args['per_page']),
                    'total_posts' => $posts_query->found_posts,
                    'total_pages' => $posts_query->max_num_pages
                )
            )
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Get post details
     */
    public static function get_post($request) {
        $post_id = $request['id'];
        
        // Validate post ID
        if (!is_numeric($post_id) || $post_id <= 0) {
            return new WP_Error(
                'invalid_post_id',
                __('Invalid post ID', 'wp-api-extended'),
                array('status' => 400)
            );
        }
        
        $post = get_post($post_id);
        
        // Check if post exists
        if (!$post || $post->post_status !== 'publish') {
            return new WP_Error(
                'post_not_found',
                __('Post not found', 'wp-api-extended'),
                array('status' => 404)
            );
        }
        
        $formatted_post = self::format_post_data($post, true);
        
        $response = array(
            'success' => true,
            'data' => $formatted_post
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Create new post
     */
    public static function create_post($request) {
        $params = $request->get_params();
        
        // Validate required fields
        if (empty($params['title'])) {
            return new WP_Error(
                'missing_title',
                __('Post title is required', 'wp-api-extended'),
                array('status' => 400)
            );
        }
        
        if (empty($params['content'])) {
            return new WP_Error(
                'missing_content',
                __('Post content is required', 'wp-api-extended'),
                array('status' => 400)
            );
        }
        
        $current_user_id = get_current_user_id();
        
        // Prepare post data
        $post_data = array(
            'post_title' => sanitize_text_field($params['title']),
            'post_content' => wp_kses_post($params['content']),
            'post_status' => sanitize_text_field($params['status'] ?? 'draft'),
            'post_author' => $current_user_id,
            'post_excerpt' => sanitize_text_field($params['excerpt'] ?? ''),
            'post_type' => 'post'
        );
        
        // Insert post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return new WP_Error(
                'post_creation_failed',
                __('Failed to create post', 'wp-api-extended'),
                array('status' => 500)
            );
        }
        
        // Handle categories
        if (!empty($params['categories']) && is_array($params['categories'])) {
            wp_set_post_categories($post_id, $params['categories']);
        }
        
        // Handle tags
        if (!empty($params['tags']) && is_array($params['tags'])) {
            wp_set_post_tags($post_id, $params['tags']);
        }
        
        // Handle featured media
        if (!empty($params['featured_media'])) {
            set_post_thumbnail($post_id, intval($params['featured_media']));
        }
        
        $post = get_post($post_id);
        $formatted_post = self::format_post_data($post, true);
        
        $response = array(
            'success' => true,
            'data' => $formatted_post,
            'message' => __('Post created successfully', 'wp-api-extended')
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Update post
     */
    public static function update_post($request) {
        $post_id = $request['id'];
        $params = $request->get_params();
        
        // Validate post ID
        if (!is_numeric($post_id) || $post_id <= 0) {
            return new WP_Error(
                'invalid_post_id',
                __('Invalid post ID', 'wp-api-extended'),
                array('status' => 400)
            );
        }
        
        $post = get_post($post_id);
        
        // Check if post exists
        if (!$post) {
            return new WP_Error(
                'post_not_found',
                __('Post not found', 'wp-api-extended'),
                array('status' => 404)
            );
        }
        
        // Check if user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error(
                'access_denied',
                __('You do not have permission to edit this post', 'wp-api-extended'),
                array('status' => 403)
            );
        }
        
        // Prepare post data
        $post_data = array(
            'ID' => $post_id
        );
        
        if (!empty($params['title'])) {
            $post_data['post_title'] = sanitize_text_field($params['title']);
        }
        
        if (!empty($params['content'])) {
            $post_data['post_content'] = wp_kses_post($params['content']);
        }
        
        if (!empty($params['status'])) {
            $post_data['post_status'] = sanitize_text_field($params['status']);
        }
        
        if (isset($params['excerpt'])) {
            $post_data['post_excerpt'] = sanitize_text_field($params['excerpt']);
        }
        
        // Update post
        $updated_post_id = wp_update_post($post_data);
        
        if (is_wp_error($updated_post_id)) {
            return new WP_Error(
                'post_update_failed',
                __('Failed to update post', 'wp-api-extended'),
                array('status' => 500)
            );
        }
        
        // Handle categories
        if (isset($params['categories']) && is_array($params['categories'])) {
            wp_set_post_categories($post_id, $params['categories']);
        }
        
        // Handle tags
        if (isset($params['tags']) && is_array($params['tags'])) {
            wp_set_post_tags($post_id, $params['tags']);
        }
        
        // Handle featured media
        if (isset($params['featured_media'])) {
            if (!empty($params['featured_media'])) {
                set_post_thumbnail($post_id, intval($params['featured_media']));
            } else {
                delete_post_thumbnail($post_id);
            }
        }
        
        $updated_post = get_post($post_id);
        $formatted_post = self::format_post_data($updated_post, true);
        
        $response = array(
            'success' => true,
            'data' => $formatted_post,
            'message' => __('Post updated successfully', 'wp-api-extended')
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Delete post
     */
    public static function delete_post($request) {
        $post_id = $request['id'];
        
        // Validate post ID
        if (!is_numeric($post_id) || $post_id <= 0) {
            return new WP_Error(
                'invalid_post_id',
                __('Invalid post ID', 'wp-api-extended'),
                array('status' => 400)
            );
        }
        
        $post = get_post($post_id);
        
        // Check if post exists
        if (!$post) {
            return new WP_Error(
                'post_not_found',
                __('Post not found', 'wp-api-extended'),
                array('status' => 404)
            );
        }
        
        // Check if user can delete this post
        if (!current_user_can('delete_post', $post_id)) {
            return new WP_Error(
                'access_denied',
                __('You do not have permission to delete this post', 'wp-api-extended'),
                array('status' => 403)
            );
        }
        
        // Delete post
        $result = wp_delete_post($post_id, true);
        
        if (!$result) {
            return new WP_Error(
                'post_deletion_failed',
                __('Failed to delete post', 'wp-api-extended'),
                array('status' => 500)
            );
        }
        
        $response = array(
            'success' => true,
            'message' => __('Post deleted successfully', 'wp-api-extended'),
            'data' => array(
                'deleted_post_id' => $post_id
            )
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Upload media
     */
    public static function upload_media($request) {
        $files = $request->get_file_params();
        
        if (empty($files['file'])) {
            return new WP_Error(
                'no_file',
                __('No file uploaded', 'wp-api-extended'),
                array('status' => 400)
            );
        }
        
        $file = $files['file'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error(
                'upload_error',
                __('File upload error', 'wp-api-extended'),
                array('status' => 400)
            );
        }
        
        // Check file type
        $allowed_types = get_allowed_mime_types();
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file_type['type'], $allowed_types)) {
            return new WP_Error(
                'invalid_file_type',
                __('File type not allowed', 'wp-api-extended'),
                array('status' => 400)
            );
        }
        
        // Check file size
        $max_upload_size = wp_max_upload_size();
        if ($file['size'] > $max_upload_size) {
            return new WP_Error(
                'file_too_large',
                __('File is too large', 'wp-api-extended'),
                array('status' => 400)
            );
        }
        
        // Prepare upload
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            return new WP_Error(
                'upload_failed',
                $upload['error'],
                array('status' => 500)
            );
        }
        
        // Create attachment post
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit',
            'guid' => $upload['url']
        );
        
        $attachment_id = wp_insert_attachment($attachment, $upload['file']);
        
        if (is_wp_error($attachment_id)) {
            return new WP_Error(
                'attachment_creation_failed',
                __('Failed to create media attachment', 'wp-api-extended'),
                array('status' => 500)
            );
        }
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        $media_item = self::format_media_data($attachment_id);
        
        $response = array(
            'success' => true,
            'data' => $media_item,
            'message' => __('Media uploaded successfully', 'wp-api-extended')
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Get media list
     */
    public static function get_media($request) {
        $params = $request->get_params();
        
        $defaults = array(
            'page' => 1,
            'per_page' => 20,
            'orderby' => 'date',
            'order' => 'desc'
        );
        
        $args = wp_parse_args($params, $defaults);
        
        $query_args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => intval($args['per_page']),
            'paged' => intval($args['page']),
            'orderby' => sanitize_text_field($args['orderby']),
            'order' => sanitize_text_field($args['order'])
        );
        
        $media_query = new WP_Query($query_args);
        $media_items = array();
        
        if ($media_query->have_posts()) {
            while ($media_query->have_posts()) {
                $media_query->the_post();
                global $post;
                
                $media_items[] = self::format_media_data($post->ID);
            }
            wp_reset_postdata();
        }
        
        $response = array(
            'success' => true,
            'data' => array(
                'media' => $media_items,
                'pagination' => array(
                    'current_page' => intval($args['page']),
                    'per_page' => intval($args['per_page']),
                    'total_media' => $media_query->found_posts,
                    'total_pages' => $media_query->max_num_pages
                )
            )
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Get media item details
     */
    public static function get_media_item($request) {
        $media_id = $request['id'];
        
        if (!is_numeric($media_id) || $media_id <= 0) {
            return new WP_Error(
                'invalid_media_id',
                __('Invalid media ID', 'wp-api-extended'),
                array('status' => 400)
            );
        }
        
        $media_item = self::format_media_data($media_id);
        
        if (!$media_item) {
            return new WP_Error(
                'media_not_found',
                __('Media not found', 'wp-api-extended'),
                array('status' => 404)
            );
        }
        
        $response = array(
            'success' => true,
            'data' => $media_item
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Delete media
     */
    public static function delete_media($request) {
        $media_id = $request['id'];
        
        if (!is_numeric($media_id) || $media_id <= 0) {
            return new WP_Error(
                'invalid_media_id',
                __('Invalid media ID', 'wp-api-extended'),
                array('status' => 400)
            );
        }
        
        // Check if user can delete this media
        if (!current_user_can('delete_post', $media_id)) {
            return new WP_Error(
                'access_denied',
                __('You do not have permission to delete this media', 'wp-api-extended'),
                array('status' => 403)
            );
        }
        
        $result = wp_delete_attachment($media_id, true);
        
        if (!$result) {
            return new WP_Error(
                'media_deletion_failed',
                __('Failed to delete media', 'wp-api-extended'),
                array('status' => 500)
            );
        }
        
        $response = array(
            'success' => true,
            'message' => __('Media deleted successfully', 'wp-api-extended'),
            'data' => array(
                'deleted_media_id' => $media_id
            )
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Search posts
     */
    public static function search_posts($request) {
        $params = $request->get_params();
        
        $search_term = isset($params['s']) ? sanitize_text_field($params['s']) : '';
        
        if (empty($search_term)) {
            return new WP_Error(
                'missing_search_term',
                __('Please enter search keyword', 'wp-api-extended'),
                array('status' => 400)
            );
        }
        
        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $search_term,
            'posts_per_page' => 10,
            'orderby' => 'relevance'
        );
        
        $posts_query = new WP_Query($query_args);
        $posts = array();
        
        if ($posts_query->have_posts()) {
            while ($posts_query->have_posts()) {
                $posts_query->the_post();
                global $post;
                
                $posts[] = self::format_post_data($post);
            }
            wp_reset_postdata();
        }
        
        $response = array(
            'success' => true,
            'data' => array(
                'search_term' => $search_term,
                'posts' => $posts,
                'total_found' => $posts_query->found_posts
            )
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Get system information
     */
    public static function get_system_info($request) {
        global $wpdb;
        
        $system_info = array(
            'wordpress' => array(
                'version' => get_bloginfo('version'),
                'language' => get_bloginfo('language'),
                'charset' => get_bloginfo('charset'),
                'url' => get_bloginfo('url'),
                'name' => get_bloginfo('name')
            ),
            'php' => array(
                'version' => phpversion(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            ),
            'database' => array(
                'version' => $wpdb->db_version(),
                'charset' => $wpdb->charset,
                'table_prefix' => $wpdb->prefix
            ),
            'server' => array(
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown'
            ),
            'plugin' => array(
                'version' => WP_API_EXTENDED_VERSION,
                'status' => 'active'
            )
        );
        
        $response = array(
            'success' => true,
            'data' => $system_info
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Get categories
     */
    public static function get_categories($request) {
        $categories = get_categories(array(
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        $formatted_categories = array();
        
        foreach ($categories as $category) {
            $formatted_categories[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'count' => $category->count,
                'parent' => $category->parent
            );
        }
        
        $response = array(
            'success' => true,
            'data' => $formatted_categories
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Get tags
     */
    public static function get_tags($request) {
        $tags = get_tags(array(
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        $formatted_tags = array();
        
        foreach ($tags as $tag) {
            $formatted_tags[] = array(
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'description' => $tag->description,
                'count' => $tag->count
            );
        }
        
        $response = array(
            'success' => true,
            'data' => $formatted_tags
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Format post data
     */
    private static function format_post_data($post, $include_content = false) {
        $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'full');
        $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
        $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
        
        $formatted_post = array(
            'id' => $post->ID,
            'title' => get_the_title($post),
            'slug' => $post->post_name,
            'excerpt' => get_the_excerpt($post),
            'date' => get_the_date('c', $post),
            'modified' => get_the_modified_date('c', $post),
            'status' => $post->post_status,
            'author' => array(
                'id' => $post->post_author,
                'name' => get_the_author_meta('display_name', $post->post_author)
            ),
            'categories' => $categories,
            'tags' => $tags,
            'thumbnail' => $thumbnail_url ? $thumbnail_url : '',
            'permalink' => get_permalink($post),
            'edit_url' => get_edit_post_link($post->ID, '')
        );
        
        if ($include_content) {
            $formatted_post['content'] = apply_filters('the_content', $post->post_content);
        }
        
        return $formatted_post;
    }
    
    /**
     * Format media data
     */
    private static function format_media_data($media_id) {
        $media = get_post($media_id);
        
        if (!$media || $media->post_type !== 'attachment') {
            return false;
        }
        
        $media_url = wp_get_attachment_url($media_id);
        $media_metadata = wp_get_attachment_metadata($media_id);
        
        $formatted_media = array(
            'id' => $media_id,
            'title' => $media->post_title,
            'description' => $media->post_content,
            'caption' => $media->post_excerpt,
            'mime_type' => $media->post_mime_type,
            'url' => $media_url,
            'date' => get_the_date('c', $media),
            'modified' => get_the_modified_date('c', $media),
            'author' => array(
                'id' => $media->post_author,
                'name' => get_the_author_meta('display_name', $media->post_author)
            )
        );
        
        if ($media_metadata) {
            $formatted_media['metadata'] = array(
                'file' => $media_metadata['file'] ?? '',
                'width' => $media_metadata['width'] ?? 0,
                'height' => $media_metadata['height'] ?? 0,
                'filesize' => $media_metadata['filesize'] ?? 0
            );
            
            // Add image sizes
            if (isset($media_metadata['sizes'])) {
                $sizes = array();
                foreach ($media_metadata['sizes'] as $size_name => $size_data) {
                    $sizes[$size_name] = array(
                        'url' => wp_get_attachment_image_src($media_id, $size_name)[0],
                        'width' => $size_data['width'],
                        'height' => $size_data['height']
                    );
                }
                $formatted_media['sizes'] = $sizes;
            }
        }
        
        return $formatted_media;
    }
}