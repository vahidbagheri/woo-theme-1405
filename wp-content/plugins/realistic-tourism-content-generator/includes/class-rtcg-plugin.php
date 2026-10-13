<?php
/**
 * Admin controller for the plugin.
 *
 * @package Realistic_Tourism_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RTCG_Plugin {

	const PAGE_SLUG      = 'rtcg-tourism-generator';
	const NOTICE_PREFIX  = 'rtcg_admin_notice_';
	const LOCK_TRANSIENT = 'rtcg_generation_lock';

	/**
	 * Singleton instance.
	 *
	 * @var RTCG_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Generator service.
	 *
	 * @var RTCG_Generator
	 */
	private $generator;

	/**
	 * Admin page hook.
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Get singleton instance.
	 *
	 * @return RTCG_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Activation callback.
	 *
	 * @return void
	 */
	public static function activate() {
		update_option( 'rtcg_version', RTCG_VERSION, false );
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->generator = new RTCG_Generator();

		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_rtcg_generate_posts', array( $this, 'handle_generate' ) );
		add_action( 'admin_post_rtcg_delete_generated', array( $this, 'handle_delete' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( RTCG_PLUGIN_FILE ), array( $this, 'add_plugin_action_link' ) );
	}

	/**
	 * Register the Tools submenu.
	 *
	 * @return void
	 */
	public function register_admin_page() {
		$this->page_hook = add_management_page(
			'داده‌ساز گردشگری',
			'داده‌ساز گردشگری',
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue page-specific CSS.
	 *
	 * @param string $hook_suffix Current admin hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style(
			'rtcg-admin',
			RTCG_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			RTCG_VERSION
		);
	}

	/**
	 * Add a settings shortcut to the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_plugin_action_link( $links ) {
		$url = admin_url( 'tools.php?page=' . self::PAGE_SLUG );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">ساخت نوشته تستی</a>' );

		return $links;
	}

	/**
	 * Handle generation form submission.
	 *
	 * @return void
	 */
	public function handle_generate() {
		$this->assert_access();
		check_admin_referer( 'rtcg_generate_posts', 'rtcg_nonce' );

		if ( get_transient( $this->get_lock_key() ) ) {
			$this->set_notice( 'warning', 'یک عملیات دیگر در حال اجراست. چند دقیقه بعد دوباره تلاش کنید.' );
			$this->redirect_to_page();
		}

		set_transient( $this->get_lock_key(), (string) get_current_user_id(), 5 * MINUTE_IN_SECONDS );
		wp_raise_memory_limit( 'admin' );

		$count       = isset( $_POST['count'] ) ? max( 1, min( 20, absint( wp_unslash( $_POST['count'] ) ) ) ) : 8;
		$date_range  = isset( $_POST['date_range'] ) ? max( 1, min( 365, absint( wp_unslash( $_POST['date_range'] ) ) ) ) : 90;
		$post_status = isset( $_POST['post_status'] ) ? sanitize_key( wp_unslash( $_POST['post_status'] ) ) : 'draft';
		$post_status = in_array( $post_status, array( 'draft', 'publish' ), true ) ? $post_status : 'draft';
		$create_tags = isset( $_POST['create_tags'] ) && '1' === (string) wp_unslash( $_POST['create_tags'] );

		$settings = array(
			'count'       => $count,
			'date_range'  => $date_range,
			'post_status' => $post_status,
			'create_tags' => $create_tags,
		);

		update_user_meta( get_current_user_id(), '_rtcg_last_settings', $settings );

		try {
			$result = $this->generator->generate(
				array(
					'count'       => $count,
					'date_range'  => $date_range,
					'post_status' => $post_status,
					'create_tags' => $create_tags,
					'author_id'   => get_current_user_id(),
				)
			);

			if ( is_wp_error( $result ) ) {
				$this->set_notice( 'error', 'ساخت نوشته‌ها ناموفق بود: ' . $result->get_error_message() );
			} else {
				$message = sprintf(
					'%1$s نوشته گردشگری با تصویر شاخص ساخته شد. شناسه اجرای تولید: %2$s',
					number_format_i18n( $result['created'] ),
					$result['batch_id']
				);

				if ( ! empty( $result['errors'] ) ) {
					$message .= sprintf( ' تعداد %s مورد نیز به‌دلیل خطای میزبان یا رسانه ساخته نشد.', number_format_i18n( count( $result['errors'] ) ) );
					$this->set_notice( 'warning', $message, array_slice( $result['errors'], 0, 5 ) );
				} else {
					$this->set_notice( 'success', $message );
				}
			}
		} catch ( Throwable $throwable ) {
			$this->set_notice( 'error', 'یک خطای پیش‌بینی‌نشده رخ داد: ' . $throwable->getMessage() );
		} finally {
			delete_transient( $this->get_lock_key() );
		}

		$this->redirect_to_page();
	}

	/**
	 * Handle deletion of generated content.
	 *
	 * @return void
	 */
	public function handle_delete() {
		$this->assert_access();
		check_admin_referer( 'rtcg_delete_generated', 'rtcg_nonce' );

		if ( get_transient( $this->get_lock_key() ) ) {
			$this->set_notice( 'warning', 'یک عملیات دیگر در حال اجراست. چند دقیقه بعد دوباره تلاش کنید.' );
			$this->redirect_to_page();
		}

		set_transient( $this->get_lock_key(), (string) get_current_user_id(), 5 * MINUTE_IN_SECONDS );
		wp_raise_memory_limit( 'admin' );

		try {
			$result = $this->generator->delete_generated_content();
			$message = sprintf(
				'%1$s نوشته، %2$s تصویر و %3$s دسته یا برچسب خالیِ ساخته‌شده توسط افزونه حذف شد.',
				number_format_i18n( $result['posts'] ),
				number_format_i18n( $result['attachments'] ),
				number_format_i18n( $result['terms'] )
			);
			$this->set_notice( 'success', $message );
		} catch ( Throwable $throwable ) {
			$this->set_notice( 'error', 'حذف محتوای آزمایشی ناموفق بود: ' . $throwable->getMessage() );
		} finally {
			delete_transient( $this->get_lock_key() );
		}

		$this->redirect_to_page();
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'شما اجازه دسترسی به این صفحه را ندارید.', 'realistic-tourism-content-generator' ) );
		}

		$stats    = $this->generator->get_stats();
		$recent   = $this->generator->get_recent_posts( 5 );
		$settings = get_user_meta( get_current_user_id(), '_rtcg_last_settings', true );
		$settings = wp_parse_args(
			is_array( $settings ) ? $settings : array(),
			array(
				'count'       => 8,
				'date_range'  => 90,
				'post_status' => 'draft',
				'create_tags' => true,
			)
		);
		?>
		<div class="wrap rtcg-wrap" dir="rtl">
			<h1>داده‌ساز گردشگری واقع‌نما</h1>
			<p class="rtcg-lead">برای post type پیش‌فرض وردپرس، نوشته‌های فارسی گردشگری با عنوان، خلاصه، متن کامل، دسته‌بندی، برچسب و تصویر شاخص محلی بسازید.</p>

			<?php $this->render_notice(); ?>

			<div class="rtcg-stats" aria-label="آمار افزونه">
				<?php $this->render_stat_card( 'نوشته‌های ساخته‌شده', $stats['posts'] ); ?>
				<?php $this->render_stat_card( 'تصاویر رسانه‌ای', $stats['attachments'] ); ?>
				<?php $this->render_stat_card( 'مقصد آماده', $stats['destinations'] ); ?>
				<?php $this->render_stat_card( 'دسته فرزند', $stats['categories'] ); ?>
			</div>

			<div class="rtcg-grid">
				<section class="rtcg-panel">
					<h2>ساخت نوشته‌های جدید</h2>
					<p>هر اجرا حداکثر ۲۰ نوشته می‌سازد تا پردازش تصاویر روی هاست‌های معمولی پایدار بماند. برای تعداد بیشتر، عملیات را دوباره اجرا کنید.</p>

					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
						<input type="hidden" name="action" value="rtcg_generate_posts">
						<?php wp_nonce_field( 'rtcg_generate_posts', 'rtcg_nonce' ); ?>

						<div class="rtcg-field">
							<label for="rtcg-count">تعداد نوشته</label>
							<input id="rtcg-count" name="count" type="number" min="1" max="20" value="<?php echo esc_attr( $settings['count'] ); ?>" required>
							<p class="description">پیشنهاد برای اجرای اول: ۵ تا ۱۰ نوشته.</p>
						</div>

						<div class="rtcg-field">
							<label for="rtcg-status">وضعیت انتشار</label>
							<select id="rtcg-status" name="post_status">
								<option value="draft" <?php selected( $settings['post_status'], 'draft' ); ?>>پیش‌نویس</option>
								<option value="publish" <?php selected( $settings['post_status'], 'publish' ); ?>>انتشار مستقیم</option>
							</select>
							<p class="description">برای بررسی اولیه، حالت پیش‌نویس امن‌تر است.</p>
						</div>

						<div class="rtcg-field">
							<label for="rtcg-date-range">پراکندگی تاریخ نوشته‌ها</label>
							<select id="rtcg-date-range" name="date_range">
								<option value="7" <?php selected( (int) $settings['date_range'], 7 ); ?>>۷ روز گذشته</option>
								<option value="30" <?php selected( (int) $settings['date_range'], 30 ); ?>>۳۰ روز گذشته</option>
								<option value="90" <?php selected( (int) $settings['date_range'], 90 ); ?>>۹۰ روز گذشته</option>
								<option value="180" <?php selected( (int) $settings['date_range'], 180 ); ?>>۱۸۰ روز گذشته</option>
								<option value="365" <?php selected( (int) $settings['date_range'], 365 ); ?>>یک سال گذشته</option>
							</select>
						</div>

						<div class="rtcg-field rtcg-checkbox-field">
							<label for="rtcg-create-tags">
								<input id="rtcg-create-tags" name="create_tags" type="checkbox" value="1" <?php checked( ! empty( $settings['create_tags'] ) ); ?>>
								برچسب‌های مقصد، استان و نوع سفر هم ساخته شوند
							</label>
						</div>

						<?php submit_button( 'ساخت نوشته‌های گردشگری', 'primary large', 'submit', false ); ?>
					</form>
				</section>

				<section class="rtcg-panel">
					<h2>ویژگی داده‌های خروجی</h2>
					<ul class="rtcg-feature-list">
						<li>استفاده از post type داخلی <code>post</code> و دسته‌بندی استاندارد وردپرس</li>
						<li>متن فارسی چندبخشی سازگار با ویرایشگر بلوکی</li>
						<li>تصاویر واقعی CC0 که داخل خود افزونه قرار دارند و به اینترنت وابسته نیستند</li>
						<li>تاریخ‌های پراکنده، خلاصه نوشته، برچسب و متادیتای سفر</li>
						<li>علامت‌گذاری داخلی تمام محتوا برای حذف امن و یک‌مرحله‌ای</li>
					</ul>

					<div class="rtcg-danger-zone">
						<h3>پاک‌سازی داده‌های آزمایشی</h3>
						<p>فقط نوشته‌ها، تصاویر و اصطلاحاتی حذف می‌شوند که این افزونه آن‌ها را علامت‌گذاری کرده است. دسته یا برچسبی که روی محتوای دیگر استفاده شده باشد نگه داشته می‌شود.</p>
						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="rtcg_delete_generated">
							<?php wp_nonce_field( 'rtcg_delete_generated', 'rtcg_nonce' ); ?>
							<?php
							submit_button(
								'حذف همه داده‌های ساخته‌شده',
								'delete',
								'submit',
								false,
								array(
									'onclick' => "return confirm('همه نوشته‌ها و تصاویر ساخته‌شده توسط افزونه حذف شوند؟ این عملیات قابل بازگشت نیست.');",
								)
							);
							?>
						</form>
					</div>
				</section>
			</div>

			<section class="rtcg-panel rtcg-recent-panel">
				<h2>آخرین نوشته‌های ساخته‌شده</h2>
				<?php if ( empty( $recent ) ) : ?>
					<p>هنوز نوشته‌ای توسط افزونه ساخته نشده است.</p>
				<?php else : ?>
					<div class="rtcg-table-wrap">
						<table class="widefat striped">
							<thead>
								<tr>
									<th>عنوان</th>
									<th>مقصد</th>
									<th>وضعیت</th>
									<th>تاریخ</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent as $post ) : ?>
									<?php $status_object = get_post_status_object( $post->post_status ); ?>
									<tr>
										<td><a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></td>
										<td><?php echo esc_html( get_post_meta( $post->ID, '_rtcg_destination', true ) ); ?></td>
										<td><?php echo esc_html( $status_object ? $status_object->label : $post->post_status ); ?></td>
										<td><?php echo esc_html( get_the_date( 'Y/m/d', $post ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</section>
		</div>
		<?php
	}

	/**
	 * Render one stats card.
	 *
	 * @param string $label Card label.
	 * @param int    $value Numeric value.
	 * @return void
	 */
	private function render_stat_card( $label, $value ) {
		?>
		<div class="rtcg-stat-card">
			<strong><?php echo esc_html( number_format_i18n( (int) $value ) ); ?></strong>
			<span><?php echo esc_html( $label ); ?></span>
		</div>
		<?php
	}

	/**
	 * Render and clear the current user's notice.
	 *
	 * @return void
	 */
	private function render_notice() {
		$key    = $this->get_notice_key();
		$notice = get_transient( $key );

		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}

		delete_transient( $key );
		$type = in_array( $notice['type'], array( 'success', 'warning', 'error', 'info' ), true ) ? $notice['type'] : 'info';
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible rtcg-notice">
			<p><?php echo esc_html( $notice['message'] ); ?></p>
			<?php if ( ! empty( $notice['details'] ) && is_array( $notice['details'] ) ) : ?>
				<ul>
					<?php foreach ( $notice['details'] as $detail ) : ?>
						<li><?php echo esc_html( $detail ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Save a one-time admin notice.
	 *
	 * @param string $type Notice type.
	 * @param string $message Message.
	 * @param array  $details Optional details.
	 * @return void
	 */
	private function set_notice( $type, $message, $details = array() ) {
		set_transient(
			$this->get_notice_key(),
			array(
				'type'    => sanitize_key( $type ),
				'message' => wp_strip_all_tags( $message ),
				'details' => array_map( 'wp_strip_all_tags', is_array( $details ) ? $details : array() ),
			),
			2 * MINUTE_IN_SECONDS
		);
	}

	/**
	 * Verify current user access.
	 *
	 * @return void
	 */
	private function assert_access() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'شما اجازه انجام این عملیات را ندارید.', 'realistic-tourism-content-generator' ) );
		}
	}

	/**
	 * Redirect back to the plugin page.
	 *
	 * @return void
	 */
	private function redirect_to_page() {
		wp_safe_redirect( admin_url( 'tools.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Build the per-user notice key.
	 *
	 * @return string
	 */
	private function get_notice_key() {
		return self::NOTICE_PREFIX . get_current_blog_id() . '_' . get_current_user_id();
	}

	/**
	 * Build the per-site lock key.
	 *
	 * @return string
	 */
	private function get_lock_key() {
		return self::LOCK_TRANSIENT . '_' . get_current_blog_id();
	}
}
