<?php
/**
 * Plugin Name: کاریونیت اسلاید
 * Plugin URI: https://carunit.ir
 * Description: اسلایدر حرفه‌ای محصولات کاریونیت
 * Version: 1.1.4
 * Author: CarUnit
 * Text Domain: carunit-slide
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Carunit_Slide {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_shortcode( 'carunit_slide', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function add_admin_menu() {
        add_menu_page( 'کاریونیت اسلاید', 'کاریونیت اسلاید', 'manage_options', 'carunit-slide', array( $this, 'settings_page' ), 'dashicons-images-alt2', 30 );
    }

    public function register_settings() { register_setting( 'carunit_slide_group', 'carunit_slide_settings' ); }

    public function admin_assets( $hook ) {
        if ( $hook !== 'toplevel_page_carunit-slide' ) return;
        wp_enqueue_style( 'vazirmatn', 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css', array(), null );
        wp_enqueue_style( 'carunit-slide-admin', plugin_dir_url( __FILE__ ) . 'admin.css', array( 'vazirmatn' ), '1.1.4' );
        wp_enqueue_script( 'carunit-slide-admin', plugin_dir_url( __FILE__ ) . 'admin.js', array( 'jquery' ), '1.1.4', true );
    }

    private function get_category_tree( $parent = 0 ) {
        $terms = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $parent, 'hide_empty' => false, 'orderby' => 'name' ) );
        $tree = array();
        if ( is_wp_error( $terms ) || empty( $terms ) ) return $tree;
        foreach ( $terms as $term ) {
            $children = $this->get_category_tree( $term->term_id );
            $products = array();
            if ( empty( $children ) ) {
                $posts = get_posts( array(
                    'post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish',
                    'tax_query' => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $term->term_id, 'include_children' => false ) ),
                    'orderby' => 'title', 'order' => 'ASC',
                ) );
                foreach ( $posts as $p ) {
                    $products[] = array( 'id' => $p->ID, 'name' => $p->post_title, 'image' => get_the_post_thumbnail_url( $p->ID, 'thumbnail' ) );
                }
            }
            if ( ! empty( $children ) || ! empty( $products ) ) {
                $tree[] = array( 'id' => $term->term_id, 'name' => $term->name, 'products' => $products, 'children' => $children );
            }
        }
        return $tree;
    }

    private function render_tree( $tree, $slider_id, $selected ) {
        if ( empty( $tree ) ) return;
        echo '<ul class="cu-tree">';
        foreach ( $tree as $node ) {
            $has = ! empty( $node['children'] ) || ! empty( $node['products'] );
            echo '<li class="cu-tree-item">';
            if ( $has ) echo '<span class="cu-toggle">+</span>';
            echo '<span class="cu-cat-name">' . esc_html( $node['name'] ) . '</span>';
            if ( ! empty( $node['children'] ) ) {
                echo '<div class="cu-children" style="display:none">';
                $this->render_tree( $node['children'], $slider_id, $selected );
                echo '</div>';
            }
            if ( ! empty( $node['products'] ) ) {
                echo '<ul class="cu-products" style="display:none">';
                foreach ( $node['products'] as $prod ) {
                    $checked = in_array( (int) $prod['id'], $selected, true ) ? 'checked' : '';
                    $img = $prod['image'] ? '<img src="' . esc_url( $prod['image'] ) . '" alt="">' : '';
                    echo '<li class="cu-prod-item"><label class="cu-prod-label"><input type="checkbox" class="cu-prod-check" data-slider="' . esc_attr( $slider_id ) . '" value="' . esc_attr( $prod['id'] ) . '" ' . $checked . '>' . $img . '<span class="cu-prod-name">' . esc_html( $prod['name'] ) . '</span></label></li>';
                }
                echo '</ul>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    public function settings_page() {
        $settings = get_option( 'carunit_slide_settings', array() );
        $tree = $this->get_category_tree( 0 );
        ?>
        <div class="wrap carunit-admin">
            <div class="carunit-header">
                <h1>کاریونیت اسلاید <span class="ver">v1.1.4</span></h1>
                <p>دسته‌ها را باز کنید تا به محصولات برسید. محصولات فقط در آخرین سطح نمایش داده می‌شوند.</p>
            </div>
            <form method="post" action="options.php">
                <?php settings_fields( 'carunit_slide_group' ); ?>
                <div class="carunit-grid">
                <?php for ( $i = 1; $i <= 4; $i++ ) :
                    $s = $settings["slider_$i"] ?? array();
                    $enabled = ! empty( $s['enabled'] );
                    $selected = array_filter( array_map( 'intval', explode( ',', $s['products'] ?? '' ) ) );
                ?>
                    <div class="carunit-card">
                        <div class="carunit-card-head">
                            <h2>اسلایدر <?php echo $i; ?></h2>
                            <label class="carunit-switch"><input type="checkbox" name="carunit_slide_settings[slider_<?php echo $i; ?>][enabled]" value="1" <?php checked( $enabled ); ?>><span class="slider"></span></label>
                        </div>
                        <div class="carunit-field"><label>عنوان</label><input type="text" name="carunit_slide_settings[slider_<?php echo $i; ?>][title]" value="<?php echo esc_attr( $s['title'] ?? '' ); ?>" placeholder="مثلاً پرفروش‌های کانکتیکس"></div>
                        <div class="carunit-field">
                            <label>انتخاب محصولات</label>
                            <input type="hidden" name="carunit_slide_settings[slider_<?php echo $i; ?>][products]" class="carunit-product-ids" value="<?php echo esc_attr( $s['products'] ?? '' ); ?>">
                            <div class="cu-tree-wrap" data-slider="<?php echo $i; ?>"><?php $this->render_tree( $tree, $i, $selected ); ?></div>
                        </div>
                        <div class="carunit-row">
                            <div class="carunit-field"><label>حالت</label><select name="carunit_slide_settings[slider_<?php echo $i; ?>][autoplay]"><option value="1" <?php selected( $s['autoplay'] ?? 1, 1 ); ?>>خودکار</option><option value="0" <?php selected( $s['autoplay'] ?? 1, 0 ); ?>>دستی</option></select></div>
                            <div class="carunit-field"><label>جهت</label><select name="carunit_slide_settings[slider_<?php echo $i; ?>][direction]"><option value="rtl" <?php selected( $s['direction'] ?? 'rtl', 'rtl' ); ?>>راست به چپ</option><option value="ltr" <?php selected( $s['direction'] ?? 'rtl', 'ltr' ); ?>>چپ به راست</option></select></div>
                            <div class="carunit-field"><label>سرعت (ms)</label><input type="number" name="carunit_slide_settings[slider_<?php echo $i; ?>][speed]" value="<?php echo esc_attr( $s['speed'] ?? 3000 ); ?>"></div>
                        </div>
                        <div class="carunit-shortcode">شورت‌کد: <code>[carunit_slide id="<?php echo $i; ?>"]</code></div>
                    </div>
                <?php endfor; ?>
                </div>
                <div class="carunit-save"><?php submit_button( 'ذخیره تنظیمات', 'primary carunit-btn', 'submit', false ); ?></div>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'carunit-slide', plugin_dir_url( __FILE__ ) . 'style.css', array(), '1.1.4' );
        wp_enqueue_script( 'carunit-slide', plugin_dir_url( __FILE__ ) . 'script.js', array( 'jquery' ), '1.1.4', true );
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array( 'id' => 1 ), $atts );
        $id = intval( $atts['id'] );
        $settings = get_option( 'carunit_slide_settings', array() );
        $slider = $settings["slider_$id"] ?? array();
        if ( empty( $slider['enabled'] ) || empty( $slider['products'] ) ) return '';
        $product_ids = array_filter( array_map( 'intval', explode( ',', $slider['products'] ) ) );
        if ( empty( $product_ids ) ) return '';
        $autoplay = ! empty( $slider['autoplay'] );
        $direction = $slider['direction'] ?? 'rtl';
        $speed = intval( $slider['speed'] ?? 3000 );
        $title = $slider['title'] ?? '';
        ob_start();
        ?>
        <div class="carunit-slide" data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>" data-speed="<?php echo $speed; ?>" data-direction="<?php echo esc_attr( $direction ); ?>">
            <?php if ( $title ) echo '<h3 class="carunit-slide-title">' . esc_html( $title ) . '</h3>'; ?>
            <div class="carunit-slide-track">
                <?php foreach ( $product_ids as $pid ) : $product = wc_get_product( $pid ); if ( ! $product ) continue; $image = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail' ); ?>
                <div class="carunit-slide-item"><a href="<?php echo get_permalink( $pid ); ?>"><?php if ( $image ) : ?><img src="<?php echo esc_url( $image[0] ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>"><?php endif; ?><div class="carunit-slide-info"><h4><?php echo esc_html( $product->get_name() ); ?></h4><span class="price"><?php echo $product->get_price_html(); ?></span></div></a></div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="carunit-slide-prev">‹</button>
            <button type="button" class="carunit-slide-next">›</button>
        </div>
        <?php
        return ob_get_clean();
    }
}
new Carunit_Slide();
