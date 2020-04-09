<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

/**
 * CapInterceptor_RS PHP class for the WordPress plugin Role Scoper
 * cap-interceptor_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2011
 * 
 */
class CapInterceptor_RS
{	
	var $scoper;
	var $scoper_admin;
	var $query_interceptor;
	
	var $in_process = false;
	var $skip_id_generation = false;
	var $skip_any_term_check = false;
	var $skip_any_object_check = false;
	var $ignore_object_roles = false;
	
	function __construct() {
		$this->scoper =& $GLOBALS['scoper'];
		$this->query_interceptor =& $GLOBALS['query_interceptor'];
		$this->scoper_admin =& $GLOBALS['scoper_admin'];
		
		// Since scoper installation implies that this plugin should take custody
		// of access control, set priority high so we have the final say on group-controlled caps.
		// This filter will not mess with any caps which are not scoper-defined.
		//
		// (note: custom caps from other plugins can be scoper-controlled if they are defined via a Role Scoper Extension plugin)
		add_filter('user_has_cap', array(&$this, 'flt_user_has_cap'), 99, 3);  // scoping will be defeated if our filter isn't applied last
	}

	// hook to wrapper function to avoid recursion
	function flt_user_has_cap($wp_blogcaps, $orig_reqd_caps, $args) {
		if ( $this->in_process )
			return $wp_blogcaps;
			
		$this->in_process = true;
		$return = $this->_flt_user_has_cap($wp_blogcaps, $orig_reqd_caps, $args);
		$this->in_process = false;
		return $return;
	}
	
