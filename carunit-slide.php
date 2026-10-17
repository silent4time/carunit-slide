<?php
/**
 * Plugin Name: کاریونیت اسلاید
 * Plugin URI: https://carunit.ir
 * Description: اسلایدر حرفه‌ای محصولات کاریونیت
 * Version: 1.1.2
 * Author: CarUnit
 * Text Domain: carunit-slide
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Carunit_Slide {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'wp_ajax_carunit_slide_search', array( $this, 'ajax_search_products' ) );
        add_shortcode( 'carunit_slide', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function add_admin_menu() {
        add_menu_page( 'کاریونیت اسلاید', 'کاریونیت اسلاید', 'manage_options', 'carunit-slide', array( $this, 'settings_page' ), 'dashicons-images-alt2', 30 );
    }

    public function register_settings() { register_setting( 'carunit_slide_group', 'carunit_slide_settings' ); }

    public function admin_assets( $hook ) {
        if ( $hook !== 'toplevel_page_carunit-slide' ) return;
        wp_enqueue_style( 'carunit-slide-admin', plugin_dir_url( __FILE__ ) . 'admin.css', array(), '1.1.2' );
        wp_enqueue_script( 'carunit-slide-admin', plugin_dir_url( __FILE__ ) . 'admin.js', array( 'jquery' ), '1.1.2', true );
        wp_localize_script( 'carunit-slide-admin', 'carunitSlide', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'carunit_slide' ) ) );
    }

    public function ajax_search_products() {
        check_ajax_referer( 'carunit_slide', 'nonce' );
        $term = sanitize_text_field( $_GET['term'] ?? '' );
        $args = array( 'post_type' => 'product', 'posts_per_page' => 15, 's' => $term, 'post_status' => 'publish' );
        if ( is_numeric( $term ) ) { $args['post__in'] = array( intval( $term ) ); unset( $args['s'] ); }
        $q = new WP_Query( $args );
        $results = array();
        foreach ( $q->posts as $p ) {
            $product = wc_get_product( $p->ID );
            $results[] = array( 'id' => $p->ID, 'name' => $p->post_title, 'price' => $product ? $product->get_price_html() : '', 'image' => get_the_post_thumbnail_url( $p->ID, 'thumbnail' ) );
        }
        wp_send_json( $results );
    }

    public function settings_page() {
        $settings = get_option( 'carunit_slide_settings', array() );
        ?>
        <div class="wrap carunit-admin">
            <div class="carunit-header">
                <h1>کاریونیت اسلاید <span class="ver">v1.1.2</span></h1>
                <p>اگر همه اسلایدرها خاموش باشند، بخش پرفروش‌ها مثل قبل نمایش داده می‌شود.</p>
            </div>
            <form method="post" action="options.php">
                <?php settings_fields( 'carunit_slide_group' ); ?>
                <div class="carunit-grid">
                <?php for ( $i = 1; $i <= 4; $i++ ) : $s = $settings["slider_$i"] ?? array(); $enabled = ! empty( $s['enabled'] ); ?>
                    <div class="carunit-card">
                        <div class="carunit-card-head"><h2>اسلایدر <?php echo $i; ?></h2>
                            <label class="carunit-switch"><input type="checkbox" name="carunit_slide_settings[slider_<?php echo $i; ?>][enabled]" value="1" <?php checked( $enabled ); ?>><span class="slider"></span></label>
                        </div>
                        <div class="carunit-field"><label>عنوان</label><input type="text" name="carunit_slide_settings[slider_<?php echo $i; ?>][title]" value="<?php echo esc_attr( $s['title'] ?? '' ); ?>" placeholder="مثلاً پرفروش‌های کانکتیکس"></div>
                        <div class="carunit-field carunit-search-wrap"><label>محصولات</label>
                            <input type="text" class="carunit-product-search" data-slider="<?php echo $i; ?>" placeholder="نام یا شناسه...">
                            <input type="hidden" name="carunit_slide_settings[slider_<?php echo $i; ?>][products]" class="carunit-product-ids" value="<?php echo esc_attr( $s['products'] ?? '' ); ?>">
                            <div class="carunit-selected" data-slider="<?php echo $i; ?>"></div>
                            <div class="carunit-results" data-slider="<?php echo $i; ?>"></div>
                        </div>
                        <div class="carunit-row">
                            <div class="carunit-field"><label>حالت</label><select name="carunit_slide_settings[slider_<?php echo $i; ?>][autoplay]"><option value="1" <?php selected( $s['autoplay'] ?? 1, 1 ); ?>>خودکار</option><option value="0" <?php selected( $s['autoplay'] ?? 1, 0 ); ?>>دستی</option></select></div>
                            <div class="carunit-field"><label>جهت</label><select name="carunit_slide_settings[slider_<?php echo $i; ?>][direction]"><option value="rtl" <?php selected( $s['direction'] ?? 'rtl', 'rtl' ); ?>>راست به چپ</option><option value="ltr" <?php selected( $s['direction'] ?? 'rtl', 'ltr' ); ?>>چپ به راست</option></select></div>
                            <div class="carunit-field"><label>سرعت</label><input type="number" name="carunit_slide_settings[slider_<?php echo $i; ?>][speed]" value="<?php echo esc_attr( $s['speed'] ?? 3000 ); ?>"></div>
                        </div>
                        <div class="carunit-preview" data-slider="<?php echo $i; ?>"><div class="preview-label">پیش‌نمایش</div><div class="preview-box">محصولی انتخاب نشده</div></div>
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
        wp_enqueue_style( 'carunit-slide', plugin_dir_url( __FILE__ ) . 'style.css', array(), '1.1.2' );
        wp_enqueue_script( 'carunit-slide', plugin_dir_url( __FILE__ ) . 'script.js', array( 'jquery' ), '1.1.2', true );
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
