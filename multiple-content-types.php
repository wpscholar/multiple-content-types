<?php

/*
 * Plugin Name: Multiple Content Types
 * Plugin URI: https://wordpress.org/plugins/multiple-content-types/
 * Description: Easily select which content types (custom post types) you want to display on your main blog and archive pages.
 * Version: 1.0.0
 * Author: Micah Wood
 * Author URI:  https://wpscholar.com
 * License:     GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: multiple-content-types
 */

class MultipleContentTypes {

	/**
	 * Setup hooks.
	 */
	public static function initialize() {
		if ( is_admin() ) {
			add_action( 'admin_init', 'MultipleContentTypes::_add_settings' );
		} else {
			add_action( 'pre_get_posts', 'MultipleContentTypes::_pre_get_posts' );
		}
	}

	/**
	 * Customize the query for specific archive pages to include additional post types.
	 *
	 * @internal
	 *
	 * @param WP_Query $query
	 */
	public static function _pre_get_posts( $query ) {

		if ( $query->is_main_query() ) {

			// Show only the desired custom post types on main blog and archive pages.
			if ( ( $query->is_home() || $query->is_date() || $query->is_author() ) ) {
				$query->set( 'post_type', get_option( 'mct_blog_post_types', array( 'post' ) ) );
			}

			// Automatically show all custom post types associated with the current taxonomy.
			if ( $query->is_tax() ) {
				$term = get_queried_object();
				if ( isset( $query->query[ $term->taxonomy ] ) ) {
					$taxonomy = get_taxonomy( $term->taxonomy );
					$query->set( 'post_type', $taxonomy->object_type );
				}
			}

		}

	}

	/**
	 * Register our setting and add to the 'Settings' -> 'Reading' page in the WordPress admin.
	 *
	 * @internal
	 */
	public static function _add_settings() {

		global $wp_version;

		$args = 'MultipleContentTypes::_sanitizeContentTypes';

		if ( version_compare( $wp_version, '4.7', '>=' ) ) {
			$args = array(
				'type'              => 'array',
				'description'       => __( 'An array of post types to be displayed on blog archive pages.', 'multiple-content-types' ),
				'sanitize_callback' => 'MultipleContentTypes::_sanitizeContentTypes',
				'default'           => array( 'post' ),
			);
		}

		register_setting( 'reading', 'mct_blog_post_types', $args );

		add_settings_field(
			'mct_blog_post_types',
			__( 'Content types to show on the main blog and archive pages', 'multiple-content-types' ),
			'MultipleContentTypes::_renderContentTypeCheckboxes',
			'reading'
		);

	}

	/**
	 * Sanitize (and validate) the content types.
	 *
	 * @internal
	 *
	 * @param array $content_types
	 *
	 * @return array
	 */
	public static function _sanitizeContentTypes( $content_types ) {
		$safe_content_types = array();

		if ( is_array( $content_types ) ) {
			foreach ( $content_types as $content_type ) {
				// Make sure post type exists and isn't the 'attachment' post type
				if ( post_type_exists( $content_type ) && 'attachment' !== $content_type ) {
					$post_type_obj = get_post_type_object( $content_type );
					// Make sure the post type is public
					if ( $post_type_obj->public ) {
						$safe_content_types[] = $content_type;
					}
				}
			}
		}

		// WordPress will display posts by default if nothing is selected, so our admin should reflect that.
		if ( empty( $safe_content_types ) ) {
			$safe_content_types[] = 'post';
		}

		return $safe_content_types;
	}

	/**
	 * Render the content type checkboxes in the admin.
	 *
	 * @internal
	 */
	public static function _renderContentTypeCheckboxes() {

		// Get currently selected post types.
		$blog_post_types = get_option( 'mct_blog_post_types', array( 'post' ) );

		// Get all public post types.
		$content_types = get_post_types( array( 'public' => true ) );

		// WordPress won't let you use attachments on blog archive pages.
		unset( $content_types['attachment'] );

		?>
        <fieldset>
			<?php foreach ( $content_types as $content_type ): ?>
				<?php $post_type = get_post_type_object( $content_type ); ?>
                <label class="widefat">
                    <input type="checkbox"<?php checked( in_array( $content_type, $blog_post_types ) ); ?>
                           name="mct_blog_post_types[]"
                           value="<?php echo esc_attr( $content_type ); ?>"/>
                    <span><?php echo esc_html( $post_type->label ); ?></span>
                </label>
			<?php endforeach; ?>
        </fieldset>
		<?php
	}

}

add_action( 'init', 'MultipleContentTypes::initialize' );