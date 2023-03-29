<?php
/**
 * Plugin Name: AutoTagWP
 * Plugin URI: https://github.com/odonline/wp-autotag-ai/
 * Description: Wordpress plugin that add AI logic for tag posts using OpenAI.
 * Version: 1.0.0
 * Author: Damián Ares
 * Author URI: https://github.com/odonline/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AutoTagWP {
    const API_ENDPOINT = 'https://api.openai.com/v1/completions';
    const MAX_TOKENS = 100;
    const TEMPERATURE = 0.5;
    const FRECUENCY_PENALTY = 0.8;
    const PRESENCE_PENALTY = 0;
    const N = 3;
    const STOP = array('.');

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'addMetaBox'));
        add_action('admin_menu', array($this, 'addSettingsPage'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('wp_ajax_autotagwp_autotag_post', array($this, 'autotagPost')); 

    }

    public function enqueueScripts() {
        global $post;
        if ($post && $post->post_type === 'post') {
            wp_enqueue_script('autotagwp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'));
            wp_localize_script('autotagwp-script', 'autotagwp', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'post_id' => $post->ID
            ));
        }
    }

    public function addSettingsPage() {
        add_submenu_page(
            'options-general.php',
            'AutoTagWP Settings',
            'AutoTagWP Settings',
            'manage_options',
            'autotagwp-settings',
            array($this, 'renderSettingsPage')
        );
    }

    public function renderSettingsPage() {
        // Check if user has permission to access settings page
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
    
        // Check if form was submitted
        if (isset($_POST['autotagwp_api_key'])) {
            // Save API key to database
            update_option('autotagwp_api_key', $_POST['autotagwp_api_key']);
            echo '<div class="notice notice-success"><p>API key updated successfully.</p></div>';
        }
    
        // Get current API key from database
        $current_api_key = get_option('autotagwp_api_key');
    
        // Render settings page
        ?>
        <div class="wrap">
            <h1>AutoTagWP Settings</h1>
            <form method="post">
                <?php wp_nonce_field('autotagwp_settings', 'autotagwp_nonce'); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="autotagwp_api_key">OpenAI API Key</label>
                            </th>
                            <td>
                                <input type="text" name="autotagwp_api_key" id="autotagwp_api_key" class="regular-text" value="<?php echo esc_attr($current_api_key); ?>">
                                <p class="description">Please, enter your OpenAI API key to enable auto-tagging.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    

    public function addMetaBox() {
        add_meta_box('autotagwp-meta-box', 'AutoTagWP', array($this, 'renderMetaBox'), 'post', 'side');
    }

    public function renderMetaBox() {
    // Retrieve post ID
        $post_id = get_the_ID();
    ?>
    <div>
        <input type="checkbox" id="autotagwp-countries" name="autotagwp-countries" value="1" checked >
        <label for="autotagwp-countries">Paises</label>
    </div>
    <div>
        <input type="checkbox" id="autotagwp-cities" name="autotagwp-cities" value="1" checked>
        <label for="autotagwp-cities">Ciudades</label>
    </div>
    <div>
        <input type="checkbox" id="autotagwp-people" name="autotagwp-people" value="1" checked>
        <label for="autotagwp-people">Personas</label> 
    </div>
    <div>
        <input type="checkbox" id="autotagwp-ent" name="autotagwp-ent" value="1">
        <label for="autotagwp-ent">Empresas</label>
    </div>

    <button class="button button-primary autotagwp-button" data-nonceid="<?php echo esc_attr(wp_create_nonce('autotagwp_autotag_post')); ?>" data-postid="<?php echo esc_attr($post_id); ?>">Auto-Tag Post</button>
    <?php
    }

    public function autotagPost() {
        if (!current_user_can('edit_post', $_POST['post_id'])) {
            wp_send_json_error('Unauthorized');
        }

        $addCountry = "true" == $_POST['countries']? "top 3 countries, ":"";
        $addCity   = "true" == $_POST['cities']? "top 3 cities, ":"";
        $addPeople  = "true" == $_POST['people']? "top 3 person names, ":"";
        $addEnterprises  = "true" == $_POST['enterprise']? "top 3 enterprises, ":"";

        // Get the text content of the post
        $text = (wp_strip_all_tags(get_post_field('post_content', $_POST['post_id'])));
        $text = preg_replace('/\s+/',' ', $text);
        $prompt = "Extract $addCountry $addCity $addPeople $addEnterprises joined all values as a coma separated values and nothing more, omit any extra text in the response from this text:\n\n" . $text;

        wp_send_json_error($prompt);
        die();
    
        // Call the OpenAI API to generate tags
        $response = wp_remote_post(self::API_ENDPOINT, array(
            'body' => json_encode(array(
                'model' => 'text-davinci-003',
                'prompt' => $prompt,
                'max_tokens' => self::MAX_TOKENS,
                'temperature' => self::TEMPERATURE
            )),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . get_option('autotagwp_api_key')
            )
        ));
        if (is_wp_error($response)) {
            wp_send_json_error('OpenAI API Error: ' . $response->get_error_message());
        }
    
        // Get the tags generated by OpenAI
        $tags = array();
        $data = json_decode($response['body'], true);
        $results = $data['choices'][0]['text'];
        $results = explode(",",str_replace("\n\n","", $results));

        foreach ($results as $tag) {
            $tag_slug = (trim($tag));
    
            // Check if the post already has the tag before adding it
            if (!has_tag($tag_slug, $_POST['post_id'])) {
                $tags[] = $tag_slug;
            }
        }
    
        // Add the tags to the post
        if (!empty($tags)) {
            wp_set_post_tags($_POST['post_id'], $tags);
            wp_send_json_success('Tags added successfully\n\n' . implode(",",$tags));
        } else {
            wp_send_json_error('No new tags added\n\n' . implode(",",$results));
        }
    }
    
}

