<?php
/**
 * Generates and removes realistic tourism sample posts.
 *
 * @package Realistic_Tourism_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RTCG_Generator {

	const POST_META_FLAG       = '_rtcg_generated';
	const UNIQUE_META_KEY      = '_rtcg_unique_key';
	const BATCH_META_KEY       = '_rtcg_batch_id';
	const TERM_META_FLAG       = '_rtcg_created_by_plugin';
	const PARENT_CATEGORY_SLUG = 'rtcg-tourism-samples';

	/**
	 * Generator data.
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$data = require RTCG_PLUGIN_DIR . 'includes/data.php';

		if ( is_array( $data ) ) {
			$this->data = $data;
		}
	}

	/**
	 * Generate a batch of posts.
	 *
	 * @param array $args Generation options.
	 * @return array|WP_Error
	 */
	public function generate( $args = array() ) {
		$defaults = array(
			'count'      => 8,
			'post_status'=> 'draft',
			'date_range' => 90,
			'create_tags'=> true,
			'author_id'  => get_current_user_id(),
		);

		$args = wp_parse_args( $args, $defaults );

		$count       = max( 1, min( 20, absint( $args['count'] ) ) );
		$date_range  = max( 1, min( 365, absint( $args['date_range'] ) ) );
		$post_status = in_array( $args['post_status'], array( 'draft', 'publish' ), true ) ? $args['post_status'] : 'draft';
		$author_id   = absint( $args['author_id'] );
		$create_tags = ! empty( $args['create_tags'] );

		if ( empty( $this->data['destinations'] ) || empty( $this->data['categories'] ) || empty( $this->data['images'] ) ) {
			return new WP_Error( 'rtcg_missing_data', 'داده‌های داخلی افزونه کامل نیستند.' );
		}

		$category_ids = $this->ensure_categories();
		if ( is_wp_error( $category_ids ) ) {
			return $category_ids;
		}

		$batch_id  = gmdate( 'YmdHis' ) . '-' . strtolower( wp_generate_password( 6, false, false ) );
		$candidates = $this->build_candidate_pool( $batch_id );

		$created_ids = array();
		$errors      = array();

		foreach ( $candidates as $candidate ) {
			if ( count( $created_ids ) >= $count ) {
				break;
			}

			$result = $this->create_post(
				$candidate,
				array(
					'post_status' => $post_status,
					'date_range'  => $date_range,
					'create_tags' => $create_tags,
					'author_id'   => $author_id,
					'batch_id'    => $batch_id,
				),
				$category_ids
			);

			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
				continue;
			}

			$created_ids[] = (int) $result;
		}

		if ( empty( $created_ids ) ) {
			$message = ! empty( $errors ) ? implode( ' | ', array_slice( $errors, 0, 3 ) ) : 'هیچ نوشته‌ای ساخته نشد.';
			return new WP_Error( 'rtcg_generation_failed', $message );
		}

		return array(
			'batch_id'   => $batch_id,
			'requested'  => $count,
			'created'    => count( $created_ids ),
			'post_ids'   => $created_ids,
			'errors'     => $errors,
		);
	}

	/**
	 * Delete posts, attachments, and empty terms created by this plugin.
	 *
	 * @return array
	 */
	public function delete_generated_content() {
		$post_ids = get_posts(
			array(
				'post_type'              => 'post',
				'post_status'            => array_keys( get_post_stati() ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => self::POST_META_FLAG,
						'value' => '1',
					),
				),
			)
		);

		$deleted_posts       = 0;
		$deleted_attachments = 0;
		$deleted_attachment_ids = array();

		foreach ( $post_ids as $post_id ) {
			$thumbnail_id = get_post_thumbnail_id( $post_id );

			if ( $thumbnail_id && '1' === (string) get_post_meta( $thumbnail_id, self::POST_META_FLAG, true ) ) {
				if ( wp_delete_attachment( $thumbnail_id, true ) ) {
					$deleted_attachments++;
					$deleted_attachment_ids[ (int) $thumbnail_id ] = true;
				}
			}

			if ( wp_delete_post( $post_id, true ) ) {
				$deleted_posts++;
			}
		}

		$orphan_attachment_ids = get_posts(
			array(
				'post_type'              => 'attachment',
				'post_status'            => array_keys( get_post_stati() ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => self::POST_META_FLAG,
						'value' => '1',
					),
				),
			)
		);

		foreach ( $orphan_attachment_ids as $attachment_id ) {
			if ( isset( $deleted_attachment_ids[ (int) $attachment_id ] ) ) {
				continue;
			}

			if ( wp_delete_attachment( $attachment_id, true ) ) {
				$deleted_attachments++;
			}
		}

		$deleted_terms = $this->delete_empty_generated_terms();

		return array(
			'posts'       => $deleted_posts,
			'attachments' => $deleted_attachments,
			'terms'       => $deleted_terms,
		);
	}

	/**
	 * Return generator statistics.
	 *
	 * @return array
	 */
	public function get_stats() {
		return array(
			'posts'        => $this->count_objects( 'post' ),
			'attachments'  => $this->count_objects( 'attachment' ),
			'destinations' => isset( $this->data['destinations'] ) ? count( $this->data['destinations'] ) : 0,
			'categories'   => isset( $this->data['categories'] ) ? count( $this->data['categories'] ) : 0,
		);
	}

	/**
	 * Return recent generated posts.
	 *
	 * @param int $limit Maximum posts.
	 * @return WP_Post[]
	 */
	public function get_recent_posts( $limit = 5 ) {
		return get_posts(
			array(
				'post_type'              => 'post',
				'post_status'            => array_keys( get_post_stati() ),
				'posts_per_page'         => max( 1, min( 10, absint( $limit ) ) ),
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => self::POST_META_FLAG,
						'value' => '1',
					),
				),
			)
		);
	}

	/**
	 * Create one post and its featured image.
	 *
	 * @param array $candidate Candidate data.
	 * @param array $options Generation options.
	 * @param array $category_ids Category ID map.
	 * @return int|WP_Error
	 */
	private function create_post( $candidate, $options, $category_ids ) {
		$destination = $candidate['destination'];
		$variant     = (int) $candidate['variant'];
		$unique_key  = (string) $candidate['unique_key'];
		$title       = $this->build_title( $destination, $variant );
		$content     = $this->build_content( $destination, $variant );
		$excerpt     = $this->build_excerpt( $destination );
		$post_date   = $this->random_post_date( (int) $options['date_range'] );
		$category_key = $destination['category'];

		if ( empty( $category_ids['parent'] ) || empty( $category_ids['children'][ $category_key ] ) ) {
			return new WP_Error( 'rtcg_category_missing', sprintf( 'دسته‌بندی نوشته «%s» پیدا نشد.', $title ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'      => 'post',
				'post_status'    => $options['post_status'],
				'post_author'    => $options['author_id'],
				'post_title'     => wp_strip_all_tags( $title ),
				'post_content'   => $content,
				'post_excerpt'   => wp_strip_all_tags( $excerpt ),
				'post_date'      => $post_date,
				'post_date_gmt'  => get_gmt_from_date( $post_date ),
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'meta_input'     => array(
					self::POST_META_FLAG      => '1',
					self::UNIQUE_META_KEY     => $unique_key,
					self::BATCH_META_KEY      => $options['batch_id'],
					'_rtcg_destination'       => $destination['place'],
					'_rtcg_province'          => $destination['province'],
					'_rtcg_best_season'       => $destination['best_season'],
					'_rtcg_suggested_duration'=> $destination['duration'],
					'_rtcg_generated_at'      => current_time( 'mysql' ),
				),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'rtcg_post_insert_failed', sprintf( 'ساخت نوشته «%1$s» ناموفق بود: %2$s', $title, $post_id->get_error_message() ) );
		}

		$assigned_categories = wp_set_post_terms(
			$post_id,
			array(
				(int) $category_ids['parent'],
				(int) $category_ids['children'][ $category_key ],
			),
			'category',
			false
		);

		if ( is_wp_error( $assigned_categories ) || false === $assigned_categories ) {
			wp_delete_post( $post_id, true );
			return new WP_Error( 'rtcg_category_assign_failed', sprintf( 'اتصال دسته‌بندی به نوشته «%s» ناموفق بود.', $title ) );
		}

		if ( ! empty( $options['create_tags'] ) ) {
			$tag_ids = $this->ensure_tags_for_destination( $destination, $category_key );

			if ( is_wp_error( $tag_ids ) ) {
				wp_delete_post( $post_id, true );
				return new WP_Error( 'rtcg_tag_create_failed', sprintf( 'ساخت برچسب‌های نوشته «%1$s» ناموفق بود: %2$s', $title, $tag_ids->get_error_message() ) );
			}

			$assigned_tags = wp_set_post_terms( $post_id, array_map( 'intval', $tag_ids ), 'post_tag', false );

			if ( is_wp_error( $assigned_tags ) || false === $assigned_tags ) {
				wp_delete_post( $post_id, true );
				return new WP_Error( 'rtcg_tag_assign_failed', sprintf( 'اتصال برچسب‌ها به نوشته «%s» ناموفق بود.', $title ) );
			}
		}

		$attachment_id = $this->attach_featured_image( $post_id, $destination, $title, $options['batch_id'] );

		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_post( $post_id, true );
			return new WP_Error( 'rtcg_featured_image_failed', sprintf( 'تصویر شاخص نوشته «%1$s» ساخته نشد: %2$s', $title, $attachment_id->get_error_message() ) );
		}

		return (int) $post_id;
	}

	/**
	 * Create or locate all categories.
	 *
	 * @return array|WP_Error
	 */
	private function ensure_categories() {
		$parent_id = $this->ensure_term(
			'گردشگری',
			'category',
			array(
				'slug'   => self::PARENT_CATEGORY_SLUG,
				'parent' => 0,
			)
		);

		if ( is_wp_error( $parent_id ) ) {
			return $parent_id;
		}

		$children = array();

		foreach ( $this->data['categories'] as $key => $category ) {
			$term_id = $this->ensure_term(
				$category['name'],
				'category',
				array(
					'slug'   => $category['slug'],
					'parent' => (int) $parent_id,
				)
			);

			if ( is_wp_error( $term_id ) ) {
				return $term_id;
			}

			$children[ $key ] = (int) $term_id;
		}

		return array(
			'parent'   => (int) $parent_id,
			'children' => $children,
		);
	}

	/**
	 * Create tags for one destination.
	 *
	 * @param array  $destination Destination data.
	 * @param string $category_key Category key.
	 * @return array|WP_Error
	 */
	private function ensure_tags_for_destination( $destination, $category_key ) {
		$category_name = isset( $this->data['categories'][ $category_key ]['name'] ) ? $this->data['categories'][ $category_key ]['name'] : 'گردشگری';
		$tag_names     = array_unique(
			array_filter(
				array(
					$destination['place'],
					$destination['province'],
					$category_name,
					'راهنمای سفر',
				)
			)
		);
		$tag_ids = array();

		foreach ( $tag_names as $tag_name ) {
			$term_id = $this->ensure_term( $tag_name, 'post_tag' );

			if ( is_wp_error( $term_id ) ) {
				return $term_id;
			}

			$tag_ids[] = (int) $term_id;
		}

		return $tag_ids;
	}

	/**
	 * Ensure a term exists and mark terms created by this plugin.
	 *
	 * @param string $name Term name.
	 * @param string $taxonomy Taxonomy name.
	 * @param array  $args Term arguments.
	 * @return int|WP_Error
	 */
	private function ensure_term( $name, $taxonomy, $args = array() ) {
		$lookup = ! empty( $args['slug'] ) ? $args['slug'] : $name;
		$exists = term_exists( $lookup, $taxonomy );

		if ( $exists ) {
			return is_array( $exists ) ? (int) $exists['term_id'] : (int) $exists;
		}

		$result = wp_insert_term( $name, $taxonomy, $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$term_id = (int) $result['term_id'];
		update_term_meta( $term_id, self::TERM_META_FLAG, '1' );

		return $term_id;
	}

	/**
	 * Build a large pool of unused post variants.
	 *
	 * @param string $batch_id Batch ID.
	 * @return array
	 */
	private function build_candidate_pool( $batch_id ) {
		$existing_keys = $this->get_existing_unique_keys();
		$grouped       = array();

		foreach ( $this->data['destinations'] as $destination ) {
			for ( $variant = 0; $variant < 6; $variant++ ) {
				$unique_key = $destination['key'] . '-v' . $variant;

				if ( isset( $existing_keys[ $unique_key ] ) ) {
					continue;
				}

				$category_key = (string) $destination['category'];

				if ( ! isset( $grouped[ $category_key ] ) ) {
					$grouped[ $category_key ] = array();
				}

				$grouped[ $category_key ][] = array(
					'destination' => $destination,
					'variant'     => $variant,
					'unique_key'  => $unique_key,
				);
			}
		}

		$candidates = $this->interleave_candidate_groups( $grouped );

		if ( count( $candidates ) < 20 ) {
			$fallback_groups = array();

			foreach ( $this->data['destinations'] as $index => $destination ) {
				$variant = $index % 6;
				$category_key = (string) $destination['category'];

				if ( ! isset( $fallback_groups[ $category_key ] ) ) {
					$fallback_groups[ $category_key ] = array();
				}

				$fallback_groups[ $category_key ][] = array(
					'destination' => $destination,
					'variant'     => $variant,
					'unique_key'  => $destination['key'] . '-v' . $variant . '-' . $batch_id . '-' . $index,
				);
			}

			$candidates = array_merge( $candidates, $this->interleave_candidate_groups( $fallback_groups ) );
		}

		return $candidates;
	}

	/**
	 * Shuffle candidates inside each category, then interleave categories.
	 *
	 * This keeps a normal batch varied instead of allowing a random shuffle to
	 * accidentally produce many consecutive posts from the same category.
	 *
	 * @param array $groups Candidate groups keyed by category.
	 * @return array
	 */
	private function interleave_candidate_groups( $groups ) {
		if ( empty( $groups ) ) {
			return array();
		}

		foreach ( $groups as $key => $items ) {
			shuffle( $items );
			$groups[ $key ] = $items;
		}

		$category_keys = array_keys( $groups );
		shuffle( $category_keys );
		$candidates = array();

		do {
			$added = false;

			foreach ( $category_keys as $category_key ) {
				if ( empty( $groups[ $category_key ] ) ) {
					continue;
				}

				$candidates[] = array_shift( $groups[ $category_key ] );
				$added        = true;
			}
		} while ( $added );

		return $candidates;
	}

	/**
	 * Fetch unique keys already generated by this plugin.
	 *
	 * @return array
	 */
	private function get_existing_unique_keys() {
		$post_ids = get_posts(
			array(
				'post_type'              => 'post',
				'post_status'            => array_keys( get_post_stati() ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'     => self::UNIQUE_META_KEY,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$keys = array();

		foreach ( $post_ids as $post_id ) {
			$key = (string) get_post_meta( $post_id, self::UNIQUE_META_KEY, true );
			if ( '' !== $key ) {
				$keys[ $key ] = true;
			}
		}

		return $keys;
	}

	/**
	 * Build a title variant.
	 *
	 * @param array $destination Destination.
	 * @param int   $variant Variant index.
	 * @return string
	 */
	private function build_title( $destination, $variant ) {
		$category_name = isset( $this->data['categories'][ $destination['category'] ]['name'] ) ? $this->data['categories'][ $destination['category'] ]['name'] : 'گردشگری';
		$titles = array(
			sprintf( 'راهنمای سفر به %s؛ از برنامه‌ریزی تا تجربه محلی', $destination['place'] ),
			sprintf( '%s در یک نگاه؛ برنامه پیشنهادی برای %s', $destination['place'], $destination['duration'] ),
			sprintf( 'سفر به %s؛ دیدنی‌ها، اقامت و نکات کاربردی', $destination['place'] ),
			sprintf( 'برنامه سفر %s به %s', $destination['duration'], $destination['place'] ),
			sprintf( '%s؛ تجربه‌ای از %s در %s', $destination['place'], $category_name, $destination['province'] ),
			sprintf( 'چطور به %s سفر کنیم؟ راهنمای کامل و کاربردی', $destination['place'] ),
		);

		return $titles[ absint( $variant ) % count( $titles ) ];
	}

	/**
	 * Build the excerpt.
	 *
	 * @param array $destination Destination.
	 * @return string
	 */
	private function build_excerpt( $destination ) {
		return sprintf(
			'%1$s زمان پیشنهادی برای این برنامه %2$s است و %3$s فصل مناسب‌تری برای سفر به شمار می‌آید.',
			$destination['lead'],
			$destination['duration'],
			$destination['best_season']
		);
	}

	/**
	 * Build realistic long-form Gutenberg-compatible HTML.
	 *
	 * @param array $destination Destination.
	 * @param int   $variant Variant index.
	 * @return string
	 */
	private function build_content( $destination, $variant ) {
		$openers = array(
			'برای اینکه سفر هم لذت‌بخش باشد و هم زمان در رفت‌وآمد هدر نرود، بهتر است برنامه را از قبل بر اساس فاصله‌ها و ساعت‌های خلوت تنظیم کنید.',
			'این مقصد را می‌توان بدون برنامه فشرده تجربه کرد؛ کافی است برای هر بخش زمان واقعی در نظر بگیرید و میان بازدیدها فرصت استراحت بگذارید.',
			'برنامه زیر برای مسافری نوشته شده که می‌خواهد دیدنی‌های اصلی، غذای محلی و فضای مقصد را در یک سفر متعادل کنار هم تجربه کند.',
			'بهترین شکل سفر به این منطقه، حرکت آرام و انتخاب چند تجربه باکیفیت به‌جای فهرست بلند جاذبه‌هاست.',
			'برای یک سفر واقع‌بینانه، زمان مسیر، وضعیت هوا و ظرفیت اقامتگاه را پیش از حرکت بررسی کنید و برنامه را انعطاف‌پذیر نگه دارید.',
			'این راهنما یک چارچوب آماده برای چیدن سفر است و می‌توانید آن را با سلیقه، فصل و ترکیب هم‌سفران خود هماهنگ کنید.',
		);

		$closing = sprintf(
			'در مجموع، %1$s برای %2$s انتخاب مناسبی است؛ به‌ویژه وقتی %3$s سفر کنید، زمان کافی برای توقف‌های کوتاه داشته باشید و تجربه‌های محلی را جدی بگیرید.',
			$destination['place'],
			$destination['audience'],
			$destination['best_season']
		);

		$content  = $this->block_paragraph( $destination['lead'] );
		$content .= $this->block_paragraph( $openers[ absint( $variant ) % count( $openers ) ] );
		$content .= $this->block_heading( 'اطلاعات سفر در یک نگاه' );
		$content .= $this->block_table(
			array(
				array( 'مقصد', $destination['place'] ),
				array( 'استان یا منطقه', $destination['province'] ),
				array( 'نزدیک‌ترین مبدا پیشنهادی', $destination['nearby'] ),
				array( 'مدت مناسب', $destination['duration'] ),
				array( 'فصل پیشنهادی', $destination['best_season'] ),
				array( 'مناسب برای', $destination['audience'] ),
				array( 'سطح بودجه', $destination['budget'] ),
			)
		);
		$content .= $this->block_heading( sprintf( 'چرا %s ارزش دیدن دارد؟', $destination['place'] ) );
		$content .= $this->block_paragraph(
			sprintf(
				'مزیت اصلی این سفر، ترکیب فضای مقصد با یک برنامه قابل‌کنترل است. با توجه به اینکه این برنامه بیشتر برای %1$s مناسب است، می‌توانید شدت فعالیت‌ها را بر اساس سن، آمادگی و علاقه هم‌سفران تنظیم کنید. دسترسی معمول نیز به این شکل است: %2$s.',
				$destination['audience'],
				$destination['access']
			)
		);
		$content .= $this->block_heading( 'برنامه پیشنهادی سفر' );
		$content .= $this->block_list( $destination['highlights'] );
		$content .= $this->block_paragraph(
			sprintf(
				'پیشنهاد می‌شود برنامه را با «%1$s» آغاز کنید. پس از یک استراحت کوتاه، «%2$s» را در بخش میانی روز قرار دهید و برای ساعات پایانی، «%3$s» را نگه دارید. اگر یک شب در مقصد می‌مانید، روز بعد را به «%4$s» اختصاص دهید تا سفر بدون عجله کامل شود.',
				$destination['highlights'][0],
				$destination['highlights'][1],
				$destination['highlights'][2],
				$destination['highlights'][3]
			)
		);
		$content .= $this->block_heading( 'دسترسی، اقامت و مدیریت زمان' );
		$content .= $this->block_paragraph(
			sprintf(
				'برای رسیدن به مقصد، %1$s. انتخاب محل اقامت به سبک سفر بستگی دارد؛ گزینه پیشنهادی برای این برنامه «%2$s» است. بهتر است زمان تحویل اتاق، فاصله اقامتگاه تا مسیر اصلی و امکان پارک یا حمل‌ونقل محلی را پیش از رزرو بررسی کنید.',
				$destination['access'],
				$destination['stay']
			)
		);
		$content .= $this->block_paragraph(
			sprintf(
				'از نظر هزینه، این سفر در سطح «%1$s» قرار می‌گیرد. به‌جای اعلام عدد ثابت، بودجه را در چهار بخش رفت‌وآمد، اقامت، خوراک و ورودی یا فعالیت‌ها تقسیم کنید؛ چون قیمت‌ها در آخر هفته، تعطیلات و فصل‌های شلوغ تغییر می‌کنند.',
				$destination['budget']
			)
		);
		$content .= $this->block_heading( 'غذا و تجربه محلی' );
		$content .= $this->block_paragraph(
			sprintf(
				'برای کامل‌شدن تجربه سفر، بخشی از برنامه را به خوراک منطقه اختصاص دهید. پیشنهاد این مقصد شامل %1$s است. رستوران یا میزبان محلی را بر اساس تازگی مواد، نظافت، نظر مسافران و شفاف‌بودن قیمت انتخاب کنید و برای مجموعه‌های کوچک، پیش از مراجعه درباره ساعت فعالیت سؤال کنید.',
				$destination['food']
			)
		);
		$content .= $this->block_heading( 'نکات مهم پیش از حرکت' );
		$tips = $destination['tips'];
		$tips[] = 'ساعت بازدید، وضعیت مسیر و شرایط آب‌وهوا را نزدیک زمان سفر دوباره بررسی کنید.';
		$tips[] = 'بخشی از بودجه و زمان را برای تغییر برنامه، ترافیک یا توقف پیش‌بینی‌نشده کنار بگذارید.';
		$content .= $this->block_list( $tips );
		$content .= $this->block_heading( 'جمع‌بندی' );
		$content .= $this->block_paragraph( $closing );
		$content .= $this->block_quote( 'این نوشته به‌صورت خودکار برای آزمایش چیدمان و محتوای سایت ساخته شده است. زمان‌ها، هزینه‌ها، محدودیت‌های محلی و شرایط ایمنی را پیش از سفر از منابع معتبر و محلی بررسی کنید.' );

		return $content;
	}

	/**
	 * Return a random past post date in the site timezone.
	 *
	 * @param int $date_range Number of past days.
	 * @return string
	 */
	private function random_post_date( $date_range ) {
		$max_seconds = max( DAY_IN_SECONDS, absint( $date_range ) * DAY_IN_SECONDS );
		$timestamp   = time() - wp_rand( 0, $max_seconds );

		return wp_date( 'Y-m-d H:i:s', $timestamp, wp_timezone() );
	}

	/**
	 * Add a bundled image to the Media Library and set it as featured.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $destination Destination.
	 * @param string $title Post title.
	 * @param string $batch_id Batch ID.
	 * @return int|WP_Error
	 */
	private function attach_featured_image( $post_id, $destination, $title, $batch_id ) {
		if ( empty( $this->data['images'][ $destination['image'] ] ) ) {
			return new WP_Error( 'rtcg_image_map_missing', 'تصویر متناسب با مقصد در بسته افزونه پیدا نشد.' );
		}

		$image       = $this->data['images'][ $destination['image'] ];
		$source_path = RTCG_PLUGIN_DIR . 'assets/images/' . $image['file'];

		if ( ! is_readable( $source_path ) ) {
			return new WP_Error( 'rtcg_image_not_readable', 'فایل تصویر داخلی قابل خواندن نیست.' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$extension = strtolower( pathinfo( $image['file'], PATHINFO_EXTENSION ) );
		$filename  = sanitize_file_name( $destination['key'] . '-' . $post_id . '.' . $extension );
		$tmp_name  = wp_tempnam( $filename );

		if ( ! $tmp_name ) {
			return new WP_Error( 'rtcg_temp_file_failed', 'وردپرس نتوانست فایل موقت تصویر را ایجاد کند.' );
		}

		$attachment_id = 0;

		try {
			if ( ! @copy( $source_path, $tmp_name ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return new WP_Error( 'rtcg_copy_image_failed', 'کپی تصویر داخلی به پوشه موقت ناموفق بود.' );
			}

			$file_array = array(
				'name'     => $filename,
				'tmp_name' => $tmp_name,
			);

			$attachment_id = media_handle_sideload(
				$file_array,
				$post_id,
				$title,
				array(
					'post_title'   => wp_strip_all_tags( $destination['place'] . ' - تصویر گردشگری' ),
					'post_excerpt' => wp_strip_all_tags( 'تصویر نمونه برای نوشته گردشگری ' . $destination['place'] ),
					'post_content' => wp_strip_all_tags( 'تصویر CC0 از WordPress Photo Directory؛ منبع در متادیتای پیوست ثبت شده است.' ),
				)
			);

			if ( is_wp_error( $attachment_id ) ) {
				return $attachment_id;
			}

			update_post_meta( $attachment_id, '_wp_attachment_image_alt', wp_strip_all_tags( $destination['place'] . ' - تصویر شاخص گردشگری' ) );
			update_post_meta( $attachment_id, self::POST_META_FLAG, '1' );
			update_post_meta( $attachment_id, self::BATCH_META_KEY, $batch_id );
			update_post_meta( $attachment_id, '_rtcg_image_source', esc_url_raw( $image['source_url'] ) );
			update_post_meta( $attachment_id, '_rtcg_image_credit', sanitize_text_field( $image['credit'] ) );

			$thumbnail_result = set_post_thumbnail( $post_id, $attachment_id );

			if ( false === $thumbnail_result && (int) get_post_thumbnail_id( $post_id ) !== (int) $attachment_id ) {
				wp_delete_attachment( $attachment_id, true );
				return new WP_Error( 'rtcg_set_thumbnail_failed', 'ثبت تصویر به‌عنوان تصویر شاخص ناموفق بود.' );
			}

			return (int) $attachment_id;
		} catch ( Throwable $throwable ) {
			if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
				wp_delete_attachment( $attachment_id, true );
			}

			return new WP_Error( 'rtcg_image_exception', $throwable->getMessage() );
		} finally {
			if ( is_string( $tmp_name ) && file_exists( $tmp_name ) ) {
				@unlink( $tmp_name ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		}
	}

	/**
	 * Count generated objects.
	 *
	 * @param string $post_type Post type.
	 * @return int
	 */
	private function count_objects( $post_type ) {
		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => array_keys( get_post_stati() ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => self::POST_META_FLAG,
						'value' => '1',
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Delete empty terms that were originally created by the plugin.
	 *
	 * @return int
	 */
	private function delete_empty_generated_terms() {
		$terms = get_terms(
			array(
				'taxonomy'   => array( 'category', 'post_tag' ),
				'hide_empty' => false,
				'meta_query' => array(
					array(
						'key'   => self::TERM_META_FLAG,
						'value' => '1',
					),
				),
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return 0;
		}

		usort(
			$terms,
			static function ( $a, $b ) {
				return (int) $b->parent <=> (int) $a->parent;
			}
		);

		$deleted = 0;

		foreach ( $terms as $term ) {
			$fresh_term = get_term( $term->term_id, $term->taxonomy );

			if ( is_wp_error( $fresh_term ) || ! $fresh_term || (int) $fresh_term->count > 0 ) {
				continue;
			}

			if ( is_taxonomy_hierarchical( $fresh_term->taxonomy ) ) {
				$children = get_term_children( $fresh_term->term_id, $fresh_term->taxonomy );

				if ( is_wp_error( $children ) || ! empty( $children ) ) {
					continue;
				}
			}

			$result = wp_delete_term( $fresh_term->term_id, $fresh_term->taxonomy );
			if ( ! is_wp_error( $result ) && false !== $result ) {
				$deleted++;
			}
		}

		return $deleted;
	}

	/**
	 * Build a paragraph block.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private function block_paragraph( $text ) {
		return "<!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->\n\n";
	}

	/**
	 * Build a heading block.
	 *
	 * @param string $text Heading text.
	 * @return string
	 */
	private function block_heading( $text ) {
		return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $text ) . "</h2>\n<!-- /wp:heading -->\n\n";
	}

	/**
	 * Build a list block.
	 *
	 * @param array $items List items.
	 * @return string
	 */
	private function block_list( $items ) {
		$html = "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n";

		foreach ( $items as $item ) {
			$html .= '<li>' . esc_html( $item ) . "</li>\n";
		}

		$html .= "</ul>\n<!-- /wp:list -->\n\n";

		return $html;
	}

	/**
	 * Build a simple two-column table block.
	 *
	 * @param array $rows Table rows.
	 * @return string
	 */
	private function block_table( $rows ) {
		$html = "<!-- wp:table -->\n<figure class=\"wp-block-table\"><table><tbody>\n";

		foreach ( $rows as $row ) {
			$html .= '<tr><th>' . esc_html( $row[0] ) . '</th><td>' . esc_html( $row[1] ) . "</td></tr>\n";
		}

		$html .= "</tbody></table></figure>\n<!-- /wp:table -->\n\n";

		return $html;
	}

	/**
	 * Build a quote block.
	 *
	 * @param string $text Quote text.
	 * @return string
	 */
	private function block_quote( $text ) {
		return "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>" . esc_html( $text ) . "</p></blockquote>\n<!-- /wp:quote -->\n";
	}
}
