<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class ScoperCustomTaxonomyHelper {

	function get_terms_query_vars($tx, $terms_only = false) {
		global $scoper;
		
		// query on custom taxonomy schema does not involve any object data, so refer to term_id in term table
		if ( $terms_only ) {
			$tx_src = $scoper->data_sources->get( $tx->source );
			
			$tmp = array();
			$tmp['table'] = $tx_src->table;
			$tmp['alias'] = ($tx_src->table_alias) ? $tx_src->table_alias : $tmp['table'];
			$tmp['as'] = ( $tx_src->table_alias && ($tx_src->table_alias != $tx_src->table) ) ? "AS {$tmp['alias']}" : '';
			$tmp['col_id'] = $tx_src->cols->id;
			$arr['term'] = (object) $tmp;
			
		// this corresponds to 'category_id' in wp_post2cat (WP < 2.3), or some equivalent custom table
		} elseif ( ! empty($tx->cols->term2obj_tid) ) {
			$tmp = array();
			$tmp['table'] = $tx->table_term2obj;
			$tmp['alias'] = ($tx->table_term2obj_alias) ? $tx->table_term2obj_alias : $tmp['table'];
			$tmp['as'] = ( $tmp['alias'] && ($tmp['alias'] != $tmp['table']) ) ? "AS {$tmp['alias']}" : '';
			$tmp['col_id'] = $tx->cols->term2obj_tid;
			$tmp['col_obj_id'] = $tx->cols->term2obj_oid;
			$arr['term'] = (object) $tmp;
			
			$obj_src = $scoper->data_sources->get( $tx->object_source );
			
			$tmp = array();
			$tmp['table'] = $obj_src->table;
			$tmp['alias'] = ($obj_src->table_alias) ? $obj_src->table_alias : $tmp['table'];
			$tmp['as'] = ( $tmp['alias'] && ($tmp['alias'] != $tmp['table']) ) ? "AS {$tmp['alias']}" : '';
			$tmp['col_id'] = $obj_src->cols->id;
			$arr['obj'] = (object) $tmp;
			
		// also support custom taxonomies which store a single term_id right in object table
		} elseif ( ! empty($tx->cols->objtable_tid) ) {
			global $scoper;
			$obj_src = $scoper->data_sources->get( $tx->object_source );
			
			$tmp = array();
			$tmp['table'] = $obj_src->table;
			$tmp['alias'] = ($obj_src->table_alias) ? $obj_src->table_alias : $tmp['table'];
			$tmp['as'] = ( $tmp['alias'] && ($tmp['alias'] != $tmp['table']) ) ? "AS {$tmp['alias']}" : '';
			$tmp['col_id'] = $tx->cols->objtable_tid;
			$tmp['col_obj_id'] = $obj_src->cols->id;
			$arr['term'] = (object) $tmp;
			
			$tmp = array();
			$tmp['table'] = $tmp['table'];
			$tmp['alias'] = $tmp['alias'];
			$tmp['as'] = $tmp['as'];
			$tmp['col_id'] = $tmp['col_obj_id'];
			$arr['obj'] = (object) $tmp;
		} else {
			rs_notice( sprintf( 'Role Scoper Config Error: the specified taxonomy (%s) has not defined its relation to the object data source.  A col_term2obj_tid or col_objtable_tid setting is required.', $tx->name) ); 
			return;
		}

		return (object) $arr;
	}

	// This function is only used for custom taxonomies that don't use wp_term_taxonomy
	function get_terms_query($tx, $cols = COLS_ALL_RS, $object_id = 0, $terms_only = true) {
		$join = $where = $orderby = '';

		// this taxonomy uses a custom schema
		global $scoper;
		$tx_src = $scoper->data_sources->get( $tx->source );
		$table = $tx_src->table;

		// table alias
		$t = ($tx_src->table_alias) ? $tx_src->table_alias : $table;

		$as = ( $t != $table ) ? "AS $t" : '';

		if ( ! empty($object_id) ) {
			$qv = ScoperCustomTaxonomyHelper::get_terms_query_vars($tx, $terms_only);

			if ( ! $terms_only )
				$join = " INNER JOIN {$qv->obj->table} {$qv->obj->as} ON {$qv->obj->alias}.{$qv->obj->col_id} = $t.{$qv->term->col_obj_id} AND {$qv->obj->alias}.{$qv->obj->col_id} = '$object_id'";
		}
		
		if ( COL_TAXONOMY_ID_RS == $cols )  // term_id / tt_id in separate tables is only supported for taxonomies using standard WP schema
			$cols = COL_ID_RS;

		switch ( $cols ) {
			case COL_ID_RS:
				$qcols = "$t.{$tx_src->cols->id}";
				break;
			case COL_COUNT_RS:
				$qcols = "COUNT($t.{$tx_src->cols->id})";
				break;
			default: // COLS_ALL {
				$qcols = "$t.*";
				$orderby = "ORDER BY $t.{$tx_src->cols->name}";
		}

		if ( ! empty($tx->cols->require_zero) )
			$where .= " AND {$tx->cols->require_zero} = '0'";
			
		if ( ! empty($tx->cols->require_nonzero) )
			$where .= " AND {$tx->cols->require_nonzero} > '0'";

		return "SELECT DISTINCT $qcols FROM $table $as $join WHERE 1=1 $where $orderby";
	}

} // end class
?>