<?php

namespace Objectiv\Plugins\Checkout\Factories;

use Exception;
use Objectiv\Plugins\Checkout\Interfaces\BumpInterface;
use Objectiv\Plugins\Checkout\Model\Bumps\AllProductsBump;
use Objectiv\Plugins\Checkout\Model\Bumps\BumpAbstract;
use Objectiv\Plugins\Checkout\Model\Bumps\CategoriesBump;
use Objectiv\Plugins\Checkout\Model\Bumps\NullBump;
use Objectiv\Plugins\Checkout\Model\Bumps\SpecificProductsBump;

class BumpFactory {
	public static function get( int $post_id ): BumpInterface {
		if ( empty( $post_id ) ) { // because get_post tries to snag the global post if it's empty
			return new NullBump();
		}

		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return new NullBump();
		}

		$display_for = get_post_meta( $post->ID, 'cfw_ob_display_for', true );

		if ( 'all_products' === $display_for ) {
			$bump = new AllProductsBump();
		} elseif ( 'specific_categories' === $display_for ) {
			$bump = new CategoriesBump();
		} elseif ( 'specific_products' === $display_for ) {
			$bump = new SpecificProductsBump();
		} else {
			return new NullBump();
		}

		$bump->load( $post );

		return $bump;
	}

	/**
	 * @return BumpAbstract[]
	 * @throws Exception
	 */
	public static function get_all(): array {
		$posts = get_posts(
			array(
				'post_type'        => BumpAbstract::get_post_type(),
				'numberposts'      => -1,
				'suppress_filters' => false,
				'orderby'          => 'menu_order',
				'order'			   => 'ASC',
			)
		);

		$non_null_bumps  = array();
		$null_bump_class = get_class( new NullBump() );

		foreach ( $posts as $post ) {
			$bump = self::get( $post->ID );

			if ( get_class( $bump ) === $null_bump_class ) {
				continue;
			}

			$non_null_bumps[] = $bump;
		}

		return array_filter( $non_null_bumps );
	}
}
