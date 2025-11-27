<?php
/**
 * Plugin Name: LLM News Studio Pro
 * Description: Automated news engine connecting Groq (AI) and Unsplash (Images).
 * Version: 5.2.0
 * Author: Gemini
 */

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('LLM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('LLM_PLUGIN_URL', plugin_dir_url(__FILE__));

// -------------------------------------------------------------------------
// 1. MENU & PAGE
// -------------------------------------------------------------------------

function llm_register_menu() {
    add_menu_page('LLM Studio', 'LLM News Studio', 'manage_options', 'llm-news-studio', 'llm_render_page', 'dashicons-admin-site-alt3', 25);
}
add_action('admin_menu', 'llm_register_menu');

// -------------------------------------------------------------------------
// 2. ASSETS (CSS)
// -------------------------------------------------------------------------

function llm_admin_assets($hook) {
    if ('toplevel_page_llm-news-studio' !== $hook) return;
    
    wp_enqueue_style(
        'llm-admin-css',
        LLM_PLUGIN_URL . 'admin-assets.css',
        array(),
        '5.2.0'
    );
}
add_action('admin_enqueue_scripts', 'llm_admin_assets');

// -------------------------------------------------------------------------
// 3. ACTION HANDLERS
// -------------------------------------------------------------------------

function llm_handle_post_actions() {
    if (!current_user_can('manage_options')) return;

    // SAVE SETTINGS ONLY
    if (isset($_POST['llm_action']) && $_POST['llm_action'] === 'save_settings' && check_admin_referer('llm_save_nonce')) {
        if (isset($_POST['llm_api_key'])) update_option('llm_api_key', sanitize_text_field($_POST['llm_api_key']));
        if (isset($_POST['llm_unsplash_key'])) update_option('llm_unsplash_key', sanitize_text_field($_POST['llm_unsplash_key']));
        if (isset($_POST['llm_daily_posts'])) update_option('llm_daily_posts', intval($_POST['llm_daily_posts']));
        if (isset($_POST['llm_topic'])) update_option('llm_topic', sanitize_textarea_field($_POST['llm_topic']));
        if (isset($_POST['llm_model'])) update_option('llm_model', sanitize_text_field($_POST['llm_model']));
        if (isset($_POST['llm_schedule_time'])) update_option('llm_schedule_time', sanitize_text_field($_POST['llm_schedule_time']));
        if (isset($_POST['llm_auto_publish'])) {
            update_option('llm_auto_publish', true);
        } else {
            update_option('llm_auto_publish', false);
        }

        // Reschedule cron with new time
        llm_reschedule_cron();

        wp_redirect(admin_url('admin.php?page=llm-news-studio&status=saved'));
        exit;
    }

    // GENERATE ONLY
    if (isset($_POST['llm_action']) && $_POST['llm_action'] === 'generate_preview' && check_admin_referer('llm_gen_nonce')) {
        $result = llm_generate_article();
        $status = $result['success'] ? 'gen_success' : 'gen_error';
        $msg = urlencode($result['message']);
        wp_redirect(admin_url("admin.php?page=llm-news-studio&status=$status&msg=$msg"));
        exit;
    }
}
add_action('admin_init', 'llm_handle_post_actions');

// -------------------------------------------------------------------------
// 4. CRON SCHEDULING SYSTEM (FIXED)
// -------------------------------------------------------------------------

function llm_reschedule_cron() {
    // Clear any existing schedule
    $timestamp = wp_next_scheduled('llm_daily_generation_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'llm_daily_generation_event');
    }
    
    $schedule_time = get_option('llm_schedule_time', '09:00');
    $next_run = llm_calculate_next_run($schedule_time);
    
    // Schedule the event
    wp_schedule_event($next_run, 'daily', 'llm_daily_generation_event');
    
    // Log for debugging
    error_log('LLM News Studio: Cron rescheduled to run at ' . date('Y-m-d H:i:s', $next_run));
}

function llm_calculate_next_run($time_string) {
    $timezone = wp_timezone();
    $now = new DateTime('now', $timezone);
    $schedule_time = DateTime::createFromFormat('H:i', $time_string, $timezone);
    
    if (!$schedule_time) {
        $schedule_time = DateTime::createFromFormat('H:i', '09:00', $timezone);
    }
    
    $schedule_time->setDate($now->format('Y'), $now->format('m'), $now->format('d'));
    
    // If scheduled time has passed today, schedule for tomorrow
    if ($schedule_time < $now) {
        $schedule_time->modify('+1 day');
    }
    
    return $schedule_time->getTimestamp();
}

// Activation hook
register_activation_hook(__FILE__, 'llm_news_studio_pro_activate');
function llm_news_studio_pro_activate() {
    // Set default options if not exists
    if (get_option('llm_model') === false) {
        update_option('llm_model', 'llama-3.1-8b-instant');
    }
    
    if (get_option('llm_schedule_time') === false) {
        update_option('llm_schedule_time', '09:00');
    }
    
    if (get_option('llm_auto_publish') === false) {
        update_option('llm_auto_publish', false);
    }
    
    llm_reschedule_cron();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'llm_news_studio_pro_deactivate');
function llm_news_studio_pro_deactivate() {
    $timestamp = wp_next_scheduled('llm_daily_generation_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'llm_daily_generation_event');
    }
}

// -------------------------------------------------------------------------
// 5. IMAGE LOGIC
// -------------------------------------------------------------------------

function llm_download_image($post_id, $query) {
    $client_id = get_option('llm_unsplash_key');
    if (empty($client_id)) return "Unsplash Key Missing.";

    // 1. Get Image URL from Unsplash
    $search_url = "https://api.unsplash.com/search/photos?query=" . urlencode($query) . "&per_page=1&orientation=landscape&client_id=" . $client_id;
    
    $response = wp_remote_get($search_url, ['timeout' => 15, 'sslverify' => false]);

    if (is_wp_error($response)) return "Unsplash Connection Failed: " . $response->get_error_message();
    
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) return "Unsplash API Error (Code $code). Check API Key.";

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['results'][0])) return "No images found for query: $query";

    $photo = $body['results'][0];
    $image_url = $photo['urls']['regular'];
    $desc = $photo['alt_description'] ?? 'News Image';
    $credits = "Photo by " . ($photo['user']['name'] ?? 'Unsplash') . " on Unsplash";

    // 2. Download to WordPress
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Download file to temp dir
    $tmp = download_url($image_url);

    if (is_wp_error($tmp)) return "Download Failed: " . $tmp->get_error_message();

    $file_array = [
        'name' => 'unsplash-' . $photo['id'] . '.jpg',
        'tmp_name' => $tmp
    ];

    // Import
    $id = media_handle_sideload($file_array, $post_id, $desc);

    if (is_wp_error($id)) {
        @unlink($file_array['tmp_name']);
        return "Media Import Failed: " . $id->get_error_message();
    }

    // Success: Set Featured & Caption
    set_post_thumbnail($post_id, $id);
    wp_update_post(['ID' => $id, 'post_excerpt' => $credits]);
    
    // Trigger Unsplash Download Count
    wp_remote_get($photo['links']['download_location'] . "&client_id={$client_id}");

    return "Success";
}

