<?php
/*
Plugin Name: Aparat Embed
Plugin URI:  http://wordpress.org/plugins/aparat-embed/
Description: Embed support for http://aparat.com videos.
Author:      Hassan Derakhshandeh
Version:     0.1
Author URI:  http://shazdeh.me/
*/

class Aparat_Embed {

	private static $instance = null;

	public static function get_instance() {
		return null == self::$instance ? self::$instance = new self : self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_embed_handler' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		add_filter( 'mce_css', array( $this, 'mce_css' ) );
	}

	public function register_embed_handler() {
		wp_embed_register_handler( 'aparat', '#http://(?:www)\.aparat\.com\/v\/(.*?)\/?$#i', array( $this, 'video_embed' ), 5 );
		wp_embed_register_handler( 'aparat', '#http://(?:www)\.aparat\.com\/(.*?)\/?$#i', array( $this, 'channel_embed' ), 20 );
	}

	public function video_embed( $matches, $attr, $url, $rawattr ) {
		$output = sprintf(
			'<iframe src="http://www.aparat.com/video/video/embed/videohash/%s/vt/frame" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" width="640" height="360"></iframe>',
			$matches[1]
		);

		return apply_filters( 'aparat_embed', $output, $matches, $attr, $url, $rawattr );
	}

	public function channel_embed( $matches, $attr, $url, $rawattr ) {
		$rss = fetch_feed( sprintf( 'http://www.aparat.com/rss/%s', $matches[1] ) );
		if( is_wp_error( $rss ) ) {
			return $url;
		}

		$items = 10;
		if ( !$rss->get_item_quantity() ) {
			$rss->__destruct();
			unset( $rss );
			return __( 'Error.', 'aparat-embed' );
		}

		$output = '<div class="aparat-embed-list">';
		foreach ( $rss->get_items( 0, $items ) as $item ) {
			$link = $item->get_link();
			while ( stristr( $link, 'http' ) != $link ) {
				$link = substr( $link, 1 );
			}
			$link = esc_url( strip_tags( $link ) );

			$title = esc_html( trim( strip_tags( $item->get_title() ) ) );
			if ( empty( $title ) ) {
				$title = __( 'Untitled' );
			}

			preg_match( '#http://(?:www)\.aparat\.com\/v\/(.*?)\/#i', $link, $matches );
			$video = sprintf(
				'<iframe src="http://www.aparat.com/video/video/embed/videohash/%s/vt/frame" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" width="640" height="360"></iframe>',
				$matches[1]
			);

			$desc = @html_entity_decode( $item->get_description(), ENT_QUOTES, get_option( 'blog_charset' ) );
			$desc = esc_attr( wp_trim_words( $desc, 55, ' [&hellip;]' ) );

			$date = date_i18n( get_option( 'date_format' ), $item->get_date( 'U' ) );

			$output .= sprintf(
				'<div class="video-item">
					<div class="video-preview">%s</div>
					<div class="video-description">
						<h4 class="video-title"><a href="%s">%s</a></h4>
						<span class="video-date">%s</span>
						%s
					</div>
				</div>',
				$video,
				$link,
				$title,
				$date,
				esc_html( $desc )
			);
		}
		$output .= '</div>';
		$rss->__destruct();
		unset($rss);

		return apply_filters( 'aparat_channel_embed', $output, $matches, $attr, $url, $rawattr );
	}

	public function wp_enqueue_scripts() {
		wp_enqueue_style( 'aparat-embed', plugins_url( 'assets/style.css', __FILE__ ) );
	}

	function mce_css( $mce_css ) {
		if( ! empty( $mce_css ) ) $mce_css .= ',';
		$mce_css .= plugins_url( 'assets/style.css', __FILE__ );
		return $mce_css;
	}
}

add_action( 'plugins_loaded', array( 'Aparat_Embed', 'get_instance' ) );