<?php
/**
 * Plugin Name: Nível de Dificuldade
 * Description: Adiciona um campo e uma taxonomia personalizada "Nível de Dificuldade" aos posts.
 * Version: 1.2
 * Author: Heitor
 * Text Domain: nivel-dificuldade
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Evita acesso direto
}

if (!class_exists('ND_NivelDificuldadePlugin')) {
    /**
     * Classe principal do plugin
     */
    class ND_NivelDificuldadePlugin {

        public function __construct() {
            // Campo personalizado
            add_action('add_meta_boxes', [$this, 'adicionar_meta_box']);
            add_action('save_post', [$this, 'salvar_meta_box']);
            add_filter('the_content', [$this, 'exibir_nivel_dificuldade'], 9);

            // Taxonomia personalizada
            add_action('init', [$this, 'registrar_taxonomia']);
            add_filter('the_content', [$this, 'exibir_taxonomia_nivel'], 10);

            // Carregar tradução
            add_action('plugins_loaded', [$this, 'load_textdomain']);
        }

        /**
         * Carrega o textdomain para internacionalização
         */
        public function load_textdomain() {
            load_plugin_textdomain('nivel-dificuldade', false, dirname(plugin_basename(__FILE__)) . '/languages');
        }

        /**
         * Adicionamos a meta box do campo personalizado
         */
        public function adicionar_meta_box() {
            add_meta_box(
                'nd_nivel_dificuldade_meta_box',
                __('Nível de Dificuldade (Campo Personalizado)', 'nivel-dificuldade'),
                [$this, 'renderizar_meta_box'],
                'post',
                'side',
                'high'
            );
        }

        /**
         * Renderiza o campo personalizado na meta box
         * @param WP_Post $post
         */
        public function renderizar_meta_box($post) {
            wp_nonce_field('nd_salvar_nivel_dificuldade', 'nd_nivel_dificuldade_nonce');
            $valor = get_post_meta($post->ID, '_nd_nivel_dificuldade', true);
            echo '<label for="nd_nivel_dificuldade">' . esc_html__('Escolha o nível:', 'nivel-dificuldade') . '</label>';
            echo '<select name="nd_nivel_dificuldade" id="nd_nivel_dificuldade">';
            $opcoes = [__('Fácil', 'nivel-dificuldade'), __('Médio', 'nivel-dificuldade'), __('Difícil', 'nivel-dificuldade')];
            foreach ($opcoes as $opcao) {
                $selected = ($valor === $opcao) ? 'selected' : '';
                echo "<option value='" . esc_attr($opcao) . "' $selected>" . esc_html($opcao) . "</option>";
            }
            echo '</select>';
        }

        /**
         * Salva o valor do campo personalizado
         * @param int $post_id
         */
        public function salvar_meta_box($post_id) {
            if (!isset($_POST['nd_nivel_dificuldade_nonce']) || !wp_verify_nonce($_POST['nd_nivel_dificuldade_nonce'], 'nd_salvar_nivel_dificuldade')) {
                return;
            }
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
            if (isset($_POST['nd_nivel_dificuldade'])) {
                $nivel = sanitize_text_field($_POST['nd_nivel_dificuldade']);
                update_post_meta($post_id, '_nd_nivel_dificuldade', $nivel);
            } else {
                delete_post_meta($post_id, '_nd_nivel_dificuldade');
            }
        }

        /**
         * Exibe o campo personalizado no conteúdo do post
         * @param string $content
         * @return string
         */
        public function exibir_nivel_dificuldade($content) {
            if (is_single() && get_post_type() === 'post') {
                $nivel = get_post_meta(get_the_ID(), '_nd_nivel_dificuldade', true);
                if (!empty($nivel)) {
                    $nivel_html = "<p><strong>" . esc_html__('Nível de Dificuldade (Campo):', 'nivel-dificuldade') . "</strong> " . esc_html($nivel) . "</p>";
                    $content = $nivel_html . $content;
                }
            }
            return $content;
        }

        /**
         * Registramos a taxonomia e a personalização
         */
        public function registrar_taxonomia() {
            $labels = [
                'name'              => __('Níveis de Dificuldade', 'nivel-dificuldade'),
                'singular_name'     => __('Nível de Dificuldade', 'nivel-dificuldade'),
                'search_items'      => __('Procurar Níveis', 'nivel-dificuldade'),
                'all_items'         => __('Todos os Níveis', 'nivel-dificuldade'),
                'edit_item'         => __('Editar Nível', 'nivel-dificuldade'),
                'update_item'       => __('Atualizar Nível', 'nivel-dificuldade'),
                'add_new_item'      => __('Adicionar Novo Nível', 'nivel-dificuldade'),
                'new_item_name'     => __('Novo Nível', 'nivel-dificuldade'),
                'menu_name'         => __('Nível de Dificuldade', 'nivel-dificuldade'),
            ];

            $args = [
                'hierarchical'      => true,
                'labels'            => $labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'rewrite'           => ['slug' => 'nivel-dificuldade'],
                'show_in_rest'      => true,
                'public'            => true,
                'show_tagcloud'     => false,
            ];

            register_taxonomy('nd_nivel_dificuldade_tax', ['post'], $args);
        }

        /**
         * Exibimos a taxonomia no conteúdo do post
         * @param string $content
         * @return string
         */
        public function exibir_taxonomia_nivel($content) {
            if (is_single() && get_post_type() === 'post') {
                $terms = get_the_term_list(get_the_ID(), 'nd_nivel_dificuldade_tax', '<p><strong>' . esc_html__('Nível de Dificuldade (Taxonomia):', 'nivel-dificuldade') . '</strong> ', ', ', '</p>');
                if ($terms) {
                    $content = $terms . $content;
                }
            }
            return $content;
        }
    }

    /**
     * Aqui ativamos o plugin e registramos a taxonomia
     */
    function nd_nivel_dificuldade_ativar() {
        (new ND_NivelDificuldadePlugin())->registrar_taxonomia();
        flush_rewrite_rules();
    }
    register_activation_hook(__FILE__, 'nd_nivel_dificuldade_ativar');

    new ND_NivelDificuldadePlugin();
}
