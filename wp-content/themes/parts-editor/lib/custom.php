<?php

$colors = array('#ffad46', '#42d692', '#4986E7', '#fb4c2f', '#A81F5E','#F03826','#F2811D','#44A143','#3A5A64','#ffad46', '#42d692', '#4986E7', '#fb4c2f', '#A81F5E','#F03826','#F2811D','#44A143','#3A5A64');

function the_excerpt_max_charlength($charlength) {
    $excerpt = get_the_excerpt();
    $charlength++;

    if ( mb_strlen( $excerpt ) > $charlength ) {
        $subex = mb_substr( $excerpt, 0, $charlength - 5 );
        $exwords = explode( ' ', $subex );
        $excut = - ( mb_strlen( $exwords[ count( $exwords ) - 1 ] ) );
        if ( $excut < 0 ) {
            return mb_substr( $subex, 0, $excut );
        } else {
            return $subex;
        }
        return '[...]';
    } else {
        return $excerpt;
    }
}

function print_taxonomy_ranks( $terms = '' ){

    $order = $family = $subfamily = '';

    // check input
    if ( empty( $terms ) || is_wp_error( $terms ) || ! is_array( $terms ) )
        return;

    // set id variables to 0 for easy check 
    $order_id = $family_id = $subfamily_id = 0;

    // get order
    foreach ( $terms as $term ) {
        if ( $order_id || $term->parent )
            continue;
        $order_id  = $term->term_id;
        $order     = $term->name;
    }

    // get family
    foreach ( $terms as $term ) { 
        if ( $family_id || $order_id != $term->parent )
            continue;
        $family_id = $term->term_id;
        $family    = $term->name;
    }

    // get subfamily
    foreach ( $terms as $term ) { 
        if ( $subfamily_id || $family_id != $term->parent ) 
            continue;
        $subfamily_id = $term->term_id;
        $subfamily    = $term->name;
    }

    // output
    echo "Order: $order, Family: $family, Sub-family: $subfamily";

}

function the_applied_taxononmies($part_id, $contentOnly=false){

    echo (!$contentOnly) ? "<dl class='dl-horizontal applied-taxonomies'>" : "";

    echo "<dt>Taxonomy</dt>";
    
    // get terms for current post
    $terms = wp_get_object_terms( $part_id, 'fz_taxonomy_2013' );
    // set vars
    $top_parent_terms = array();
    $r = "";
    foreach ( $terms as $term ) {
        //get top level parent
        $top_parent = get_term_top_most_parent( $term->term_id, 'fz_taxonomy_2013' );
        echo "<dd>{$top_parent->name} &rsaquo; {$term->name}</dd>";
    }
    // build output (the HTML is up to you)

    echo (!$contentOnly) ? "</dl>" : "";
}

function getPartIdsByFamilyId( $_family_id ){
    
    //get the parts of the group
    $args = array(
                'post_type' => 'fz_fzp',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'fz_original_family',
                        'field' => 'id',
                        'terms' => $_family_id
                    )
                )
            );
    $query = new WP_Query( $args );

    //store all ids in an array
    $part_ids = array();
    foreach($query->posts as $part)
        array_push($part_ids, $part->ID);

    return $part_ids;
}



function createBinInstanceNode($_dom, $_moduleId, $_modelIndex, $_path){
    
    //proxy dom
    $dom = $_dom;

    //create instance node
    $instance_node = $dom->createElement('instance');

    //set attributes
    $instance_node->setAttribute('moduleIdRef', $_moduleId);
    $instance_node->setAttribute('modelIndex', $_modelIndex);
    $instance_node->setAttribute('path', $_path);

    //views
    $views_node = $dom->createElement('views');
    $iconView_node = $dom->createElement('iconView');
    $geometry_node = $dom->createElement('geometry');

    //set attributes
    $iconView_node->setAttribute('layer', 'icon');
    $iconView_node->setAttribute('layer', 'icon');
    $geometry_node->setAttribute('z', '-1');
    $geometry_node->setAttribute('x', '-1');
    $geometry_node->setAttribute('y', '-1');

    //stack elements
    $iconView_node->appendChild($geometry_node);
    $views_node->appendChild($iconView_node);
    $instance_node->appendChild($views_node);

    return $instance_node;
}