// -------------------------------------------------------------------------
// 6. GENERATION LOGIC
// -------------------------------------------------------------------------

function llm_generate_article() {
    $api_key = get_option('llm_api_key');
    $topic = get_option('llm_topic');
    $model = get_option('llm_model', 'llama-3.1-8b-instant');
    $auto_publish = get_option('llm_auto_publish', false);

    if (empty($api_key)) return ['success' => false, 'message' => 'Missing Groq API Key.'];

    // --- AI REQUEST ---
    $system = "You are a professional journalist. Return a JSON object.";
    $user = "Write a 600-word news article about: '$topic'.
    Return strictly valid JSON with these keys:
    - 'title' (String, engaging headline)
    - 'content' (String, HTML format <h2>, <p>, <ul>)
    - 'excerpt' (String, max 160 chars)
    - 'keywords' (String, comma separated tags)";

    $body = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user]
        ],
        'response_format' => [ "type" => "json_object" ]
    ];

    $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', [
        'body' => json_encode($body),
        'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Content-Type' => 'application/json'],
        'timeout' => 60
    ]);

    if (is_wp_error($response)) return ['success' => false, 'message' => 'AI Connection Error.'];
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($data['error'])) return ['success' => false, 'message' => 'AI Error: ' . $data['error']['message']];

    $content_str = $data['choices'][0]['message']['content'] ?? '';
    $json = json_decode($content_str, true);

    if (!isset($json['title'])) return ['success' => false, 'message' => 'Invalid JSON from AI.'];

    // --- CREATE POST ---
    $post_status = $auto_publish ? 'publish' : 'draft';
    
    $post_data = [
        'post_title'   => sanitize_text_field($json['title']),
        'post_content' => wp_kses_post($json['content'] ?? ''),
        'post_excerpt' => sanitize_text_field($json['excerpt'] ?? ''),
        'post_status'  => $post_status,
        'post_author'  => get_current_user_id(),
        'tags_input'   => sanitize_text_field($json['keywords'] ?? '')
    ];

    $pid = wp_insert_post($post_data);

    if (is_wp_error($pid)) return ['success' => false, 'message' => 'WP Save Error.'];

    // --- IMAGE ---
    // Try Title first, then Topic
    $img_status = llm_download_image($pid, $json['title']);
    if ($img_status !== "Success") {
        // Retry with Topic
        $img_status = llm_download_image($pid, $topic);
    }

    return ['success' => true, 'message' => "Post #$pid Created. Image Status: $img_status"];
}

