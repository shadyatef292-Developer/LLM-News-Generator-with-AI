<?php
if (!defined('ABSPATH')) {
    exit;
}

$api_key = get_option('llm_api_key', '');
$unsplash_key = get_option('llm_unsplash_key', '');
$topic = get_option('llm_topic', 'Small Business Trends');
$count = get_option('llm_daily_posts', 1);
$model = get_option('llm_model', 'llama-3.1-8b-instant');
$schedule_time = get_option('llm_schedule_time', '09:00');
$auto_publish = get_option('llm_auto_publish', false);
$last_run = get_option('llm_last_run', 'Never');

// Calculate next run time
$next_run = llm_calculate_next_run($schedule_time);
$next_run_formatted = date('M j, g:i A', $next_run);

// Get cron status
$cron_status = wp_next_scheduled('llm_daily_generation_event');
$cron_active = $cron_status ? '🟢 Active' : '🔴 Inactive';

// Display status messages
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'saved') {
        echo '<div class="llm-wrap"><div class="llm-notice success">🎉 Settings Saved Successfully! <strong>No posts were generated.</strong> Automated publishing is scheduled for later.</div></div>';
    } elseif ($_GET['status'] === 'gen_success') {
        $msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : '';
        echo '<div class="llm-wrap"><div class="llm-notice success">🎉 <strong>SUCCESS:</strong> ' . esc_html($msg) . '</div></div>';
    } elseif ($_GET['status'] === 'gen_error') {
        $msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : '';
        echo '<div class="llm-wrap"><div class="llm-notice error">⚠️ <strong>ERROR:</strong> ' . esc_html($msg) . '</div></div>';
    }
}
?>

