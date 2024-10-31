<?php
/**
 * Plugin Name: myCRED for BuddyPress Links
 * Plugin URI: http://mycred.me
 * Description: Allows you to reward users adding or voting on BuddyPress links.
 * Version: 1.1.1
 * Tags: mycred, points, buddypress, links
 * Author: myCRED
 * Author URI:  https://mycred.me
 * Author Email: support@mycred.me
 * Requires at least: WP 4.0
 * Tested up to: WP 5.8.1
 * Text Domain: mycred_bp_links
 * Domain Path: /lang
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! class_exists( 'myCRED_BP_Links' ) ) :
	final class myCRED_BP_Links {

		// Plugin Version
		public $version             = '1.1.1';

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		public $slug                = '';
		public $domain              = '';
		public $plugin              = NULL;
		public $plugin_name         = '';

		/**
		 * Setup Instance
		 * @since 1.0
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) )
				define( $name, $value );
		}

		/**
		 * Require File
		 * @since 1.0
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->slug        = 'mycred-buddypress-links';
			$this->plugin      = plugin_basename( __FILE__ );
			$this->domain      = 'mycred_bp_links';
			$this->plugin_name = 'myCRED for BuddyPress Links';

			$this->define_constants();

			add_filter( 'mycred_setup_hooks',    array( $this, 'register_hook' ) );
			add_action( 'mycred_init',           array( $this, 'load_textdomain' ) );
			add_action( 'mycred_all_references', array( $this, 'add_badge_support' ) );
			add_action( 'mycred_load_hooks',     'mycred_load_buddypress_links_hook' );

		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		public function define_constants() {

			$this->define( 'MYCRED_BP_LINKS_SLUG',    $this->slug );
			$this->define( 'MYCRED_DEFAULT_TYPE_KEY', 'mycred_default' );

		}

		/**
		 * Includes
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() { }

		/**
		 * Load Textdomain
		 * @since 1.0
		 * @version 1.0
		 */
		public function load_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

			load_textdomain( $this->domain, WP_LANG_DIR . '/' . $this->slug . '/' . $this->domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $this->domain, false, dirname( $this->plugin ) . '/lang/' );

		}

		/**
		 * Register Hook
		 * @since 1.0
		 * @version 1.0
		 */
		public function register_hook( $installed ) {

			if ( class_exists( 'myCRED_BuddyPress_Links' ) || ! function_exists( 'bp_links_setup_root_component' ) ) return $installed;

			$installed['hook_bp_links'] = array(
				'title'       => __( 'BuddyPress: Links', $this->domain ),
				'description' => __( 'Awards %_plural% for link related actions.', $this->domain ),
				'callback'    => array( 'myCRED_BuddyPress_Links' )
			);

			return $installed;

		}

		/**
		 * Add Badge Support
		 * @since 1.0
		 * @version 1.0
		 */
		public function add_badge_support( $references ) {

			if ( class_exists( 'myCRED_BuddyPress_Links' ) || ! function_exists( 'bp_links_setup_root_component' ) ) return $references;

			$references['new_link']       = __( 'New Links (BuddyPress Links)', $this->domain );
			$references['vote_link']      = __( 'Vote on Link (BuddyPress Links)', $this->domain );
			$references['vote_link_up']   = __( 'Vote Up Link (BuddyPress Links)', $this->domain );
			$references['vote_link_down'] = __( 'Vote Down Link (BuddyPress Links)', $this->domain );
			$references['update_link']    = __( 'Update Link (BuddyPress Links)', $this->domain );
			$references['delete_link']    = __( 'Delete Link (BuddyPress Links)', $this->domain );

			return $references;

		}

	}
endif;

function mycred_bp_links_plugin() {
	return myCRED_BP_Links::instance();
}
mycred_bp_links_plugin();