	// CapInterceptor_RS::flt_user_has_cap
	//
	// Capability filter applied by WP_User->has_cap (usually via WP current_user_can function)
	// Pertains to logged user's capabilities blog-wide, or for a single item
	//
	// $wp_blogcaps = current user's blog-wide capabilities
	// $reqd_caps = primitive capabilities being tested / requested
	// $args = array with:
	// 		$args[0] = original capability requirement passed to current_user_can (possibly a meta cap)
	// 		$args[1] = user being tested
	// 		$args[2] = object id (could be a postID, linkID, catID or something else)
	//
	// The intent here is to add to (or take away from) $wp_blogcaps based on scoper role assignments
	// (only offer an opinion on scoper-defined caps, with others left in $allcaps array as blog-wide caps)
	//
	function _flt_user_has_cap($wp_blogcaps, $orig_reqd_caps, $args) {
		
		// =============================== STATIC VARIABLE DECLARATION AND INITIALIZATION (to memcache filtering results) =====
		static $cache_tested_ids;
		static $cache_okay_ids;
		static $cache_where_clause;
							
		if ( empty($cache_tested_ids) ) {
			$cache_where_clause = array();
			$cache_tested_ids = array();
			$cache_okay_ids = array();
		}
		// ====================================================================================================================
		

		// =============================================== TEMPORARY DEBUG CODE ================================================

		//dump($orig_reqd_caps);
		//dump($args);
		
		//if ( strpos( $_SERVER['REQUEST_URI'], 'ajax' ) ) {
			//if ( ! empty($_REQUEST) )
			//	rs_errlog( serialize($_REQUEST) );
			//rs_errlog( '' );

			//rs_errlog('flt_user_has_cap');
			//rs_errlog(serialize($orig_reqd_caps));
			//rs_errlog(serialize($args));
			//rs_errlog('');
		//}
		
		// ============================================= (end temporary debug code) ==============================================

		// convert 'rs_role_name' to corresponding caps (and also make a tinkerable copy of orig_reqd_caps)
		$orig_reqd_caps = $this->scoper->role_defs->role_handles_to_caps($orig_reqd_caps);
		
		
		// ================= EARLY EXIT CHECKS (if the provided reqd_caps do not need filtering or need special case filtering ==================
		global $pagenow;

		// Disregard caps which are not defined in Role Scoper config
		if ( ! $rs_reqd_caps = array_intersect( $orig_reqd_caps, $this->scoper->cap_defs->get_all_keys() ) ) {
			return $wp_blogcaps;		
		}
		
		// log initial set of RS-filtered caps (in case we swap in equivalent caps for intermediate processing)
		$orig_reqd_caps = $rs_reqd_caps;

		// permitting this filter to execute early in an attachment request resets the found_posts record, preventing display in the template
		if ( is_attachment() && ! is_admin() && ! did_action('template_redirect') ) {
			if ( empty( $GLOBALS['scoper_checking_attachment_access'] ) ) {
				return $wp_blogcaps;
			}
		}
		
		// work around bug in mw_EditPost method (requires publish_pages AND publish_posts cap)
		if ( defined('XMLRPC_REQUEST') && ( 'publish_posts' == $orig_reqd_caps[0] ) ) {
			if ( ! empty($GLOBALS['xmlrpc_post_type_rs']) && ( 'page' == $GLOBALS['xmlrpc_post_type_rs'] ) ) {
				return array( 'publish_posts' => true );
			}
		}
		
		// backdoor to deal with rare cases where one of the caps included in RS role defs cannot be filtered properly
		if ( defined('UNSCOPED_CAPS_RS') && ! array_diff( $orig_reqd_caps, explode( ',', UNSCOPED_CAPS_RS ) ) ) {
			return $wp_blogcaps;
		}
		
		// custom workaround to reveal all private / restricted content in all blogs if logged into main blog 
		if ( defined( 'SCOPER_MU_MAIN_BLOG_RULES' ) ) {
			include_once( dirname(__FILE__).'/mu-custom.php' );
			if ( ! array_diff( $orig_reqd_caps, array( 'read', 'read_private_pages', 'read_private_posts' ) ) )
				if ( $return_caps = ScoperMU_Custom::current_user_logged_into_main( $wp_blogcaps, $orig_reqd_caps ) ) {
					return $return_caps;
				}
		}

		//define( 'SCOPER_NO_COMMENT_FILTERING', true );
		if ( defined( 'SCOPER_NO_COMMENT_FILTERING' ) && ( 'moderate_comments' == $orig_reqd_caps[0] ) && empty( $GLOBALS['current_rs_user']->allcaps['moderate_comments'] ) ) {
			return $wp_blogcaps;			
		}
		
		if ( defined( 'SCOPER_ALL_UPLOADS_EDITABLE' ) && ( $pagenow == 'upload.php' ) && in_array( $orig_reqd_caps[0], array( 'upload_files', 'edit_others_posts', 'delete_others_posts' ) ) ) {
			return $wp_blogcaps;
		}		
		// =================================================== (end early exit checks) ======================================================

		
		// ============================ GLOBAL VARIABLE DECLARATIONS, ARGUMENT TRANSLATION AND STATUS DETECTION =============================
		global $current_rs_user;
		
		$user_id = ( isset($args[1]) ) ? $args[1] : 0;

		if ( $user_id && ($user_id != $current_rs_user->ID) )
			$user = rs_get_user($user_id);
		else
			$user = $current_rs_user;

		// currently needed for filtering async-upload.php
		if ( empty($user->blog_roles ) || empty($user->blog_roles[''] ) )
			$this->scoper->refresh_blogroles();
			
		$object_id = ( isset($args[2]) ) ? (int) $args[2] : 0;
		
		// WP passes comment ID with 'edit_comment' metacap
		if ( $object_id && ( 'edit_comment' == $args[0] ) ) {
			if ( ! in_array( 'moderate_comments', $rs_reqd_caps ) ) {	 // as of WP 3.2.1, 'edit_comment' maps to related post's 'edit_post' caps without requiring moderate_comments
				if ( scoper_get_option( 'require_moderate_comments_cap' ) ) {
					$rs_reqd_caps[] = 'moderate_comments';
					$modified_caps = true;
				}
			}
				
			if ( $comment = get_comment( $object_id ) )
				$object_id = $comment->comment_post_ID;
			else
				$object_id = 0;
		}
		
		// note the data source and object type(s) which are associated with the required caps (based on inclusion in RS Role Definitions)
		$is_taxonomy_cap = false;
		$src_name = '';
		$cap_types = $this->scoper->cap_defs->src_otypes_from_caps( $rs_reqd_caps, $src_name );	  // note: currently only needed for src_name determination
		
		$doing_admin_menus = is_admin() && (
		( did_action( '_admin_menu' ) && ! did_action('admin_menu') ) 	 // menu construction
		|| ( did_action( 'admin_head' ) && ! did_action('adminmenu') )	 // menu display
		);

		// for scoped menu management roles, satisfy edit_theme_options cap requirement
		if ( array_key_exists(0, $orig_reqd_caps) && ( 'edit_theme_options' == $orig_reqd_caps[0] ) && empty( $wp_blogcaps['edit_theme_options'] ) ) {
			if ( in_array( $GLOBALS['pagenow'], array( 'nav-menus.php', 'admin-ajax.php' ) ) || $doing_admin_menus ) {
				$key = array_search( 'edit_theme_options', $rs_reqd_caps );
				if ( false !== $key ) {
					$tx = get_taxonomy( 'nav_menu' );
					$rs_reqd_caps[$key] = $tx->cap->manage_terms;
					
					$src_name = 'nav_menu';
					
					// menu-specific manager assignment does not permit deletion of the menu
					if ( ! empty( $_REQUEST['action'] ) && ( 'delete' == $_REQUEST['action'] ) )
						$this->skip_any_term_check = true;
				}
			}
		}
		
		if ( ! $src_name ) {
			// required capabilities correspond to multiple data sources
			return $wp_blogcaps;		
		}
		
		// slight simplification: assume a single cap object type for a few cap substitution checks
		$is_taxonomy_cap = $this->scoper->cap_defs->member_property( reset($rs_reqd_caps), 'is_taxonomy_cap' );
		
		// Establish some context by detecting object type - based on object ID if provided, or otherwise based on http variables.
		if ( in_array( $pagenow, array( 'media-upload.php', 'async-upload.php' ) ) ) {
			if ( ! empty($GLOBALS['post']) ) 
				$object_type = $GLOBALS['post']->post_type;	
		
		} elseif ( is_admin() && ( 'edit-tags.php' == $GLOBALS['pagenow'] ) && ( 'link_category' == $_REQUEST['taxonomy'] ) ) {
			$src_name = 'link';
			$object_type = 'link_category';
		} elseif ( array_key_exists(0, $orig_reqd_caps) && in_array( $orig_reqd_caps[0], array( 'manage_nav_menus', 'edit_theme_options' ) ) ) {
			$src_name = 'nav_menu';
		}

		if ( empty($object_type) )
			$object_type = cr_find_object_type( $src_name, $object_id );

		$object_type_obj = cr_get_type_object( $src_name, $object_type );
		
		$is_att_rev = false;
		if ( 'post' == $src_name ) {
			if ( in_array( $object_type, array( 'attachment', 'revision' ) ) ) {
				$is_att_rev = true;
			
				if ( $object_id ) {
					if ( $_post = get_post( $object_id ) ) {
						if ( $_parent = get_post($_post->post_parent) ) {
							$object_type = $_parent->post_type;
							$object_id = $_parent->ID;
							
							// deal with case of edit_posts cap check on attachments to revision (with Revisionary)
							if ( 'revision' == $object_type ) {
								if ( $_orig_post = get_post($_parent->post_parent) ) {
									$object_type = $_orig_post->post_type;
									$object_id = $_orig_post->ID;
								}
							}
							
							$object_type_obj = get_post_type_object( $object_type );
						}
					}
				}
			} elseif ( ! $is_taxonomy_cap ) {
				$use_post_types = scoper_get_option( 'use_post_types' );
				if ( empty( $use_post_types[$object_type] ) )
					return $wp_blogcaps;
			}
		}
		
		// =====================================================================================================================================
		
		// ======================================== SUBVERT MISGUIDED CAPABILITY REQUIREMENTS ==================================================
		if ( 'post' == $src_name ) {	
			if ( ! $is_taxonomy_cap ) {
				$modified_caps = false;
				
				if ( 'post' != $object_type ) {
					$replace_post_caps = array( 'publish_posts', 'edit_others_posts', 'edit_published_posts' );
					
					// Replace edit_posts requirement with corresponding type-specific requirement, but only after admin menu is drawn, or on a submission before the menu is drawn
					if ( did_action( 'admin_init' ) ) {	// otherwise extra padding between menu items due to some items populated but unpermitted
						$replace_post_caps []= 'edit_posts';
					}
	
					if ( in_array( $pagenow, array( 'upload.php', 'media.php' ) ) ) {
						$replace_post_caps = array_merge( $replace_post_caps, array( 'delete_posts', 'delete_others_posts' ) );
					}

					foreach( $replace_post_caps as $post_cap_name ) {
						$key = array_search( $post_cap_name, $rs_reqd_caps );

						if ( ( false !== $key ) && ! $doing_admin_menus && in_array( $pagenow, array( 'edit.php', 'post.php', 'post-new.php', 'press-this.php', 'admin-ajax.php', 'upload.php', 'media.php' ) ) ) {				
							$rs_reqd_caps[$key] = $object_type_obj->cap->$post_cap_name;
							$modified_caps = true;
						}
					}
				}
				
				// WP core quirk workaround: edit_others_posts is required as preliminary check for populating authors dropdown for any post type.  Instead, we need to do our own validation based on scoped roles.
				// (but don't mess if this cap requirement is part of an edit_post metacap check for a specific post)
				if ( ! $object_id && ( count($rs_reqd_caps) == 1 ) ) {
					if( in_array( reset($rs_reqd_caps), array( 'edit_others_posts' ) ) ) {
						require_once( dirname(__FILE__).'/lib/agapetry_wp_admin_lib.php' ); // function awp_metaboxes_started()
		
						if ( ! awp_metaboxes_started( $object_type ) && ( 'revision.php' != $pagenow ) && ( 'revisions' != $GLOBALS['plugin_page_cr'] ) ) // don't enable contributors to view/restore revisions
							$rs_reqd_caps[0] = $object_type_obj->cap->edit_posts;   // don't enable contributors to view/restore revisions
						else
							$rs_reqd_caps[0] = $object_type_obj->cap->edit_published_posts;	// we will filter / suppress the author dropdown downstream from here
						
						$modified_caps = true;
					}
				}
				
				// as of WP 3.1, addition of new nav menu items requires edit_posts capability (otherwise nav menu item is orphaned with no menu relationship)
				if ( is_admin() && strpos( $_SERVER['SCRIPT_NAME'], 'nav-menus.php' ) ) {
					if ( 'edit_posts' == $orig_reqd_caps[0] ) {
						$type_obj = get_taxonomy( 'nav_menu' );
						$rs_reqd_caps[0] = $type_obj->cap->manage_terms;
						$modified_caps = true;
					}
				}
				
			} // endif not taxonomy cap
		} // endif caps correspond to 'post' data source
		//====================================== (end subvert misguided capability requirements) =============================================
		
		if ( defined( 'RVY_VERSION' ) ) {
			require_once( dirname(__FILE__).'/revisionary-helper_rs.php' );
			$rs_reqd_caps = Rvy_Helper::convert_post_edit_caps( $rs_reqd_caps, $object_type );
		}

		//rs_errlog( "matched context for $object_id : $matched_context" );
		
		// don't apply object-specific filtering for auto-drafts
		if ( 'post' == $src_name ) {
			if ( $object_id ) {
				if ( $_post = get_post($object_id) ) {
					if ( ( 'auto-draft' == $_post->post_status ) ) { // && ! empty($_POST['action']) )
						$object_id = 0;
						
						if ( ! $doing_admin_menus )
							$this->skip_id_generation = true;
					}
				}
			} else {
				if ( ! empty($GLOBALS['post']) && ! is_object($GLOBALS['post']) )
					$GLOBALS['post'] = get_post($GLOBALS['post']);
				
				if ( ! empty( $GLOBALS['post'] ) && ( 'auto-draft' == $GLOBALS['post']->post_status ) && ! $doing_admin_menus )
					$this->skip_id_generation = true;
			}
		}
		
		//dump($object_id);
		
		// If no object id was passed in...
		if ( ! $object_id ) { // || ! $matched_context ) {
		//if ( $missing_caps = array_diff($rs_reqd_caps, array_keys($wp_blogcaps) ) ) {
			if ( ! $doing_admin_menus ) {
				if ( ! empty($_REQUEST['action']) && in_array( $pagenow, array('edit.php','edit-tags.php') ) )
					$this->skip_id_generation = true;

				// ============================================ OBJECT ID DETERMINATION ========================================
				if ( ! $this->skip_id_generation && ! defined('XMLRPC_REQUEST') && ! in_array( $pagenow, array( 'media-upload.php', 'async-upload.php' ) ) ) {  // lots of superfluous queries in media upload popup otherwise
					// Try to generate missing object_id argument for problematic current_user_can calls 
					static $generated_id;
					
					if ( ! isset( $generated_id ) )
						$generated_id = array();
	
					// if the id was not already detected and stored to the static variable...
					$caps_key = serialize($rs_reqd_caps);
					if ( ! isset( $generated_id[$object_type][$caps_key] ) ) {
						$gen_id = 0;
	
						foreach( $rs_reqd_caps as $cap_name ) {
							if ( $gen_id = (int) $this->_detect_object_id( $cap_name ) ) {
								break;	// means we are accepting the generated id
							}
						}
	
						$generated_id[$object_type][$caps_key] = $gen_id;
						$object_id = $gen_id;
					} else
						$object_id = $generated_id[$object_type][$caps_key];
	
					//rs_errlog( "detected ID: $object_id" );
				} else
					$this->skip_id_generation = false; // this is a one-time flag

				// ========================================= (end object id determination) =======================================
			}
			
			// If we still have no object id (detection was skipped or failed to identify it)...
			if ( ! $object_id ) { // || ! $matched_context ) {
				// ============================================ "CAN FOR ANY" CHECKS ===========================================
				if ( $missing_caps = array_diff($rs_reqd_caps, array_keys($wp_blogcaps) ) ) {
					// These checks are only relevant since no object_id was provided.  Otherwise (in the main body of this function), taxonomy and object caps will be credited via scoped query.
					
					// If we are about to fail the blogcap requirement, credit a missing cap if the user has it by term role for ANY term.
					// This prevents failing initial UI entrance exams that only consider blog-wide roles.
					if ( ! $this->skip_any_term_check ) {
						if ( $tax_caps = $this->user_can_for_any_term($missing_caps) )
							$wp_blogcaps = array_merge($wp_blogcaps, $tax_caps);

						//rs_errlog( "can for any term: " . serialize($tax_caps) );
					} else
						$this->skip_any_term_check = false;  // this is a one-time flag

					// If we are still missing required caps, credit a missing scoper-defined cap if the user has it by object role for ANY object.
					// (i.e. don't bar user from "Edit Pages" if they have edit_pages cap for at least one page)
					if ( $missing_caps = array_diff($rs_reqd_caps, array_keys($wp_blogcaps) ) ) {
						// prevent object-specific editing roles from allowing new object creation w/o sitewide capability
						$add_new_check = strpos( $_SERVER['SCRIPT_NAME'], 'post-new.php' ) && ( 'post' == $src_name ) && ( reset( $rs_reqd_caps ) == $object_type_obj->cap->edit_posts );

						if ( ( ! $this->skip_any_object_check ) && ! $add_new_check ) {
						//if ( ! $this->skip_any_object_check ) {
							if ( $object_caps = $this->user_can_for_any_object( $missing_caps ) )
								$wp_blogcaps = array_merge($wp_blogcaps, $object_caps);
								
							//rs_errlog( "can for any object: " . serialize($object_caps) );
						} else
							$this->skip_any_object_check = false;  // this is a one-time flag
					}
				}
				// ========================================== (end "can for any" checks ) =========================================
				
				//rs_errlog( serialize( $wp_blogcaps) );
				
				if ( $missing_caps = array_diff($rs_reqd_caps, array_keys($wp_blogcaps) ) )
					// normal exit point when no object ID is passed or detected, or when detected object type does not match required capabilities
					return $wp_blogcaps;
				else {
					if ( $restore_caps = array_diff( $orig_reqd_caps, $rs_reqd_caps ) )  // restore original reqd_caps which we substituted for the type-specific scoped query
						$wp_blogcaps = array_merge( $wp_blogcaps, array_fill_keys($restore_caps, true) );

					return $wp_blogcaps;
				}	
			}
		//} else
			//return $wp_blogcaps;
			
		} 

		if ( $object_id && ( 'post' == $src_name ) ) {
			$_post = get_post($object_id);
			$object_type = $_post->post_type;
			$object_type_obj = cr_get_type_object( $src_name, $object_type );
			
			if ( defined('RVY_VERSION') && in_array( $pagenow, array('edit.php', 'edit-tags.php', 'admin-ajax.php') ) && ( ! empty($_REQUEST['action']) && ( -1 != $_REQUEST['action'] ) ) ) {
				$rs_reqd_caps = Rvy_Helper::fix_table_edit_reqd_caps( $rs_reqd_caps, $args[0], $_post, $object_type_obj );
			}

			// if the top level page structure is locked, don't allow non-administrator to delete a top level page either
			if ( ( 'page' == $object_type ) || defined( 'SCOPER_LOCK_OPTION_ALL_TYPES' ) && ! is_content_administrator_rs() ) {
				$delete_metacap = ( ! empty($object_type_obj->hierarchical) ) ? $object_type_obj->cap->delete_post : 'delete_page';

				// if the top level page structure is locked, don't allow non-administrator to delete a top level page either
				if ( $delete_metacap == $args[0] ) {
					if ( '1' === scoper_get_option( 'lock_top_pages' ) ) {	  // stored value of 1 means only Administrators are allowed to modify top-level page structure
						if ( $page = get_post( $args[2] ) ) {
							if ( empty( $page->post_parent ) ) {
								$in_process = false;
								return false;
							}
						}
					}
				}
			}
		}

		// Note: At this point, we have a nonzero object_id...
		
		// if this is a term administration request, route to user_can_admin_terms()
		if ( $is_taxonomy_cap ) {
			if ( 'post' == $src_name )
				$cap_otype_obj = get_taxonomy( $object_type );

			if ( ( ( 'post' != $src_name ) || ( $cap_otype_obj && $rs_reqd_caps[0] == $cap_otype_obj->cap->manage_terms ) ) && ( count($rs_reqd_caps) == 1 ) ) {  // don't re-route if multiple caps are being required
				// always pass through any assigned blog caps which will not be involved in this filtering
				$rs_reqd_caps = array_fill_keys( $rs_reqd_caps, 1 );
				$undefined_reqd_caps = array_diff_key( $wp_blogcaps, $rs_reqd_caps);

				require_once( dirname(__FILE__).'/admin/permission_lib_rs.php' );
				if ( user_can_admin_terms_rs( $object_type, $object_id, $user) ) {
					return array_merge($undefined_reqd_caps, $rs_reqd_caps);
				} else {
					return $undefined_reqd_caps;	// required caps we scrutinized are excluded from this array
				}
			}
		}
		
		// Workaround to deal with WP core's checking of publish cap prior to storing categories
		// Store terms to DB in advance of any cap-checking query which may use those terms to qualify an operation		
		if ( ! empty($_REQUEST['action']) && ( in_array( $_REQUEST['action'], array( 'editpost', 'post' ) ) || ('autosave' == $_REQUEST['action']) ) ) {
			if ( array_intersect( array( 'publish_posts', 'edit_posts', $object_type_obj->cap->publish_posts,  $object_type_obj->cap->edit_posts ), $rs_reqd_caps) ) {
				$uses_taxonomies = scoper_get_taxonomy_usage( $src_name, $object_type );
				
				static $inserted_terms;
				if ( ! isset( $inserted_terms ) )
					$inserted_terms = array();
				
				foreach ( $uses_taxonomies as $taxonomy ) { 	// TODO: only if tx->requires_term is true?
					if ( isset( $inserted_terms[$taxonomy][$object_id] ) )
						continue;

					$inserted_terms[$taxonomy][$object_id] = true;

					//if ( $stored_terms = wp_get_object_terms( $object_id, $taxonomy ) ) // note: this will cause trouble if WP core ever auto-stores object terms on post creation
					//	continue;

					$stored_terms = $this->scoper->get_terms($taxonomy, UNFILTERED_RS, COL_ID_RS, $object_id);
					
					require_once( dirname(__FILE__).'/admin/filters-admin-save_rs.php' );
					$selected_terms = cr_get_posted_object_terms( $taxonomy );

					if ( is_array($selected_terms) ) { // non-hierarchical terms do not need to be pre-inserted
						if ( $set_terms = $GLOBALS['scoper_admin_filters']->flt_pre_object_terms($selected_terms, $taxonomy) ) {
							$set_terms = array_unique( array_map('intval', $set_terms) );

							if ( ( $set_terms != $stored_terms ) && $set_terms && ( $set_terms != array(1) ) ) { // safeguard against unintended clearing of stored categories
								wp_set_object_terms( $object_id, $set_terms, $taxonomy );

								// delete any buffered cap check results which were queried prior to storage of these object terms
								unset( $cache_tested_ids );
								unset( $cache_where_clause );
								unset( $cache_okay_ids );
							}
						}
					}
				}
				
				// also avoid chicken-egg situation when publish cap is granted by a propagating page role
				if ( $object_type_obj->hierarchical && isset( $_POST['parent_id'] ) ) {
					if ( $_POST['parent_id'] != get_post_field( 'post_parent', $object_id ) ) {
						global $wpdb;
						$set_parent = $GLOBALS['scoper_admin_filters']->flt_page_parent( $_POST['parent_id'] );
						$GLOBALS['wpdb']->query( "UPDATE $wpdb->posts SET post_parent = '$set_parent' WHERE ID = '$object_id'" );
						
						require_once( dirname(__FILE__).'/admin/filters-admin-save_rs.php' );
						scoper_inherit_parent_roles($object_id, OBJECT_SCOPE_RS, $src_name, $set_parent, $object_type);
						scoper_inherit_parent_restrictions($object_id, OBJECT_SCOPE_RS, $src_name, $set_parent, $object_type);
					}
				}
			}
		}

		// generate a string key for this set of required caps, for use below in checking, caching the scoped results
		$arg_append = '';
		$arg_append .= ( ! empty( $this->require_full_object_role ) ) ? '-require_full_object_role-' : '';
		$arg_append .= ( ! empty( $GLOBALS['revisionary']->skip_revision_allowance ) ) ? '-skip_revision_allowance-' : '';

		sort($rs_reqd_caps);
		$capreqs_key = implode($rs_reqd_caps) . $arg_append;  // see ScoperAdmin::user_can_admin_object
		
	
		// ================================ SPECIAL HANDLING FOR ATTACHMENTS AND REVISIONS ==========================================
		$maybe_revision = ( 'post' == $src_name && ! isset($cache_tested_ids[$src_name][$object_type][$capreqs_key][$object_id]) );

		$maybe_attachment = in_array( $pagenow, array( 'upload.php', 'media.php' ) );

		if ( $maybe_revision || $maybe_attachment ) {
			global $wpdb;
	
			if ( $_post = get_post($object_id) ) {
				if ( 'revision' == $_post->post_type ) {
					require_once( dirname(__FILE__).'/lib/revisions_lib_rs.php' );
					
					$rev_where = ( defined('RVY_VERSION') && rvy_get_option( 'revisor_lock_others_revisions' ) ) ? " AND post_author = '$current_rs_user->ID'" : '';  // might need to apply different cap requirement for other users' revisions. todo: skip this clause for sitewide editors
					$revisions = rs_get_post_revisions($_post->post_parent, 'inherit', array( 'fields' => constant('COL_ID_RS'), 'return_flipped' => true, 'where' => $rev_where ) );						
				}

				if ( ( 'revision' == $_post->post_type ) || ( 'attachment' == $_post->post_type ) ) {
					$is_att_rev = true;
				
					if ( $_post->post_parent ) {
						$object_id = $_post->post_parent;

						if ( $_parent = get_post($_post->post_parent) ) {
							$object_type = $_parent->post_type;
							$object_type_obj = get_post_type_object( $object_type );
						}
					} elseif ( 'attachment' == $_post->post_type ) {
						// special case for unattached uploads: uploading user should have their way with them
						if ( $_post->post_author == $current_rs_user->ID ) {
							$rs_reqd_caps[0] = 'read';

							if ( $restore_caps = array_diff($orig_reqd_caps, array_keys($rs_reqd_caps) ) )  // restore original reqd_caps which we substituted for the type-specific scoped query
								$wp_blogcaps = array_merge( $wp_blogcaps, array_fill_keys($restore_caps, true) );
						}

						return $wp_blogcaps;
					}
				} //endif retrieved post is a revision or attachment
			} // endif post retrieved
		} // endif specified id might be a revision or attachment
		
		if ( $is_att_rev ) {
			if ( 'post' != $object_type_obj->name ) {
				// Compensate for WP's requirement of posts cap for attachment editing, regardless of whether it's attached to a post or page							
				if ( 'edit_others_posts' == $rs_reqd_caps[0] )
					$rs_reqd_caps[0] = $object_type_obj->cap->edit_others_posts;
					
				elseif ( 'delete_others_posts' == $rs_reqd_caps[0] )
					$rs_reqd_caps[0] = $object_type_obj->cap->delete_others_posts;
					
				elseif ( 'edit_posts' == $rs_reqd_caps[0] )
					$rs_reqd_caps[0] = $object_type_obj->cap->edit_posts;
					
				elseif ( 'delete_posts' == $rs_reqd_caps[0] )
					$rs_reqd_caps[0] = $object_type_obj->cap->delete_posts;
			}
		} //endif retrieved post is a revision or attachment
		
		
		// ============================== (end special handling for attachments and revisions) ==========================================
		
	
		// ============ SCOPED QUERY for required caps on object id (if other listed ids are known, query for them also).  Cache results to static var. ===============
		
		// $force_refresh = 'async-upload.php' == $pagenow;
		
		// Page refresh following publishing of new page by users who can edit by way of Term Role fails without this workaround
		if ( ! empty( $_POST ) && ( defined( 'SCOPER_CACHE_SAFE_MODE' ) || ( in_array( $pagenow, array( 'post.php', 'press-this.php' ) ) && ( $args[0] == $object_type_obj->cap->edit_post ) ) ) ) {
			$force_refresh = true;
			$cache_tested_ids = array();
			$cache_okay_ids = array();
			$cache_where_clause = array();
		} else
			$force_refresh = false;

		// Check whether this object id was already tested for the same reqd_caps in a previous execution of this function within the same http request
		if ( $force_refresh || ! isset($cache_tested_ids[$src_name][$object_type][$capreqs_key][$object_id]) ) {
		//if ( ! isset($cache_tested_ids[$src_name][$object_type][$capreqs_key][$object_id]) ) {
	
			// retrieve CR_Data_Source object, which contains database column names
			$src_table = $this->scoper->data_sources->member_property($src_name, 'table');
			$cols = $this->scoper->data_sources->member_property($src_name, 'cols');
			
			// Before querying for caps on this object, check whether we have a record of other posts listed alongside it.  
			// If so, run the scoped query for ALL listed objects in that buffer, and buffer the results to static variable hascap_object_ids. 
			//
			// (This is useful when front end code must check caps for each post 
			//  to determine whether to display 'edit' link, etc.)
			if ( is_admin() && ( 'index.php' == $pagenow ) ) {  // there's too much happening on the dashboard (and too much low-level query filtering) to buffer listed IDs reliably.
				$listed_ids = array();
			} else {
				if ( isset($this->scoper->listed_ids[$src_name]) )
					$listed_ids = array_keys($this->scoper->listed_ids[$src_name]);
				else // note: don't use wp_object_cache because it includes posts not present in currently displayed resultset listing page
					$listed_ids = array();
			}
			
			// make sure our current object_id is in the list
			$listed_ids[] = $object_id;

			// since the objects_where_role_clauses() output itself is not id-specific, also statically buffer it per reqd_caps
			if ( $force_refresh || ! isset( $cache_where_clause[$src_name][$object_type][$capreqs_key] ) ) {
				$check_otype = ( 'link_category' == $object_type ) ? 'link' : $object_type;
				$use_term_roles = scoper_get_otype_option( 'use_term_roles', $src_name, $check_otype );

				$no_object_roles = $this->scoper->data_sources->member_property($src_name, 'no_object_roles');
				$use_object_roles = ( $no_object_roles ) ? false : scoper_get_otype_option( 'use_object_roles', $src_name, $object_type );
	
				$this_args = array( 'object_type' => $object_type, 'user' => $user, 'otype_use_term_roles' => $use_term_roles, 'otype_use_object_roles' => $use_object_roles, 'skip_teaser' => true, 'require_full_object_role' => ! empty($this->require_full_object_role) );

				//rs_errlog( serialize($rs_reqd_caps) );
				//rs_errlog( serialize($this_args) );
				
				$where = $this->query_interceptor->objects_where_role_clauses($src_name, $rs_reqd_caps, $this_args );
				
				if ( $where )
					$where = "AND ( $where )";
					
				// update static variable
				$cache_where_clause[$src_name][$object_type][$capreqs_key] = $where;
			} else
				$where = $cache_where_clause[$src_name][$object_type][$capreqs_key];
				
			// run the query
			$query = "SELECT $src_table.{$cols->id} FROM $src_table WHERE 1=1 $where AND $src_table.{$cols->id} IN ('" . implode( "', '", array_unique($listed_ids) ) . "')";
			
			if ( isset( $cache_okay_ids[$query] ) )
				$okay_ids = $cache_okay_ids[$query];
			else {
				if ( $okay_ids = scoper_get_col($query) )
					$okay_ids = array_fill_keys($okay_ids, true);
			}

			//dump($rs_reqd_caps);
			//dump($query);
			//dump($okay_ids);

			//rs_errlog( $query );
			//rs_errlog( 'results: ' . serialize( $okay_ids ) );
				
			// update static cache_tested_ids to log scoped results for this object id, and possibly also for other listed IDs
			if ( empty($_GET['doaction']) || ( ('delete_post' != $args[0]) && ($object_type_obj->cap->delete_post != $args[0]) ) ) {		// bulk post/page deletion is broken by hascap buffering
				foreach ( $listed_ids as $_id )
					$cache_tested_ids[$src_name][$object_type][$capreqs_key][$_id] = isset( $okay_ids[$_id] );
					
				$cache_okay_ids[$query] = $okay_ids;
			}
			
			$this_id_okay = isset( $okay_ids[$object_id] );
		} else {
			 // results of this same has_cap inquiry are already stored (from another call within current http request)
			$this_id_okay = $cache_tested_ids[$src_name][$object_type][$capreqs_key][$object_id];
		}

		//rs_errlog( "okay ids: " . serialize( $okay_ids ) );
		
		// if we redirected the cap check to revision parent, also credit all the revisions for passing results
		if ( $this_id_okay && ! empty($revisions) ) {
			if ( empty($_GET['doaction']) || ( ('delete_post' != $args[0]) && ($object_type_obj->cap->delete_post != $args[0]) ) )	// bulk post/page deletion is broken by hascap buffering
				$cache_tested_ids[$src_name][$object_type][$capreqs_key] = $cache_tested_ids[$src_name][$object_type][$capreqs_key] + array_fill_keys( $revisions, true );
		}
		
		$rs_reqd_caps = array_fill_keys( $rs_reqd_caps, true );
		
		if ( ! $this_id_okay ) {
			if ( array_key_exists(0, $orig_reqd_caps) && ( 'edit_posts' == $orig_reqd_caps[0] ) && strpos( $_SERVER['REQUEST_URI'], 'async-upload.php' ) ) {  // temp workaround for ACF with Revisionary
				return $wp_blogcaps;
			}

			// ================= TEMPORARY DEBUG CODE ===================
			//d_echo("object_id $object_id FAILED !!!!!!!!!!!!!!!!!" );

			//rs_errlog( "object_id $object_id FAILED !!!!!!!!!!!!!!!!!"  );
			//rs_errlog(serialize($orig_reqd_caps));
			//rs_errlog(serialize($rs_reqd_caps));
			//rs_errlog('');

			/*
			$log .= "checked caps: " . serialize($rs_reqd_caps) . "\r\n";
			$log .= "object_id $object_id FAILED !!!!!!!!!!!!!!!!!\r\n";
			$log .= $query;
			rs_errlog( "\r\n{$log}\r\n" );
			*/
			//d_echo( "FAILED for " . serialize($rs_reqd_caps) );
			// ============== (end temporary debug code ==================

			return array_diff_key( $wp_blogcaps, $rs_reqd_caps);	// required caps we scrutinized are excluded from this array
		} else {
			if ( $restore_caps = array_diff($orig_reqd_caps, array_keys($rs_reqd_caps) ) )  // restore original reqd_caps which we substituted for the type-specific scoped query
				$rs_reqd_caps = $rs_reqd_caps + array_fill_keys($restore_caps, true);
				
			//d_echo( 'OKAY:' );
			//dump($args);
			//dump($rs_reqd_caps);
			//d_echo( '<br />' );
			
			return array_merge($wp_blogcaps, $rs_reqd_caps);
		}
	}
	
	
	// Try to generate missing has_cap object_id arguments for problematic caps
	// Ideally, this would be rendered unnecessary by updated current_user_can calls in WP core or other offenders
	function _detect_object_id( $required_cap ) {
		if ( has_filter( 'detect_object_id_rs' ) ) { // currently not used internally 
			if ( $object_id = apply_filters( 'detect_object_id_rs', 0, $required_cap ) )
				return $object_id;
		}
		
		if ( $this->scoper->cap_defs->member_property( $required_cap, 'is_taxonomy_cap' ) ) {
			if ( ! empty($_REQUEST['tag_ID']) )
				return (int) $_REQUEST['tag_ID'];
		}
		
		global $pagenow;
		if ( in_array( $pagenow, array( 'media-upload.php', 'async-upload.php' ) ) ) {
			if ( ! empty($_POST['post_ID']) )
				return (int) $_POST['post_ID'];
			elseif ( ! empty($_REQUEST['post_id']) )
				return (int) $_REQUEST['post_id'];
			elseif ( ! empty($_REQUEST['attachment_id']) ) {
				if ( $attachment = get_post( $_REQUEST['attachment_id'] ) )
					return $attachment->post_parent;
			}
		}
		
		if ( ! $src_name = $this->scoper->cap_defs->member_property( $required_cap, 'src_name' ) )
			return;
		
		if ( ! empty( $_POST ) ) {
			// special case for comment post ID
			if ( ! empty( $_POST['comment_post_ID'] ) )
				$_POST['post_ID'] = $_POST['comment_post_ID'];
				
			// WP core edit_post function requires edit_published_posts or edit_published_pages cap to save a post to "publish" status, but does not pass a post ID
			// Similar situation with edit_others_posts, publish_posts.
			// So... insert the object ID from POST vars
			if ( 'post' == $src_name ) {
				if ( ! $id = (int) $this->scoper->data_sources->get_from_http_post('id', 'post') ) {
					
					if ( 'async-upload.php' != $GLOBALS['pagenow'] ) {
						if ( $attach_id = (int) $this->scoper->data_sources->get_from_http_post('attachment_id', 'post') ) {
							if ( $attach_id ) {
								global $wpdb;
								$id = scoper_get_var( "SELECT post_parent FROM $wpdb->posts WHERE post_type = 'attachment' AND ID = '$attach_id'" );
								if ( $id > 0 )
									return $id;
							}
						}
					} elseif ( ! $id && ! empty($_POST['id']) ) // in case normal POST variable differs from ajax variable
						$id = (int) $_POST['id'];
				}
			}

			/* on the moderation page, admin-ajax tests for moderate_comments without passing any ID */
			if ( 'moderate_comments' == $required_cap )
				if ( $comment = get_comment( $id ) )
					return $comment->comment_post_ID;
			
			if ( ! empty($id) )
				return $id;
				
			// special case for adding categories
			if ( 'manage_categories' == $required_cap ) {
				if ( ! empty($_POST['newcat_parent']) )
					return (int) $_POST['newcat_parent'];
				elseif ( ! empty($_POST['category_parent']) )
					return (int) $_POST['category_parent'];
			}
			
		} elseif ( defined('XMLRPC_REQUEST') ) {
			if ( ! empty($GLOBALS['xmlrpc_post_id_rs']) )
				return (int) $GLOBALS['xmlrpc_post_id_rs'];
		} else {
			//rs_errlog("checking uri for source $src_name");
			return (int) $this->scoper->data_sources->get_from_uri('id', $src_name);
		}
	}
	
	
	// Some users with term or object roles are now able to view and edit certain 
	// content, if only the unscoped core would let them in the door.  For example, you can't 
	// load edit-pages.php unless current_user_can('edit_pages') blog-wide.
	//
	// This policy is sensible for unscoped users, as it hides stuff they can't have.
	// But it is needlessly oppressive to those who walk according to the law of the scoping. 
	// Subvert the all-or-nothing paradigm by reporting a blog-wide cap if the user has 
	// the capability for any taxonomy.
	//
	// Due to subsequent query filtering, this does not unlock additional content blog-wide.  
	// It merely enables us to run all pertinent content through our gauntlet (rather than having 
	// some contestants disqualified before we arrive at the judging stand).
	//
	// A happy side effect is that, in a fully scoped blog, all non-administrator users can be set
	// to "Subscriber" blogrole so the failure state upon accidental Role Scoper disabling 
	// is overly narrow access, not overly open.
	function user_can_for_any_term($reqd_caps, $user = '') {
		if ( ! is_object($user) ) {
			$user = $GLOBALS['current_rs_user'];
		}
		
		// Instead of just intersecting the missing reqd_caps with termcaps from all term_roles,
		// require each subset of caps with matching src_name, object type and op_type to 
		// all be satisfied by the same role (any assigned term role).  This simulates flt_objects_where behavior (which does so to support role restrictions.
		
		$grant_caps = array();

		$caps_by_otype = $this->scoper->cap_defs->organize_caps_by_otype($reqd_caps);
		
		// temp workaround
		if ( 'manage_categories' == current($reqd_caps) && isset( $caps_by_otype['post']['link'] ) ) {
			$caps_by_otype['link']['link_category'] = $caps_by_otype['post']['link'];
			unset( $caps_by_otype['post']['link'] );
		}
		
		foreach ( $caps_by_otype as $src_name => $otypes ) {
			$object_types = $this->scoper->data_sources->member_property($src_name, 'object_types');
		
			// deal with upload_files and other capabilities which have no specific object type
			if ( ! array_diff_key( $otypes, array( '' => true ) ) ) {
				foreach( array_keys( $object_types ) as $_object_type )
					$otypes[$_object_type] = $otypes[''];
				
				unset( $otypes[''] );
			}

			$uses_taxonomies = scoper_get_taxonomy_usage( $src_name, array_keys($otypes) );
			
			// this ensures we don't credit term roles on custom taxonomies which have been disabled
			if ( ! $uses_taxonomies = array_intersect( $uses_taxonomies, $this->scoper->taxonomies->get_all_keys() ) )
				continue;

			foreach ( $otypes as $this_otype_caps ) { // keyed by object_type
				$caps_by_op = $this->scoper->cap_defs->organize_caps_by_op( (array) $this_otype_caps);
				
				foreach ( $caps_by_op as $this_op_caps ) { // keyed by op_type
					$roles = $this->scoper->role_defs->qualify_roles($this_op_caps);
					
					foreach ( $uses_taxonomies as $taxonomy ) {
						if ( ! isset($user->term_roles[$taxonomy]) )
							$user->term_roles[$taxonomy] = $user->get_term_roles_daterange($taxonomy);				// call daterange function populate term_roles property - possible perf enhancement for subsequent code even though we don't conider content_date-limited roles here

						if ( array_intersect_key($roles, agp_array_flatten( $user->term_roles[$taxonomy], false ) ) ) {	// okay to include all content date ranges because can_for_any_term checks are only preliminary measures to keep the admin UI open
							$grant_caps = array_merge($grant_caps, $this_op_caps);
							break;
						}
					}
				}
			}
		}	
		
		if ( $grant_caps )
			return array_fill_keys( array_unique($grant_caps), true);
		else
			return array();
	}
	
