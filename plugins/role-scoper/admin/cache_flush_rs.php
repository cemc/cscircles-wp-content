<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

function scoper_flush_results_cache( $role_bases = '', $user_ids = array() ) {}
function scoper_flush_roles_cache( $scope, $role_bases = '', $user_ids = array(), $taxonomies = '' ) {}	
function scoper_flush_restriction_cache( $scope, $src_or_tx_name = '' ) {}
?>