// -------------------------------------------------------------------------
// 7. CRON EXECUTION (FIXED)
// -------------------------------------------------------------------------
add_action('llm_daily_generation_event', 'llm_cron_exec');
function llm_cron_exec() {
    // Check if API keys are set
    $api_key = get_option('llm_api_key');
    $unsplash_key = get_option('llm_unsplash_key');
    
    if (empty($api_key) || empty($unsplash_key)) {
        error_log('LLM News Studio: API keys not set. Skipping automatic generation.');
        return;
    }
    
    $count = get_option('llm_daily_posts', 1);
    
    error_log("LLM News Studio: Starting automatic generation of $count posts");
    
    // FIXED: Actually generate the specified number of posts
    $generated_count = 0;
    for ($i = 0; $i < $count; $i++) {
        $result = llm_generate_article();
        if ($result['success']) {
            $generated_count++;
        }
        // Add delay between posts to avoid rate limiting
        if ($i < $count - 1) {
            sleep(15); // Increased delay for better reliability
        }
    }
    
    // Log the execution
    error_log("LLM News Studio: Successfully generated $generated_count out of $count scheduled posts on " . date('Y-m-d H:i:s'));
    
    // Update last run time
    update_option('llm_last_run', current_time('mysql'));
}

// -------------------------------------------------------------------------
// 8. MANUAL CRON TESTING (NEW FEATURE)
// -------------------------------------------------------------------------
function llm_test_cron_manually() {
    if (!current_user_can('manage_options')) return;
    
    if (isset($_GET['llm_test_cron']) && $_GET['llm_test_cron'] === 'run') {
        llm_cron_exec();
        wp_redirect(admin_url('admin.php?page=llm-news-studio&status=gen_success&msg=' . urlencode('Manual cron test completed. Check error logs for details.')));
        exit;
    }
}
add_action('admin_init', 'llm_test_cron_manually');

// Include admin page
function llm_render_page() {
    include LLM_PLUGIN_PATH . 'admin-page.php';
}