<?php
/**
 * class-groups-shortcodes.php
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

/**
 * Shortcode handlers
 */
class Groups_Shortcodes {
	
	/**
	 * Adds shortcodes.
	 */
	public static function init() {
		// group info
		add_shortcode( 'groups_group_info', array( __CLASS__, 'groups_group_info' ) );
		// user groups
		add_shortcode( 'groups_user_groups', array( __CLASS__, 'groups_user_groups' ) );
		// groups
		add_shortcode( 'groups_groups',  array( __CLASS__, 'groups_groups' ) );
	}
	
	/**
	 * Renders information about a group.
	 * Attributes:
	 * - "group" : group name or id
	 * - "show" : what to show, can be "name", "description", "count" 
	 * 
	 * @param array $atts attributes
	 * @param string $content content to render
	 * @return rendered information
	 */
	public static function groups_group_info( $atts, $content = null ) {
		global $wpdb;
		$output = "";
		$options = shortcode_atts(
			array(
				'group' => '',
				'show' => '',
				'format' => '',
				'single' => '1',
				'plural' => '%d'
			),
			$atts
		);
		$group = trim( $options['group'] );
		$current_group = Groups_Group::read( $group );
		if ( !$current_group ) {
			$current_group = Groups_Group::read_by_name( $group );
		}
		if ( $current_group ) {
			switch( $options['show'] ) {
				case 'name' :
					$output .= wp_filter_nohtml_kses( $current_group->name );
					break;
				case 'description' :
					$output .= wp_filter_nohtml_kses( $current_group->description );
					break;
				case 'count' :
					$user_group_table = _groups_get_tablename( "user_group" );
					$count = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM $user_group_table WHERE group_id = %d",
						Groups_Utility::id( $current_group->group_id )
					) );
					if ( $count === null ) {
						$count = 0;
					} else {
						$count = intval( $count );
					}
					$output .= _n( $options['single'], sprintf( $options['plural'], $count ), $count, GROUPS_PLUGIN_DOMAIN );
					break;
			}
		}
		return $output;
	}
	
	/**
	 * Renders the current or a specific user's groups.
	 * Attributes:
	 * - "user_id" OR "user_login" OR "user_email" to identify the user, if none given assumes the current user
	 * - "format" : one of "list" "div" "ul" or "ol" - "list" and "ul" are equivalent
	 * - "list_class" : defaults to "groups"
	 * - "item_class" : defaults to "name"
	 * - "order_by"   : defaults to "name", also accepts "group_id"
	 * - "order"      : default to "ASC", also accepts "asc", "desc" and "DESC"
	 * 
	 * @param array $atts attributes
	 * @param string $content not used
	 * @return rendered groups for current user
	 */
	public static function groups_user_groups( $atts, $content = null ) {
		$output = "";
		$options = shortcode_atts(
			array(
				'user_id' => null,
				'user_login' => null,
				'user_email' => null,
				'format' => 'list',
				'list_class' => 'groups',
				'item_class' => 'name',
				'order_by' => 'name',
				'order' => 'ASC'
			),
			$atts
		);
		$user_id = null;
		if ( $options['user_id'] !== null ) {
			if ( $user = get_user_by( 'id', $options['user_id'] ) ) {
				$user_id = $user->ID;
			}
		} else if ( $options['user_id'] !== null ) {
			if ( $user = get_user_by( 'login', $options['user_login'] ) ) {
				$user_id = $user->ID;
			}
		} else if ( $options['user_email'] !== null ) {
			if ( $user = get_user_by( 'email', $options['user_login'] ) ) {
				$user_id = $user->ID;
			}
		}
		if ( $user_id === null ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id !== null ) {
			$user = new Groups_User( $user_id );
			$groups = $user->__get( 'groups' );
			if ( !empty( $groups ) ) {
				switch( $options['order_by'] ) {
					case 'group_id' :
						usort( $groups, array( __CLASS__, 'sort_id' ) );
						break;
					default :
						usort( $groups, array( __CLASS__, 'sort_name' ) );
				}
				switch( $options['order'] ) {
					case 'desc' :
					case 'DESC' :
						$groups = array_reverse( $groups );
						break;
				}
				
				switch( $options['format'] ) {
					case 'list' :
					case 'ul' :
						$output .= '<ul class="' . esc_attr( $options['list_class'] ) . '">';
						break;
					case 'ol' :
						$output .= '<ol class="' . esc_attr( $options['list_class'] ) . '">';
						break;
					default :
						$output .= '<div class="' . esc_attr( $options['list_class'] ) . '">';
				}
				foreach( $groups as $group ) {
					switch( $options['format'] ) {
						case 'list' :
						case 'ul' :
						case 'ol' :
							$output .= '<li class="' . esc_attr( $options['item_class'] ) . '">' . $group->__get( 'name' ) . '</li>';
							break;
						default :
							$output .= '<div class="' . esc_attr( $options['item_class'] ) . '">' . $group->__get( 'name' ) . '</div>';
					}
				}
				switch( $options['format'] ) {
					case 'list' :
					case 'ul' :
						$output .= '</ul>';
						break;
					case 'ol' :
						$output .= '</ol>';
						break;
					default :
						$output .= '</div>';
				}
			}
		}
		return $output;
	}
	
	/**
	 * Group comparison by group_id.
	 *
	 * @param Groups_Group $a
	 * @param Groups_Group $b
	 * @return int
	 */
	public static function sort_id( $a, $b ) {
		return $a->group_id - $b->group_id;
	}
	
	/**
	 * Group comparison by name.
	 * 
	 * @param Groups_Group $a
	 * @param Groups_Group $b
	 * @return int
	 */
	public static function sort_name( $a, $b ) {
		return strcmp( $a->name, $b->name );
	}

	/**
	 * Renders a list of the site's groups.
	 * Attributes:
	 * - "format" : one of "list" "div" "ul" or "ol" - "list" and "ul" are equivalent
	 * - "list_class" : defaults to "groups"
	 * - "item_class" : defaults to "name"
	 * - "order_by"   : defaults to "name", also accepts "group_id"
	 * - "order"      : default to "ASC", also accepts "asc", "desc" and "DESC"
	 *
	 * @param array $atts attributes
	 * @param string $content not used
	 * @return rendered groups
	 */
	public static function groups_groups( $atts, $content = null ) {
		global $wpdb;
		$output = "";
		$options = shortcode_atts(
			array(
				'format' => 'list',
				'list_class' => 'groups',
				'item_class' => 'name',
				'order_by' => 'name',
				'order' => 'ASC'
			),
			$atts
		);
		switch( $options['order_by'] ) {
			case 'group_id' :
			case 'name' :
				$order_by = $options['order_by'];
				break;
			default :
				$order_by = 'name';
		}
		switch( $options['order'] ) {
			case 'asc' :
			case 'ASC' :
			case 'desc' :
			case 'DESC' :
				$order = strtoupper( $options['order'] );
				break;
			default :
				$order = 'ASC';
		}
		$group_table = _groups_get_tablename( "group" );
		if ( $groups = $wpdb->get_results( $wpdb->prepare(
			"SELECT group_id FROM $group_table ORDER BY $order_by $order"
		) ) ) {
			switch( $options['format'] ) {
				case 'list' :
				case 'ul' :
					$output .= '<ul class="' . esc_attr( $options['list_class'] ) . '">';
					break;
				case 'ol' :
					$output .= '<ol class="' . esc_attr( $options['list_class'] ) . '">';
					break;
				default :
					$output .= '<div class="' . esc_attr( $options['list_class'] ) . '">';
			}
			foreach( $groups as $group ) {
				$group = new Groups_Group( $group->group_id );
				switch( $options['format'] ) {
					case 'list' :
					case 'ul' :
					case 'ol' :
						$output .= '<li class="' . esc_attr( $options['item_class'] ) . '">' . $group->__get( 'name' ) . '</li>';
						break;
					default :
						$output .= '<div class="' . esc_attr( $options['item_class'] ) . '">' . $group->__get( 'name' ) . '</div>';
				}
			}
			switch( $options['format'] ) {
				case 'list' :
				case 'ul' :
					$output .= '</ul>';
					break;
				case 'ol' :
					$output .= '</ol>';
					break;
				default :
					$output .= '</div>';
			}
		}
		return $output;
	}
}
Groups_Shortcodes::init();
