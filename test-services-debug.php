<?php
/**
 * TEMPORARY DEBUG CODE - Copy this to your theme's functions.php
 * Remove this after debugging is complete
 */

// STEP 1: Show available CPTs in admin (check admin dashboard)
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'docontact_submissions') {
        $post_types = get_post_types(array('public' => true), 'names');
        echo '<div class="notice notice-info is-dismissible">';
        echo '<h3>🔍 DoContact Debug: Available Public Post Types</h3>';
        echo '<ul>';
        foreach ($post_types as $pt) {
            $exists = post_type_exists($pt);
            $count = wp_count_posts($pt);
            $highlight = (stripos($pt, 'service') !== false) ? ' <strong style="color:red;">← Service related!</strong>' : '';
            echo '<li>';
            echo '<strong>' . esc_html($pt) . '</strong>';
            echo ' - Published: ' . intval($count->publish) . ' posts';
            echo $highlight;
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
});

// STEP 2: Show debug info in footer (check page source - view source code)
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        echo "\n<!-- DoContact Services Debug Info -->\n";
        
        $post_types_to_check = array('services', 'service');
        foreach ($post_types_to_check as $pt) {
            echo "<!-- Checking: {$pt} -->\n";
            
            $exists = post_type_exists($pt);
            echo "<!-- Post type '{$pt}' exists: " . ($exists ? 'YES' : 'NO') . " -->\n";
            
            if ($exists) {
                $query = new WP_Query(array(
                    'post_type' => $pt,
                    'posts_per_page' => -1,
                    'post_status' => 'publish'
                ));
                
                echo "<!-- Found {$query->found_posts} published posts -->\n";
                
                if ($query->have_posts()) {
                    echo "<!-- Posts: -->\n";
                    while ($query->have_posts()) {
                        $query->the_post();
                        echo "<!--   - ID: " . get_the_ID() . " | Title: " . esc_html(get_the_title()) . " -->\n";
                    }
                    wp_reset_postdata();
                }
            }
        }
        echo "<!-- End DoContact Debug Info -->\n";
    }
});

// STEP 3: Add a test shortcode to see results on any page
add_shortcode('docontact_debug', function() {
    if (!current_user_can('manage_options')) {
        return '<p>Admin access required for debugging.</p>';
    }
    
    ob_start();
    echo '<div style="background: #f0f0f0; padding: 20px; border: 2px solid #333; margin: 20px 0;">';
    echo '<h2>DoContact Services CPT Debug Results</h2>';
    
    $post_types_to_check = array('services', 'service');
    
    foreach ($post_types_to_check as $post_type) {
        echo '<div style="margin: 15px 0; padding: 10px; background: white; border-left: 4px solid #0073aa;">';
        echo '<h3>Testing: <code>' . esc_html($post_type) . '</code></h3>';
        
        $exists = post_type_exists($post_type);
        echo '<p><strong>Post type exists:</strong> ' . ($exists ? '✅ YES' : '❌ NO') . '</p>';
        
        if ($exists) {
            $query = new WP_Query(array(
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            echo '<p><strong>Published posts found:</strong> ' . intval($query->found_posts) . '</p>';
            
            if ($query->have_posts()) {
                echo '<ul>';
                while ($query->have_posts()) {
                    $query->the_post();
                    echo '<li><strong>ID:</strong> ' . get_the_ID() . ' | <strong>Title:</strong> ' . esc_html(get_the_title()) . '</li>';
                }
                echo '</ul>';
                wp_reset_postdata();
            } else {
                echo '<p style="color: orange;">⚠️ Post type exists but no published posts found!</p>';
                echo '<p>Make sure you have at least one post with status "Published".</p>';
            }
        } else {
            echo '<p style="color: red;">❌ This post type does not exist. Check your CPT registration code.</p>';
        }
        
        echo '</div>';
    }
    
    echo '<hr>';
    echo '<h3>All Public Post Types:</h3>';
    echo '<ul>';
    $all_types = get_post_types(array('public' => true), 'names');
    foreach ($all_types as $type) {
        echo '<li><code>' . esc_html($type) . '</code></li>';
    }
    echo '</ul>';
    
    echo '</div>';
    return ob_get_clean();
});

