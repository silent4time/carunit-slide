<?php
/**
 * Plugin Name: کاریونیت اسلاید
 * Plugin URI: https://carunit.ir
 * Description: اسلایدر محصولات کاریونیت با تنظیمات کامل
 * Version: 1.0.0
 * Author: CarUnit
 * Text Domain: carunit-slide
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Carunit_Slide {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_shortcode( 'carunit_slide', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            'کاریونیت اسلاید',
            'کاریونیت اسلاید',
            'manage_options',
            'carunit-slide',
            array( $this, 'settings_page' ),
            'dashicons-images-alt2',
            30
        );
    }

    public function register_settings() {
        register_setting( 'carunit_slide_group', 'carunit_slide_settings' );
    }

    public function settings_page() {
        $settings = get_option( 'carunit_slide_settings', array() );
        ?>
        <div class="wrap">
            <h1>تنظیمات کاریونیت اسلاید</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'carunit_slide_group' ); ?>
                <table class="form-table">
                    <?php for ( $i = 1; $i <= 4; $i++ ) : ?>
                    <tr><th colspan="2"><h2>اسلایدر <?php echo $i; ?></h2></th></tr>
                    <tr>
                        <th>عنوان</th>
                        <td><input type="text" name="carunit_slide_settings[slider_<?php echo $i; ?>][title]" value="<?php echo esc_attr( $settings["slider_$i"]['title'] ?? '' ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>محصولات (ID با کاما جدا کنید)</th>
                        <td><input type="text" name="carunit_slide_settings[slider_<?php echo $i; ?>][products]" value="<?php echo esc_attr( $settings["slider_$i"]['products'] ?? '' ); ?>" class="regular-text" placeholder="11239,11238,11255"></td>
                    </tr>
                    <tr>
                        <th>حالت</th>
                        <td>
                            <select name="carunit_slide_settings[slider_<?php echo $i; ?>][autoplay]">
                                <option value="1" <?php selected( $settings["slider_$i"]['autoplay'] ?? 1, 1 ); ?>>خودکار</option>
                                <option value="0" <?php selected( $settings["slider_$i"]['autoplay'] ?? 1, 0 ); ?>>دستی</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>جهت</th>
                        <td>
                            <select name="carunit_slide_settings[slider_<?php echo $i; ?>][direction]">
                                <option value="rtl" <?php selected( $settings["slider_$i"]['direction'] ?? 'rtl', 'rtl' ); ?>>راست به چپ</option>
                                <option value="ltr" <?php selected( $settings["slider_$i"]['direction'] ?? 'rtl', 'ltr' ); ?>>چپ به راست</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>سرعت (میلی‌ثانیه)</th>
                        <td><input type="number" name="carunit_slide_settings[slider_<?php echo $i; ?>][speed]" value="<?php echo esc_attr( $settings["slider_$i"]['speed'] ?? 3000 ); ?>"></td>
                    </tr>
                    <?php endfor; ?>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>شورت‌کد: <code>[carunit_slide id="1"]</code> تا <code>[carunit_slide id="4"]</code></p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'carunit-slide', plugin_dir_url( __FILE__ ) . 'style.css', array(), '1.0' );
        wp_enqueue_script( 'carunit-slide', plugin_dir_url( __FILE__ ) . 'script.js', array( 'jquery' ), '1.0', true );
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array( 'id' => 1 ), $atts );
        $id = intval( $atts['id'] );
        $settings = get_option( 'carunit_slide_settings', array() );
        $slider = $settings["slider_$id"] ?? array();

        if ( empty( $slider['products'] ) ) return '';

        $product_ids = array_map( 'intval', explode( ',', $slider['products'] ) );
        $autoplay = ! empty( $slider['autoplay'] );
        $direction = $slider['direction'] ?? 'rtl';
        $speed = intval( $slider['speed'] ?? 3000 );
        $title = $slider['title'] ?? '';

        ob_start();
        ?>
        <div class="carunit-slide" data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>" data-speed="<?php echo $speed; ?>" data-direction="<?php echo esc_attr( $direction ); ?>">
            <?php if ( $title ) echo '<h3>' . esc_html( $title ) . '</h3>'; ?>
            <div class="carunit-slide-track">
                <?php foreach ( $product_ids as $pid ) :
                    $product = wc_get_product( $pid );
                    if ( ! $product ) continue;
                    $image = wp_get_attachment_image_src( $product->get_image_id(), 'medium' );
                ?>
                <div class="carunit-slide-item">
                    <a href="<?php echo get_permalink( $pid ); ?>">
                        <?php if ( $image ) : ?>
                        <img src="<?php echo esc_url( $image[0] ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>">
                        <?php endif; ?>
                        <div class="carunit-slide-info">
                            <h4><?php echo esc_html( $product->get_name() ); ?></h4>
                            <span class="price"><?php echo $product->get_price_html(); ?></span>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="carunit-slide-prev">‹</button>
            <button class="carunit-slide-next">›</button>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Carunit_Slide();
