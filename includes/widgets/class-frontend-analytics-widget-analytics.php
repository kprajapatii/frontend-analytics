<?php
/**
 * Displays The Analytics Widget.
 *
 * @package    frontend-analytics
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend_Analytics_Widget_Analytics class.
 */
class Frontend_Analytics_Widget_Analytics extends WP_Super_Duper {

	public $arguments;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$options = array(
			'textdomain'     => 'frontend-analytics',
			'block-icon'     => 'chart-bar',
			'block-category' => 'widgets',
			'block-keywords' => "['analytics','ga','google']",
			'block-output'  => array(
				'element::p'   => array(
					'element'	 => 'p',
					'content'	 => __( 'Frontend Analytics Button Placeholder', 'frontend-analytics' ),
					'title' 	 => '[%title%]',
					'user_roles' => '[%user_roles%]',
				)
			),
			'class_name'     => __CLASS__,
			'base_id'        => 'frontend_analytics',
			'name'           => __( 'Frontend Analytics', 'frontend-analytics' ),
			'widget_ops'     => array(
				'classname'     => 'frontend-analytics' . ( frontend_analytics_design_style() ? ' bsui' : '' ),
				'description'   => esc_html__( 'Show google analytics stats on your website front page.', 'frontend-analytics' ),
				'geodirectory'  => false,
			)
		);

