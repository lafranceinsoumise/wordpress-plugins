<?php
/*
    Plugin Name: RSS Aggregator - Link to source
    Description: Plugin ultrabasique pour lier les titres aux articles originaux
    Author: Jill Royer
    License: GPL-3.0
*/

function rss_aggregator_link_to_source($url, $post) {
    $permalink = '';

    if ($post->post_type == 'post') {
        $permalink = get_post_meta($post->ID, "wprss_item_permalink", true);
    }

    if ($permalink !== '') {
        return $permalink . '?" target="_blank';
    }

    return $url;
}

add_filter( 'post_link', 'rss_aggregator_link_to_source', 10, 3 );