	// used by flt_user_has_cap prior to failing blogcaps requirement
	// Note that this is not to be called if an object_id was provided to (or detected by) flt_user_has_cap
	// This is primarily a way to ram open a closed gate prior to selectively re-closing it ourself
	function user_can_for_any_object($reqd_caps, $user = '') {
		if ( ! empty( $this->ignore_object_roles ) ) {
			// use this to force cap via blog/term role for Write Menu item
			$this->ignore_object_roles = false;
			return array();
		}

		if ( ! is_object($user) ) {
			$user = $GLOBALS['current_rs_user'];
		}
	
		if ( $roles = $this->scoper->qualify_object_roles( $reqd_caps, '', $user, true ) )  // arg: convert 'edit_others', etc. to equivalent owner base cap
			return array_fill_keys($reqd_caps, true);

		return array();
	}
} // end class Cap_Interceptor_RS

// equivalent to current_user_can, 
// except it supports array of reqd_caps, supports non-current user, and does not support numeric reqd_caps
function _cr_user_can( $reqd_caps, $object_id = 0, $user_id = 0, $meta_flags = array() ) {	
	// $meta_flags currently used for 'skip_revision_allowance', 'skip_any_object_check', 'skip_any_term_check', 'skip_id_generation', 'require_full_object_role'
	// For now, skip array_merge with defaults, for perf
	if ( $user_id )
		$user = new WP_User($user_id);  // don't need Scoped_User because only using allcaps property (which contain WP blogcaps).  flt_user_has_cap will instantiate new WP_Scoped_User based on the user_id we pass
	else
		$user = wp_get_current_user();
	
	if ( empty($user) )
		return false;

	$reqd_caps = (array) $reqd_caps;
	$check_caps = $reqd_caps;
	foreach ( $check_caps as $cap_name ) {
		if ( $meta_caps = map_meta_cap($cap_name, $user->ID, $object_id) ) {
			$reqd_caps = array_diff( $reqd_caps, array($cap_name) );
			$reqd_caps = array_unique( array_merge( $reqd_caps, $meta_caps ) );
		}
	}

	if ( 'blog' === $object_id ) { // legacy API support
		$meta_flags['skip_any_object_check'] = true;
		$meta_flags['skip_any_term_check'] = true;
		$meta_flags['skip_id_generation'] = true;
	} elseif ( ! $object_id ) {
		$meta_flags['skip_id_generation'] = true;	
	}

	if ( $meta_flags ) {
		// handle special case revisionary flag
		if ( ! empty($meta_flags['skip_revision_allowance']) ) {
			if ( defined( 'RVY_VERSION' ) ) {
				global $revisionary;
				$revisionary->skip_revision_allowance = true;	// this will affect the behavior of Role Scoper's user_has_cap filter
			}
			
			unset( $meta_flags['skip_revision_allowance'] );	// no need to set this flag on cap_interceptor
		}
	
		// set temporary flags for use by our user_has_cap filter
		global $cap_interceptor;
		if ( isset($cap_interceptor) ) {
			foreach( $meta_flags as $flag => $value )
				$cap_interceptor->$flag = $value;
		} else
			$meta_flags = array();
	}
	
	$capabilities = apply_filters('user_has_cap', $user->allcaps, $reqd_caps, array( $reqd_caps, $user->ID, $object_id ) );

	if ( $meta_flags ) {
		// clear temporary flags
		foreach( $meta_flags as $flag => $value )
			$cap_interceptor->$flag = false;
	}
	
	if ( ! empty($revisionary) )
		$revisionary->skip_revision_allowance = false;

	foreach ( $reqd_caps as $cap_name ) {
		if( empty($capabilities[$cap_name]) || ! $capabilities[$cap_name] ) {
			// if we're about to fail due to a missing create_child_pages cap, honor edit_pages cap as equivalent
			// TODO: abstract this with cap_defs property
			if ( 'create_child_pages' == $cap_name ) {
				$alternate_cap_name = 'edit_pages';
				$_args = array( array($alternate_cap_name), $user->ID, $object_id );
				$capabilities = apply_filters('user_has_cap', $user->allcaps, array($alternate_cap_name), $_args);
				
				if ( empty($capabilities[$alternate_cap_name]) || ! $capabilities[$alternate_cap_name] )
					return false;
			} else
				return false;
		}
	}

	return true;
}


?>