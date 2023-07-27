<?php
/*
Plugin Name: Like Plugin
Description: Adicione botões de Like e Deslike para os visitantes classificarem as publicações.
Version: 1.0
Author: Eduardo Castro
*/

//  salvar os votos no post meta
function like_plugin_save_like($post_id) {
    $likes = (int) get_post_meta($post_id, 'like_count', true);
    $likes++;
    update_post_meta($post_id, 'like_count', $likes);
    error_log('Like salvo. Post ID: ' . $post_id . ' Likes: ' . $likes);
}

function like_plugin_save_dislike($post_id) {
    $dislikes = (int) get_post_meta($post_id, 'dislike_count', true);
    $dislikes++;
    update_post_meta($post_id, 'dislike_count', $dislikes);
    error_log('Dislike salvo. Post ID: ' . $post_id . ' Dislikes: ' . $dislikes);
}

// processar o voto via Ajax
add_action('wp_ajax_like_plugin_vote', 'like_plugin_ajax_vote');
add_action('wp_ajax_nopriv_like_plugin_vote', 'like_plugin_ajax_vote');
function like_plugin_ajax_vote() {
    if (!isset($_POST['post_id']) || !isset($_POST['vote_action']) || !isset($_POST['nonce'])) {
        wp_send_json_error('Erro ao processar o voto: campos não encontrados.');
    }

    $post_id = intval($_POST['post_id']);
    $action = $_POST['vote_action'];
    $nonce = $_POST['nonce'];

    if (!wp_verify_nonce($nonce, 'like_plugin_vote_nonce')) {
        wp_send_json_error('Erro de segurança. Tente novamente.');
    }

    error_log('Nonce verificado com sucesso.');

    // Verificar se o visitante já votou neste post (cookies)
    $voted_posts = isset($_COOKIE['like_plugin_voted_posts']) ? json_decode(stripslashes($_COOKIE['like_plugin_voted_posts']), true) : array();

    if (!in_array($post_id, $voted_posts)) {
        if ($action === 'like') {
            like_plugin_save_like($post_id);
        } elseif ($action === 'dislike') {
            like_plugin_save_dislike($post_id);
        }

        // Adicionar o post ID aos votos registrados
        $voted_posts[] = $post_id;
        setcookie('like_plugin_voted_posts', json_encode($voted_posts), time() + 3600 * 24 * 30, '/'); // Expira em 30 dias

        $likes = (int) get_post_meta($post_id, 'like_count', true);
        $dislikes = (int) get_post_meta($post_id, 'dislike_count', true);

        wp_send_json_success(array('likes' => $likes, 'dislikes' => $dislikes));
    } else {
        wp_send_json_error('Você já votou neste post.');
    }
}

// adicionar os botões Like e Dislike
function like_plugin_add_buttons_to_content($content) {
    if (is_singular('post')) {
        global $post;
        $post_id = $post->ID;
        $likes = (int) get_post_meta($post_id, 'like_count', true);
        $dislikes = (int) get_post_meta($post_id, 'dislike_count', true);

        // logs para testes 
        error_log('Post ID: ' . $post_id);
        error_log('Likes: ' . $likes);
        error_log('Dislikes: ' . $dislikes);

        $content .= '<div class="like-buttons">';
        $content .= '<div class="containerBtn"><button class="like-button" data-post-id="' . $post_id . '" data-action="like" data-nonce="' . wp_create_nonce('like_plugin_vote_nonce') . '">Like</button>';
        $content .= '<span class="like-count" data-post-id="' . $post_id . '">' . $likes . '</span></div>';
        $content .= '<div class="containerBtn"><button class="dislike-button" data-post-id="' . $post_id . '" data-action="dislike" data-nonce="' . wp_create_nonce('like_plugin_vote_nonce') . '">Dislike</button>';
        $content .= '<span class="dislike-count" data-post-id="' . $post_id . '">' . $dislikes . '</span></div>';
        $content .= '</div>';
    }

    return $content;
}
add_filter('the_content', 'like_plugin_add_buttons_to_content');

// registrar os estilos e scripts
function like_plugin_enqueue_scripts() {
    wp_enqueue_script('like-plugin-js', plugin_dir_url(__FILE__) . 'assets/like.js', array('jquery'), '1.0', true);

    // Passar a variável "ajaxurl" para o script JavaScript
    wp_localize_script('like-plugin-js', 'like_plugin_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));

    wp_enqueue_style('like-plugin-css', plugin_dir_url(__FILE__) . 'assets/like.css', array(), '1.0');
}
add_action('wp_enqueue_scripts', 'like_plugin_enqueue_scripts');

//shortcode

function like_plugin_top_liked_posts($atts) {
    $atts = shortcode_atts(array(
      'limit' => 5, // numero de posts
    ), $atts);

    $args = array(
      'post_type' => 'post',
      'post_status' => 'publish',
      'meta_key' => 'like_count',
      'orderby' => 'meta_value_num',
      'order' => 'DESC',
      'posts_per_page' => $atts['limit'],
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
      $output = '<ul>';

      while ($query->have_posts()) {
        $query->the_post();
        $likes = (int) get_post_meta(get_the_ID(), 'like_count', true);
        $output .= '<li>' . get_the_title() . ' - Likes: ' . $likes . '</li>';
      }

      $output .= '</ul>';

      wp_reset_postdata();

      return $output;
    } else {
      return 'Nenhum post encontrado.';
    }
}
add_shortcode('top-liked', 'like_plugin_top_liked_posts');