function get_terms_by_parent($_taxonomy, $_parent){

    $args = array(
        'orderby'       => 'name', 
        'order'         => 'ASC',
        'hide_empty'    => false,  
        'parent'        => $_parent,
        'hierarchical'  => true, 
        'child_of'      => 0
    );

    $output = get_terms($_taxonomy, $args);

    return $output;
}


// replace node content in dom
function replaceNodeContent($_dom, $_xpath, $_newcontent){
    
    $xpath = new DOMXPath( $_dom );

    $fragA = $_dom->createTextNode( $_newcontent ); 
    $xpResult = $xpath->query( $_xpath );   
    $blipblip = $xpResult->item(0)->appendChild( $fragA );

    return $blipblip;
}

// determine the topmost parent of a term
function get_term_top_most_parent($term_id, $taxonomy){
    // start from the current term
    $parent  = get_term_by( 'id', $term_id, $taxonomy);
    // climb up the hierarchy until we reach a term with parent = '0'
    while ($parent->parent != '0'){
        $term_id = $parent->parent;

        $parent  = get_term_by( 'id', $term_id, $taxonomy);
    }
    return $parent;
}

// so once you have this function you can just loop over the results returned by wp_get_object_terms

function hey_top_parents($taxonomy, $post_id) {

    // get terms for current post
    $terms = wp_get_object_terms( $post_id, $taxonomy );
    // set vars
    $top_parent_terms = array();
    $r = "";
    foreach ( $terms as $term ) {
        //get top level parent
        $top_parent = get_term_top_most_parent( $term->term_id, $taxonomy );
        $r .= "<span class='part-tax'><b>" . $top_parent->name . "</b> / " . $term->name . "</span><br>\n";
    }
    // build output (the HTML is up to you)

    // return the results
    return $r;

}



if( !function_exists( 'mfields_overwrite_hide_taxonomies_from_admin' ) ) {
    add_action( 'add_meta_boxes', 'mfields_hide_taxonomies_from_admin' );
    function mfields_hide_taxonomies_from_admin() {
        global $wp_taxonomies;
        $hide = array( 
            'fz_lbr'
            );
        foreach( $wp_taxonomies as $name => $taxonomy ) {
            if( in_array( $name, $hide ) ) {
                remove_meta_box( 'tagsdiv-' . $name, 'fz_part', 'side' );
                add_meta_box(
                    'mfields_taxonomy_ui_' . $name,
                    $taxonomy->label,
                    'my_custom_taxonomy_handler_function',
                    'post',
                    'side',
                    'core',
                    array( 'taxonomy' => $name )
                    );
            }
        }
    }
}

// custom loop for grouping fzp

function query_group_fzp( ){
    global $wpdb, $paged, $max_num_pages, $current_date;

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $post_per_page = intval(get_query_var('posts_per_page'));
    $offset = ($paged - 1)*$post_per_page;

    /* Custom sql here. I left out the important bits and deleted the body 
     as it will be specific when you have your own. */
    $sql = "
        SELECT *
        FROM {$wpdb->posts}
        WHERE post_type = 'fz_fzp'
        AND post_status = 'publish'
        GROUP BY {$wpdb->posts}.post_title 
        ORDER BY {$wpdb->posts}.post_date DESC";
        //LIMIT ".$offset.", ".$post_per_page."; ";   

    $sql_result = $wpdb->get_results( $sql, OBJECT);

    /* Determine the total of results found to calculate the max_num_pages
     for next_posts_link navigation */
    $sql_posts_total = $wpdb->get_var( "SELECT FOUND_ROWS();" );
    $max_num_pages = ceil($sql_posts_total / $post_per_page);

    return $sql_result;
}