		parent::__construct( $options );
	}

	/**
	 * Widget arguments.
	 */
	public function set_arguments() {
		$design_style = frontend_analytics_design_style();

		$arguments = array(
			'title'  => array(
				'title' => __( 'Title:', 'frontend-analytics' ),
				'desc' => __( 'The widget title:', 'frontend-analytics' ),
				'type' => 'text',
				'desc_tip' => true,
				'default'  => '',
				'advanced' => false
			),
			'height' => array(
				'title' => __( 'Chart Height:', 'frontend-analytics' ),
				'desc' => __( 'Chart height in px. Default: 200', 'frontend-analytics' ),
				'type' => 'number',
				'desc_tip' => true,
				'default' => '200',
				'advanced' => true
			),
			'button_text'  => array(
				'title' => __( 'Button text:', 'frontend-analytics' ),
				'desc' => __( 'The text to use for the button to show the analytics:', 'frontend-analytics' ),
				'type' => 'text',
				'placeholder' => __( 'Show Google Analytics', 'frontend-analytics' ),
				'desc_tip' => true,
				'default'  => '',
				'advanced' => true
			),
			'user_roles'  => array(
				'title' => __( 'Google Analytics visible to:', 'frontend-analytics' ),
				'desc' => __( 'Google Analytics will be visible to selected users only.', 'frontend-analytics' ),
				'type' => 'select',
				'options' =>  array(
					"administrator" => __( 'Administrator', 'frontend-analytics' ),
					"author" => __( 'Author or profile owner.', 'frontend-analytics' ),
					"all-logged-in" => __( 'Everyone logged in', 'frontend-analytics' ),
					"all" => __( 'Everyone', 'frontend-analytics' ),
				),
				'desc_tip' => true,
				'advanced' => false
				)
		);

		if ( $design_style ) {
			$arguments['btn_color'] = array(
				'type' => 'select',
				'title' => __( 'Button Color:', 'frontend-analytics' ),
				'desc' => __( 'Analytics button color.', 'frontend-analytics' ),
				'options' => array(
					'' => __( 'Default (primary)', 'frontend-analytics' ),
				) + geodir_aui_colors(),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'frontend-analytics' )
			);

			$arguments['btn_size'] = array(
				'type' => 'select',
				'title' => __( 'Button Size:', 'frontend-analytics' ),
				'desc' => __( 'Analytics button size.', 'frontend-analytics' ),
				'options' => array(
					'' => __( 'Default (medium)', 'frontend-analytics' ),
					'small' => __( 'Small', 'frontend-analytics' ),
					'medium' => __( 'Medium', 'frontend-analytics' ),
					'large' => __( 'Large', 'frontend-analytics' ),
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'frontend-analytics' )
			);

			$arguments['btn_alignment'] = array(
				'type' => 'select',
				'title' => __( 'Button Position:', 'frontend-analytics' ),
				'desc' => __( 'Analytics button alignment.', 'frontend-analytics' ),
				'options' => array(
					'' => __( 'Default (left)', 'frontend-analytics' ),
					'left' => __( 'Left', 'frontend-analytics' ),
					'center' => __( 'Center', 'frontend-analytics' ),
					'right' => __( 'Right', 'frontend-analytics' ),
					'block' => __( 'Block', 'frontend-analytics' ),
				),
				'default' => '',
				'desc_tip' => true,
				'advanced' => false,
				'group' => __( 'Design', 'frontend-analytics' )
			);
		}

		return $arguments;
	}

	/**
	 * This is the output function for the widget, shortcode and block (front end).
	 *
	 * @param array $args The arguments values.
	 * @param array $widget_args The widget arguments when used.
	 * @param string $content The shortcode content argument
	 *
	 * @return string
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {
		global $post, $preview;

		if ( $preview || empty( $post ) ) {
			return;
		}

		// options
		$defaults = array(
			'title' => '',
			'button_text' => '',
			'user_roles' => array( 'administrator' ),
			'height' => 200,
			// AUI
			'btn_color' => '',
			'btn_size' => '',
			'btn_alignment' => ''
		);

		/**
		 * Parse incoming $args into an array and merge it with $defaults
		 */
		$options = wp_parse_args( $args, $defaults );

		$allow_roles = ! empty( $options['user_roles'] ) ? $options['user_roles'] : array( 'administrator' );

		if ( ! is_array( $allow_roles ) ) {
			$allow_roles = explode( ",", $allow_roles );
		}

		$allow_roles = apply_filters( 'frontend_analytics_widget_user_roles', $allow_roles, $widget_args, $this->id_base );
		if ( empty( $allow_roles ) ) {
			return;
		}

		$options['user_roles'] = $allow_roles[0]; // @todo we need to make this work for arrays.

		if ( ! in_array( 'all', $allow_roles ) ) {

			if ( in_array( 'all-logged-in', $allow_roles ) ) {
				$user_id = is_user_logged_in() ? get_current_user_id() : 0;
				if ( empty( $user_id ) ) {
					return;
				}
			} elseif ( in_array( 'author', $allow_roles ) ) {
				if ( !current_user_can( 'manage_options' ) ) {
					$user_id = is_user_logged_in() ? get_current_user_id() : 0;
					if ( empty( $user_id ) ) {
						return;
					}
					$author_ID = get_the_author_meta( "ID" );
					if ( ! $author_ID && function_exists( 'bp_displayed_user_id' ) ) {
						$author_ID = bp_displayed_user_id();
					} elseif ( function_exists( 'uwp_get_displayed_user' ) && $post->post_type == 'page' && $post->ID == uwp_get_page_id( 'profile_page' ) ) {
						$displayed_user = uwp_get_displayed_user();
						if ( ! empty( $displayed_user->ID ) ) {
							$author_ID = $displayed_user->ID;
						}
					}
					if ( ! $author_ID || $author_ID != $user_id ) {
						return;
					}
				}
			} else {
				$user_id = is_user_logged_in() ? get_current_user_id() : 0;
				if ( empty( $user_id ) ) {
					return;
				}

				$allow = false;
				if ( ! empty( $post->post_author ) && $post->post_author == $user_id && in_array( 'owner', $allow_roles ) ) {
					$allow = true; // Listing owner
				}

				if ( ! $allow ) {
					$user_data = get_userdata( $user_id );
					if ( empty( $user_data->roles ) ) {
						return;
					}

					$allow = false;
					foreach ( $user_data->roles as $user_role ) {
						if ( in_array( $user_role, $allow_roles ) ) {
							$allow = true;
							break;
						}
					}
				}

				if ( ! $allow ) {
					return;
				}
			}

		}

		ob_start();

		frontend_analytics_display_analytics( $options );

		return ob_get_clean();
	}

}
