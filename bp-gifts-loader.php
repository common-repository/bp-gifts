<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if(!class_exists('BP_GIFTS_EXT')):
	class BP_GIFTS_EXT{
	  /**
	   * @var array $_instance   Class Instance
	   */
		protected static $_instance = null;
	  /**
	   * @var string $basename  Plugin Basename
	   */
		public $basename;
	  /**
	   * @var string $post_type  Post type
	   */
		public $post_type;
	  /**
	   * @var array $gifts  Array of all active gifts
	   */
		public $gifts = null;
		/**
		 * Main BP Gifts Instance
		 *
		 * @since 1.0.0
		 *
		 * @return BP Gifts - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * BP Gifts Constructor.
		 *
		 * @since 1.0.0
		 *
		 */
		public function __construct(){
			if($this->check_requirements()){
				$this->define_constants();
				$this->get_post_type();
				$this->includes();
				$this->init_hooks();
				$this->gifts = $this->get_all_gifts();
				do_action( 'bp_gifts_loaded' );
			}else{
				$this->display_requirement_message();
			}
		}
		/**
		*	Check if BuddyPress is activated
		*	@return boolean
		 *
		 * @since 1.0.0
		 *
		 */
		public function check_requirements(){
			if (!in_array( 'buddypress/bp-loader.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				return false;
			}
			return true;
		}
		/**
		 * Initiate Requirement Notice
		 *
		 * @since 1.0.0
		 *
		 */
		public function display_requirement_message(){
			add_action('admin_notices', array($this, 'display_admin_notice'));
		}
		/**
		 * Print Requirement Notice
		 *
		 * @since 1.0.0
		 *
		 */
		public function display_admin_notice(){
				if(current_user_can('manage_options')):
				echo '<div class="error"><p>'; 
				echo __('Please install and activate <strong>BuddyPress</strong> to use BP Gifts', 'bp-gifts');
				echo "</p></div>";
				endif;
		}
		/**
		 * Define BP Gifts Constants
		 *
		 * @since 1.0.0
		 *
		 */
		private function define_constants(){
			$this->define( 'BP_GIFTS_DIR', plugin_dir_path( __FILE__ ) );
			$this->define( 'BP_GIFTS_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'BP_GIFTS_BASENAME', plugin_basename( __FILE__ ) );
		}
		/**
		 * Define constant if not already set
		 * @param  string $name
		 * @param  string|bool $value
		 *
		 * @since 1.0.0
		 *
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}
		/**
		 * Include all necessary files
		 *
		 * @since 1.0.0
		 *
		 */
		public function includes(){
			if(is_admin()){
				require_once(BP_GIFTS_DIR.'admin/class-bp_gifts-admin.php');
			}
		}
		/**
		 * Initiate Hooks
		 *
		 * @since 1.0.0
		 *
		 */
		public function init_hooks(){
			add_action( 'plugins_loaded', array($this, 'plugin_load_textdomain'));
			add_action( 'init', array($this, 'register_post_type'));
			add_action( 'bp_after_messages_compose_content', array($this, 'render_gift_composer'));
			add_action( 'bp_after_message_reply_box', array($this, 'render_gift_composer'));
			add_action( 'wp_enqueue_scripts', array($this, 'add_scripts'));
			add_action( 'messages_message_after_save', array($this,  'send_gift'), 12, 1);
			add_action( 'bp_after_message_content', array($this, 'display_gift'));
			add_action( 'save_post', array($this, 'update_transient' ), 12, 2);
		}
		/**
		 * Include Translation file
		 *
		 * @since 1.0.0
		 *
		 */
		public function plugin_load_textdomain(){
			load_plugin_textdomain( 'bp-gifts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
		}
		/**
		 * Set Post Type for the plugin
		 *
		 * @since 1.0.0
		 *
		 */
		public function get_post_type(){
			$this->post_type = apply_filters('bp_gifts_post', 'bp_gifts');
		}
		/**
		 * Register Post Type for Gifts
		 *
		 * @since 1.0.0
		 *
		 */
		public function register_post_type(){
			$labels = array(
			'name'               => _x( 'Gifts', 'post type general name', $this->basename ),
			'singular_name'      => _x( 'Gift', 'post type singular name', $this->basename ),
			'menu_name'          => _x( 'Gifts', 'admin menu', $this->basename ),
			'name_admin_bar'     => _x( 'Gift', 'add new on admin bar', $this->basename ),
			'add_new'            => _x( 'Add New', 'Gift', $this->basename ),
			'add_new_item'       => __( 'Add New Gift', $this->basename ),
			'new_item'           => __( 'New Gift', $this->basename ),
			'edit_item'          => __( 'Edit Gift', $this->basename ),
			'view_item'          => __( 'View Gift', $this->basename ),
			'all_items'          => __( 'All Gifts', $this->basename ),
			'search_items'       => __( 'Search Gifts', $this->basename ),
			'parent_item_colon'  => __( 'Parent Gifts:', $this->basename ),
			'not_found'          => __( 'No gifts found.', $this->basename ),
			'not_found_in_trash' => __( 'No gifts found in Trash.', $this->basename )
		);
	
		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'bp_gifts' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'thumbnail' )
		);
		register_post_type( $this->post_type, $args );	
		}
		/**
		 * Save all gifts to transients
		 *
		 * @since 1.0.0
		 *
		 */
		public function update_transient($post_id, $post){
			// If this isn't a 'gifts' post, don't update it.
			if ( $this->post_type != $post->post_type ) {
				return;
			}
			//If this a revision, skip
			if ( wp_is_post_revision( $post_id ) ){
				return;
			}
			set_transient( 'sp_bp_gifts_array', false);
			$gifts = $this->get_all_gifts();
			set_transient( 'sp_bp_gifts_array', $gifts );
		}
		/**
		 * Fetches all gifts on site
		 *
		 * @since 1.0.0
		 *
		 */
		public function get_all_gifts(){
			// Check for the cached gifts key in the 'bp_gifts' group.
    		
			//print_r($gifts);
			 // If nothing is found, build the object.
    		if ( false === ( $all_gifts = get_transient( 'sp_bp_gifts_array' )) ) {
				global $wpdb;
				//query
				$query = apply_filters('bp_gifts_get_query', 'SELECT post_title AS name, ID AS id FROM '.$wpdb->prefix.'posts WHERE post_type="'.$this->post_type.'" AND post_status="publish"');
				$results = $wpdb->get_results($query);
				//$gifts = array();
				if(!empty($results)):
					foreach($results as $row){
						$post_thumbnail_id = get_post_thumbnail_id( $row->id );
						$image_attributes = wp_get_attachment_image_src( $post_thumbnail_id, 'thumbnail' );
						if(empty($image_attributes)){
							continue;
						}
						$gifts[] = array(
							'id' => $row->id,
							'name' => $row->name,
							'image' => $image_attributes[0]
						);
					}
				endif;
				$all_gifts = $gifts;
				//wp_cache_set( 'prefix_top_commented_posts', $gifts, 'top_posts' );
				set_transient( 'sp_bp_gifts_array', $all_gifts, '', 24 * HOUR_IN_SECONDS );
			}
			return $all_gifts;
		}
		/**
		 *	Enqueue all Javascript and CSS
		 *
		 * @since 1.0.0
		 *
		 */
		public function add_scripts() {
			wp_enqueue_script(
				'bp-gift-modal',
				plugins_url( '/assets/jquery.easyModal.js' , __FILE__ ),
				array( 'jquery' )
			);
			wp_enqueue_script(
				'bp-gift-list',
				plugins_url( '/assets/list.min.js' , __FILE__ ),
				array( 'jquery' )
			);
			wp_enqueue_script(
				'bp-gift-list-pagination',
				plugins_url( '/assets/list.pagination.min.js' , __FILE__ ),
				array( 'jquery' )
			);
		}
		/**
		 *	Gift Picker
		 *
		 * @since 1.0.0
		 *
		 */
		public function render_gift_composer(){
			$gifts = $this->gifts;
			
			if(empty($gifts)){
				return;
			}
			?>
            <style type="text/css">
			.easy-modal,
			.easy-modal-animated {
				width: 600px;
				padding: 2em;
				box-shadow: 1px 1px 3px rgba(0,0,0,0.35);
				background-color: white;
			}
			#bpmodalbox h3 {
				margin: 0px;
			}
			li.bp-gift-item{
				float: left;
				margin: 4px 0px!important;
				list-style: none;
				width: 33.33%;
				text-align: center;
			}
			.bp-gift-item:after {
			  content: "";
			  display: table;
			  clear: both;
			}
			.bp-gift-pagination {
				text-align: center;
				margin: 20px
			}
			.bp-gift-pagination a, .bp-gift-pagination strong {
				background: #fff;
				display: inline-block;
				margin-right: 3px;
				padding: 4px 12px;
				text-decoration: none;
				line-height: 1.5em;
				
				-webkit-border-radius: 3px;
				-moz-border-radius: 3px;
				border-radius: 3px;
			}
			.bp-gift-pagination a:hover {
				background-color: #BEBEBE;
				color: #fff;
			}
			.bp-gift-pagination a:active {
				background: rgba(190, 190, 190, 0.75);
			}
			.bp-gift-pagination strong {
				color: #fff;
				background-color: #BEBEBE;
			}
			.bp-gift-pagination li {
			  display:inline-block;
			  padding:5px;
			}
			.bp-gift-item-ele:hover{
				border: 2px solid #ccc;
			}
			.bp-gift-item-ele {
				cursor:pointer;
				border: 2px solid #fff;
				margin: 0px 10px;
				padding: 10px;
			}
			</style>
            <label><a href="" class="button bp-send-gift-btn"><?php _e( 'Send a Gift', 'bp-gifts' ); ?></a></label>
            <div class="bp-gift-edit-container"></div>
            <div class="easy-modal" id="bpmodalbox">
			<div class="bp-modal-inner">
				<h3><?php _e('Select a gift', 'bp-gifts'); ?></h3>
                <div class="bp-gifts-list" id="bp-gifts-list">
                	<?php if(!empty($gifts)):
					?>
                    <ul class="list">
                    <?php
						foreach($gifts as $gift):
					?>
                    <li class="bp-gift-item">
                    	<div class="bp-gift-item-ele"  data-id="<?php echo $gift['id']; ?>" data-image="<?php echo $gift['image']; ?>">
                            <img  src="<?php echo $gift['image']; ?>" />
                            <div class="bp-gift-title">
                                <?php echo $gift['name']; ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach;
					?>
                    </ul>
                    <?php
					endif;
					?>
                <div class="clear clearfix"></div>
                <ul class="bp-gift-pagination"></ul>
                </div>
            </div>
            </div>
			<script type="text/javascript">
			jQuery("#bpmodalbox").easyModal();
			
			jQuery(document).ready(function($) {
				var paginationBottomOptions = {
				name: "bp-gift-pagination",
				paginationClass: "bp-gift-pagination"
			  };
				var giftList = new List('bp-gifts-list', {
				  valueNames: [ 'bp-gift-title', 'category' ],
				  page: 6,
				  plugins: [ 
				  	 ListPagination(paginationBottomOptions) 
				  ] 
				});

                jQuery(document).on("click", ".bp-send-gift-btn", function(event){
					event.preventDefault();
					var gifts = '<?php echo json_encode($gifts); ?>';
					gifts = jQuery.parseJSON(gifts);
					jQuery("#bpmodalbox").trigger('openModal');
				});
				 jQuery(document).on("click", ".bp-gift-item-ele", function(event){
					event.preventDefault();
				 	var image = jQuery(this).data("image");
					var id = jQuery(this).data("id");
					var html = '<div class="bp-gift-holder">'+
					'<img src="' + image + '" />'+
					'<div class="bp-gift-remover"><a href="#" class="bp-gift-remove"><?php _e('Remove', 'bp-gifts'); ?></a></div>'+
					'<input type="hidden" name="bp_gift_id" value="' + id + '" />'+
					'</div>';
					jQuery('.bp-gift-edit-container').html(html);
					jQuery("#bpmodalbox").trigger('closeModal');
				 });
				 jQuery(document).on("click", ".bp-gift-remove", function(event){
				 	event.preventDefault();
					jQuery(this).closest(".bp-gift-holder").slideUp().remove();
				 });
            });
			</script>
            <?php
		}
		/**
		 *	Save gift to Message meta
		 *
		 * @since 1.0.0
		 *
		 */
		public function send_gift($message){
			if(isset($_POST['bp_gift_id'])):
				bp_messages_update_meta($message->id, '_bp_gift', (int)$_POST['bp_gift_id']);
			endif;
		}
		/**
		 *	Renders the gift on single message page
		 *
		 * @since 1.0.0
		 *
		 */
		public function display_gift(){
			$message_id = bp_get_the_thread_message_id();
			$gift = bp_messages_get_meta( $message_id, $meta_key = '_bp_gift', $single = true );
			if($gift){
				$post_thumbnail_id = get_post_thumbnail_id( $gift );
				$image_attributes = wp_get_attachment_image_src( $post_thumbnail_id, 'thumbnail' );
				?>
                <div class="bp-gift-holder">
                <img src="<?php echo $image_attributes[0]; ?>" />
                </div>
                <?php
			}
		}
	}
endif;

function BP_Gifts() {
	return BP_GIFTS_EXT::instance();
}

// Global variable for accessing gifts
$GLOBALS['bp_gifts'] = BP_Gifts();