/**
 * BuddyPress Link Hook
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_load_buddypress_links_hook' ) ) :
	function mycred_load_buddypress_links_hook() {

		// If the hook has been replaced or if plugin is not installed, exit now
		if ( class_exists( 'myCRED_BuddyPress_Links' ) || ! function_exists( 'bp_links_setup_root_component' ) ) return;

		class myCRED_BuddyPress_Links extends myCRED_Hook {

			/**
			 * Construct
			 */
			function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

				parent::__construct( array(
					'id'       => 'hook_bp_links',
					'defaults' => array(
						'new_link'       => array(
							'creds'          => 1,
							'log'            => '%plural% for new Link',
							'limit'          => '0/x'
						),
						'vote_link'      => array(
							'creds'          => 1,
							'log'            => '%plural% for voting on a link',
							'limit'          => '0/x'
						),
						'vote_link_up'   => array(
							'creds'          => 1,
							'log'            => '%plural% for your link voted up',
							'limit'          => '0/x'
						),
						'vote_link_down' => array(
							'creds'          => 1,
							'log'            => '%plural% for your link voted down',
							'limit'          => '0/x'
						),
						'update_link'    => array(
							'creds'          => 1,
							'log'            => '%plural% for updating link',
							'limit'          => '0/x'
						),
						'delete_link'    => array(
							'creds'          => '-1',
							'log'            => '%singular% deduction for deleting a link'
						),
					)
				), $hook_prefs, $type );

			}

			/**
			 * Run
			 * @since 1.0
			 * @version 1.0
			 */
			public function run() {

				if ( $this->prefs['new_link']['creds'] != 0 )
					add_action( 'bp_links_create_complete',   array( $this, 'create_link' ) );

				add_action( 'bp_links_cast_vote_success', array( $this, 'vote_link' ) );

				if ( $this->prefs['update_link']['creds'] != 0 )
					add_action( 'bp_links_posted_update',     array( $this, 'update_link' ), 20, 4 );

				if ( $this->prefs['delete_link']['creds'] != 0 )
					add_action( 'bp_links_delete_link',       array( $this, 'delete_link' ) );

			}

			/**
			 * New Link
			 * @since 1.0
			 * @version 1.0
			 */
			public function create_link( $link_id ) {

				$user_id = bp_loggedin_user_id();

				// Check if user is excluded
				if ( $this->core->exclude_user( $user_id ) ) return;

				// Make sure this is unique event
				if ( ! $this->over_hook_limit( 'new_link', 'new_link', $user_id ) )
					$this->core->add_creds(
						'new_link',
						$user_id,
						$this->prefs['new_link']['creds'],
						$this->prefs['new_link']['log'],
						$link_id,
						'bp_links',
						$this->mycred_type
					);

			}

			/**
			 * Vote on Link
			 * @since 1.0
			 * @version 1.0
			 */
			public function vote_link( $link_id ) {

				$user_id = bp_loggedin_user_id();

				// Check if user is excluded
				if ( $this->core->exclude_user( $user_id ) ) return;

				// Get the vote
				$vote = '';
				if ( isset( $_REQUEST['up_or_down'] ) )
					$vote = substr( $_REQUEST['up_or_down'], 0, 4 );

				// First if we award points for voting, do so now
				if ( $this->prefs['vote_link']['creds'] != 0 && ! $this->over_hook_limit( 'vote_link', 'link_voting', $user_id ) )
					$this->core->add_creds(
						'link_voting',
						$user_id,
						$this->prefs['vote_link']['creds'],
						$this->prefs['vote_link']['log'],
						$link_id,
						'bp_links',
						$this->mycred_type
					);

				// Get link author
				if ( isset( $bp->links->current_link->user_id ) )
					$author = $bp->links->current_link->user_id;

				// Link author not found
				else return;

				// By default we do not allow votes on our own links
				if ( $author == $user_id && apply_filters( 'mycred_bp_link_self_vote', false ) === false ) return;

				// Up Vote
				if ( $vote == 'up' && $this->prefs['vote_link_up']['creds'] != 0 && ! $this->over_hook_limit( 'vote_link_up', 'link_voting', $author )  )
					$this->core->add_creds(
						'link_voting',
						$author,
						$this->prefs['vote_link_up']['creds'],
						$this->prefs['vote_link_up']['log'],
						$link_id,
						'bp_links',
						$this->mycred_type
					);

				// Down Vote
				elseif ( $vote == 'down' && $this->prefs['vote_link_down']['creds'] != 0 && ! $this->over_hook_limit( 'vote_link_down', 'link_voting', $author )  )
					$this->core->add_creds(
						'link_voting',
						$author,
						$this->prefs['vote_link_down']['creds'],
						$this->prefs['vote_link_down']['log'],
						$link_id,
						'bp_links',
						$this->mycred_type
					);

			}

			/**
			 * Update Link
			 * @since 1.0
			 * @version 1.0
			 */
			public function update_link( $content, $user_id, $link_id, $activity_id ) {

				// Check if user is excluded
				if ( $this->core->exclude_user( $user_id ) ) return;

				// Make sure this is unique event
				if ( ! $this->over_hook_limit( 'update_link', 'update_link', $user_id ) )
					$this->core->add_creds(
						'update_link',
						$user_id,
						$this->prefs['update_link']['creds'],
						$this->prefs['update_link']['log'],
						$activity_id,
						'bp_links',
						$this->mycred_type
					);

			}

			/**
			 * Delete Link
			 * @since 1.0
			 * @version 1.0
			 */
			public function delete_link( $link_id ) {

				$user_id = bp_loggedin_user_id();

				// Check if user is excluded
				if ( $this->core->exclude_user( $user_id ) ) return;

				// Make sure this is unique event
				if ( $this->core->has_entry( 'link_deletion', $link_id, $user_id ) ) return;

				// Execute
				$this->core->add_creds(
					'link_deletion',
					$user_id,
					$this->prefs['delete_link']['creds'],
					$this->prefs['delete_link']['log'],
					$link_id,
					'bp_links',
					$this->mycred_type
				);

			}

			/**
			 * Preferences
			 * @since 1.0
			 * @version 1.0
			 */
			public function preferences() {

				$prefs = $this->prefs;

?>
<!-- Creds for New Link -->
<label for="<?php echo $this->field_id( array( 'new_link', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for New Links', 'mycred_bp_links' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_link', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'new_link', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['new_link']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_link', 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred_bp_links' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'new_link', 'limit' ) ), $this->field_id( array( 'new_link', 'limit' ) ), $prefs['new_link']['limit'] ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_link', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred_bp_links' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_link', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'new_link', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['new_link']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<!-- Creds for Vote Link -->
<label for="<?php echo $this->field_id( array( 'vote_link', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for Vote on Link', 'mycred_bp_links' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'vote_link', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'vote_link', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['vote_link']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'vote_link', 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred_bp_links' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'vote_link', 'limit' ) ), $this->field_id( array( 'vote_link', 'limit' ) ), $prefs['vote_link']['limit'] ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'vote_link', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred_bp_links' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'vote_link', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'vote_link', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['vote_link']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<label for="<?php echo $this->field_id( array( 'vote_link_up', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% per received Vote', 'mycred_bp_links' ) ); ?></label>
<ol>
	<li>
		<label><?php _e( 'Vote Up', 'mycred_bp_links' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'vote_link_up', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'vote_link_up', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['vote_link_up']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'vote_link_up', 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred_bp_links' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'vote_link_up', 'limit' ) ), $this->field_id( array( 'vote_link_up', 'limit' ) ), $prefs['vote_link_up']['limit'] ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'vote_link_up', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred_bp_links' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'vote_link_up', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'vote_link_up', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['vote_link_up']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label><?php _e( 'Vote Down', 'mycred_bp_links' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'vote_link_down', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'vote_link_down', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['vote_link_down']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'vote_link_down', 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred_bp_links' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'vote_link_down', 'limit' ) ), $this->field_id( array( 'vote_link_down', 'limit' ) ), $prefs['vote_link_down']['limit'] ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'vote_link_down', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred_bp_links' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'vote_link_down', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'vote_link_down', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['vote_link_down']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<!-- Creds for Update Link -->
<label for="<?php echo $this->field_id( array( 'update_link', 'mycred_bp_links' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for Updating Links', 'mycred_bp_links' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'update_link', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'update_link', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['update_link']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'update_link', 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred_bp_links' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'update_link', 'limit' ) ), $this->field_id( array( 'update_link', 'limit' ) ), $prefs['update_link']['limit'] ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'update_link', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred_bp_links' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'update_link', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'update_link', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['update_link']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<!-- Creds for Deleting Links -->
<label for="<?php echo $this->field_id( array( 'delete_link', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for Deleting Links', 'mycred_bp_links' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_link', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_link', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['delete_link']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'delete_link', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred_bp_links' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_link', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_link', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['delete_link']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<?php

			}

			/**
			 * Sanitise Preferences
			 * @since 1.6
			 * @version 1.0
			 */
			function sanitise_preferences( $data ) {

				if ( isset( $data['new_link']['limit'] ) && isset( $data['new_link']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['new_link']['limit'] );
					if ( $limit == '' ) $limit = 0;
					$data['new_link']['limit'] = $limit . '/' . $data['new_link']['limit_by'];
					unset( $data['new_link']['limit_by'] );
				}

				if ( isset( $data['vote_link']['limit'] ) && isset( $data['vote_link']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['vote_link']['limit'] );
					if ( $limit == '' ) $limit = 0;
					$data['vote_link']['limit'] = $limit . '/' . $data['vote_link']['limit_by'];
					unset( $data['vote_link']['limit_by'] );
				}

				if ( isset( $data['vote_link_up']['limit'] ) && isset( $data['vote_link_up']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['vote_link_up']['limit'] );
					if ( $limit == '' ) $limit = 0;
					$data['vote_link_up']['limit'] = $limit . '/' . $data['vote_link_up']['limit_by'];
					unset( $data['vote_link_up']['limit_by'] );
				}

				if ( isset( $data['vote_link_down']['limit'] ) && isset( $data['vote_link_down']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['vote_link_down']['limit'] );
					if ( $limit == '' ) $limit = 0;
					$data['vote_link_down']['limit'] = $limit . '/' . $data['vote_link_down']['limit_by'];
					unset( $data['vote_link_down']['limit_by'] );
				}

				if ( isset( $data['update_link']['limit'] ) && isset( $data['update_link']['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['update_link']['limit'] );
					if ( $limit == '' ) $limit = 0;
					$data['update_link']['limit'] = $limit . '/' . $data['update_link']['limit_by'];
					unset( $data['update_link']['limit_by'] );
				}

				return $data;

			}
		}

	}
endif;