<div class="llm-wrap">
    <div class="llm-header">
        <h1>🚀 LLM News Studio Pro</h1>
        <span>v5.2 • AI-Powered</span>
    </div>
    
    <div class="llm-body">
        <div class="llm-form-section">
            <form method="post" action="">
                <input type="hidden" name="llm_action" value="save_settings">
                <?php wp_nonce_field('llm_save_nonce'); ?>
                
                <div class="llm-field">
                    <label>🔑 Groq API Key (AI Text)</label>
                    <input type="password" name="llm_api_key" value="<?php echo esc_attr($api_key); ?>" required placeholder="Enter your Groq API key (starts with gsk_)">
                    <div class="llm-helper">📋 Required for AI content generation • <a href="https://console.groq.com/keys" target="_blank">Get Free API Key</a></div>
                </div>

                <div class="llm-field">
                    <label>🖼️ Unsplash Access Key (Images)</label>
                    <input type="password" name="llm_unsplash_key" value="<?php echo esc_attr($unsplash_key); ?>" required placeholder="Enter your Unsplash Access Key">
                    <div class="llm-helper">📷 Required for automatic images • <a href="https://unsplash.com/oauth/applications" target="_blank">Get Free Access Key</a></div>
                </div>

                <div class="llm-field">
                    <label>🤖 AI Model</label>
                    <select name="llm_model" required>
                        <option value="llama-3.1-8b-instant" <?php selected($model, 'llama-3.1-8b-instant'); ?>>Llama 3.1 8B Instant (Fastest)</option>
                        <option value="llama-3.2-3b-preview" <?php selected($model, 'llama-3.2-3b-preview'); ?>>Llama 3.2 3B Preview</option>
                        <option value="llama-3.2-1b-preview" <?php selected($model, 'llama-3.2-1b-preview'); ?>>Llama 3.2 1B Preview</option>
                        <option value="llama-3.3-70b-versatile" <?php selected($model, 'llama-3.3-70b-versatile'); ?>>Llama 3.3 70B Versatile (Highest Quality)</option>
                        <option value="mixtral-8x7b-32768" <?php selected($model, 'mixtral-8x7b-32768'); ?>>Mixtral 8x7B</option>
                        <option value="gemma2-9b-it" <?php selected($model, 'gemma2-9b-it'); ?>>Gemma2 9B IT</option>
                    </select>
                    <div class="llm-helper">🚀 Choose which AI model to use for content generation</div>
                </div>

                <div class="llm-field">
                    <label>💡 Topic Prompt</label>
                    <textarea name="llm_topic" rows="4" required placeholder="Enter your news topic or niche..."><?php echo esc_textarea($topic); ?></textarea>
                    <div class="llm-helper">✨ Be specific for better results! Example: "Latest digital marketing trends for e-commerce businesses"</div>
                </div>

                <div class="llm-field">
                    <label>📊 Daily Post Count</label>
                    <input type="number" name="llm_daily_posts" value="<?php echo esc_attr($count); ?>" min="1" max="10">
                    <div class="llm-helper">⚡ How many articles to generate automatically each day (1-10)</div>
                </div>

                <div class="llm-field">
                    <label>🕒 Daily Schedule Time</label>
                    <input type="time" name="llm_schedule_time" value="<?php echo esc_attr($schedule_time); ?>" required>
                    <div class="llm-helper">⏰ When should posts be generated automatically each day (24-hour format)</div>
                </div>

                <div class="llm-field">
                    <label>
                        <input type="checkbox" name="llm_auto_publish" value="1" <?php checked($auto_publish, true); ?>>
                        🚀 Auto-publish Posts
                    </label>
                    <div class="llm-helper">📢 If checked, posts will be published immediately. Otherwise, saved as drafts for review.</div>
                </div>

                <button type="submit" class="llm-btn-save">💾 Save Settings Only</button>
            </form>
        </div>

        <div>
            <div class="llm-sidebar-box llm-pulse">
                <h3>⚡ Instant Generator</h3>
                <p style="font-size:14px; color:#666; margin-bottom:20px; line-height:1.5;">Test your configuration immediately! Creates one complete article with AI content + featured image.</p>
                
                <form method="post" action="">
                    <input type="hidden" name="llm_action" value="generate_preview">
                    <?php wp_nonce_field('llm_gen_nonce'); ?>
                    <button type="submit" class="llm-btn-gen">🎬 Generate News Post Now</button>
                </form>
                
                <div  style="margin-top:15px; text-align:center;overflow: hidden;border-radius: 50px;">
                    <a href="<?php echo admin_url('admin.php?page=llm-news-studio&llm_test_cron=run'); ?>" class="llm-btn-gen" style="background:var(--accent); font-size:12px; padding:10px;">
                        🔧 Test Auto-Generation
                    </a>
                </div>
                
                <div class="llm-features">
                    <div class="llm-feature-badge">AI Content</div>
                    <div class="llm-feature-badge">Auto Images</div>
                    <div class="llm-feature-badge">SEO Ready</div>
                    <div class="llm-feature-badge">Instant</div>
                </div>
            </div>

            <div class="llm-sidebar-box">
                <h3>📈 System Status</h3>
                <ul class="llm-status-list">
                    <li>
                        <strong>🕒 Next Auto-run</strong>
                        <span class="llm-status-value"><?php echo $next_run_formatted; ?></span>
                    </li>
                    <li>
                        <strong>📅 Last Run</strong>
                        <span class="llm-status-value"><?php echo esc_html($last_run); ?></span>
                    </li>
                    <li>
                        <strong>🔧 Cron Status</strong>
                        <span class="llm-status-value"><?php echo $cron_active; ?></span>
                    </li>
                    <li>
                        <strong>📝 Posts Scheduled</strong>
                        <span class="llm-status-value"><?php echo esc_html($count); ?> / day</span>
                    </li>
                    <li>
                        <strong>🤖 AI Model</strong>
                        <span class="llm-status-value"><?php echo esc_html($model); ?></span>
                    </li>
                    <li>
                        <strong>🚀 Auto-publish</strong>
                        <span class="llm-status-value"><?php echo $auto_publish ? '✅ On' : '📝 Drafts'; ?></span>
                    </li>
                </ul>
            </div>

            <div class="llm-sidebar-box llm-glass">
                <h3>🎯 Quick Tips</h3>
                <ul style="font-size:13px; color:#666; padding-left:15px; margin:0;">
                    <li>Use specific, detailed topic prompts</li>
                    <li>Test with manual generation first</li>
                    <li>Check posts in "Drafts" after generation</li>
                    <li>Images auto-attach with proper credits</li>
                    <li>Set schedule for off-peak hours</li>
                    <li>Use "Test Auto-Generation" to debug</li>
                </ul>
            </div>
        </div>
    </div>
</div>