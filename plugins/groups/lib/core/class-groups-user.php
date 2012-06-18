<?php
/**
 * class-groups-user.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package groups
 * @since groups 1.0.0
 */

require_once( GROUPS_CORE_LIB . "/interface-i-capable.php" );
require_once( GROUPS_CORE_LIB . "/class-groups-capability.php" );

/**
 * User OPM.
 */
class Groups_User implements I_Capable {
	
	/**
	 * User object.
	 * 
	 * @var WP_User
	 */
	var $user = null;
		
	/**
	 * Create, if $user_id = 0 an anonymous user is assumed.
	 *
	 * @param int $user_id
	 */
	public function __construct( $user_id ) {
		if ( Groups_Utility::id( $user_id ) ) {
			$this->user = get_user_by( "id", $user_id );
		} else {
			$this->user = new WP_User( 0 );
		}
	}
	
	/**
	 * Retrieve a user property.
	 * Must be "capabilities" or a property of the WP_User class.
	 * @param string $name property's name
	 */
	public function __get( $name ) {
		
		global $wpdb;
		
		$result = null;
		
		if ( $this->user !== null ) {
			switch ( $name ) {
				case "capabilities" :
					$user_capability_table = _groups_get_tablename( "user_capability" );
					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT capability_id FROM $user_capability_table WHERE user_id = %d",
						Groups_Utility::id( $this->user->ID )
					) );
					if ( $rows ) {
						$result = array();
						foreach ( $rows as $row ) {
							$result[] = new Groups_Capability( $row->capability_id );
						}
					}
					break;
				case "groups" :
					$user_group_table = _groups_get_tablename( "user_group" );
					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT group_id FROM $user_group_table WHERE user_id = %d",
						Groups_Utility::id( $this->user->ID )
					) );
					if ( $rows ) {
						$result = array();
						foreach( $rows as $row ) {
							$result[] = new Groups_Group( $row->group_id );
						}
					}
					break;
				default:
					$result = $this->user->$name;
			}
		}
		return $result;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see I_Capable::can()
	 */
	public function can( $capability ) {
		
		global $wpdb;
		$result = false;
		
		
		
		if ( $this->user !== null ) {
			
			// if administrators can override access, let them
			if ( get_option( GROUPS_ADMINISTRATOR_ACCESS_OVERRIDE, GROUPS_ADMINISTRATOR_ACCESS_OVERRIDE_DEFAULT ) ) {
				if ( user_can( $this->user->ID, 'administrator' ) ) { // just using $this->user would raise a warning on 3.2.1
					return true;
				}
			}
			
			$group_table = _groups_get_tablename( "group" );
			$capability_table = _groups_get_tablename( "capability" );
			$group_capability_table = _groups_get_tablename( "group_capability" );
			$user_group_table = _groups_get_tablename( "user_group" );
			
			// determine capability id
			$capability_id = null;
			if ( is_numeric( $capability ) ) {
				$capability_id = Groups_Utility::id( $capability );
			} else if ( is_string( $capability ) ) {
				$capability_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT capability_id FROM $capability_table WHERE capability = %s",
					$capability
				) );
			}
			
			if ( $capability_id !== null ) {
				// either the user can ...
				$result = ( Groups_User_Capability::read( $this->user->ID, $capability_id ) !== false );
				// ... or the user can because a group the user belongs to can
				if ( !$result ) {
					// Important: before making any changes check
					// INDEX usage on modified query!
					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT capability_id FROM $group_capability_table WHERE capability_id = %d AND group_id IN (SELECT group_id FROM $user_group_table WHERE user_id = %d)",
						Groups_Utility::id( $capability_id ),
						Groups_Utility::id( $this->user->ID )
					) );
					if ( count( $rows ) > 0 ) {
						$result = true;
					}
				}
				// ... or because any of the parent groups can
				if ( !$result ) {
					// search in parent groups
					$limit = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $group_table" ) );
					if ( $limit !== null ) {
						
						// note that limits by blog_id for multisite are
						// enforced when a user is added to a blog
						$user_groups = $wpdb->get_results( $wpdb->prepare(
							"SELECT group_id FROM $user_group_table WHERE user_id = %d",
							Groups_Utility::id( $this->user->ID )
						) );
						
						if ( $user_groups ) {
							$group_ids = array();
							foreach( $user_groups as $user_group ) {
								$group_ids[] = Groups_Utility::id( $user_group->group_id );
							}
							if ( count( $group_ids ) > 0 ) {
								$iterations          = 0;
								$old_group_ids_count = 0;
								while( ( $iterations < $limit ) && ( count( $group_ids ) !== $old_group_ids_count ) ) {
									$iterations++;
									$old_group_ids_count = count( $group_ids );
									$id_list = implode( ",", $group_ids );
									$parent_group_ids = $wpdb->get_results(
										"SELECT parent_id FROM $group_table WHERE parent_id IS NOT NULL AND group_id IN ($id_list)"
									);
									if ( $parent_group_ids ) {
										foreach( $parent_group_ids as $parent_group_id ) {
											$parent_group_id = Groups_Utility::id( $parent_group_id->parent_id );
											if ( !in_array( $parent_group_id, $group_ids ) ) {
												$group_ids[] = $parent_group_id;
											}
										}
									}
								}
								$id_list = implode( ",", $group_ids );
								$rows = $wpdb->get_results( $wpdb->prepare(
									"SELECT capability_id FROM $group_capability_table WHERE capability_id = %d AND group_id IN ($id_list)",
									Groups_Utility::id( $capability_id )
								) );
								if ( count( $rows ) > 0 ) {
									$result = true;
								}
							}
						}
					}
				}
			}
		}
		$result = apply_filters_ref_array( "groups_user_can", array( $result, &$this, $capability ) );
		return $result;
	}
}