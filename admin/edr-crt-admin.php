<?php

/**
 * Initialize the certificates admin.
 */
class Edr_Crt_Admin {
	/**
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_url
	 */
	public function __construct( $plugin_url ) {
		$this->plugin_url = $plugin_url;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_certificate_tpl_mb' ), 10 );
		add_action( 'save_post', array( $this, 'save_select_certificate_tpl_mb' ), 10 );
		add_action( 'manage_edr_certificate_posts_columns', array( $this, 'output_certificate_columns' ) );
		add_action( 'manage_edr_certificate_posts_custom_column', array( $this, 'output_certificate_column_values' ), 10, 2 );
	}

	/**
	 * Check if the current user can save a meta box.
	 *
	 * @param string $post_type
	 * @param int $post_id
	 * @param string $nonce_key
	 * @return boolean
	 */
	public function can_save_mb_data( $post_type, $post_id, $nonce_key ) {
		if ( ! isset( $_POST[ $nonce_key . '_nonce' ] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $_POST[ $nonce_key . '_nonce' ], $nonce_key ) ) {
			return false;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( $post_type != get_post_type( $post_id ) || ! current_user_can( 'edit_' . $post_type, $post_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( $screen && 'edr_certificate_tpl' == $screen->id ) {
			// Scripts for the "edit certificate template post" page.
			wp_enqueue_style( 'jquery-ui', $this->plugin_url . 'admin/css/jquery-ui/jquery-ui.min.css', array(), '1.11.4' );
			wp_enqueue_style( 'edr-certificate-tpl', $this->plugin_url . 'admin/css/certificate-tpl.css', array(), '1.0' );
			wp_enqueue_script(
				'edr-certificate-tpl',
				$this->plugin_url . 'admin/js/certificate-tpl.js',
				array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-resizable' ),
				'1.0',
				true
			);
		}
	}

	/**
	 * Add meta boxes.
	 */
	public function add_meta_boxes() {
		// Certificate template meta box.
		add_meta_box(
			'edr_certificate_tpl',
			__( 'Certificate Template', 'edr-crt' ),
			array( $this, 'certificate_tpl_mb' ),
			'edr_certificate_tpl'
		);

		// Certificate template select meta box.
		add_meta_box(
			'edr_select_certificate_tpl',
			__( 'Certificate Template', 'edr-crt' ),
			array( $this, 'select_certificate_tpl_mb' ),
			'ib_educator_course'
		);
	}

	/**
	 * Display the certificate template meta box.
	 *
	 * @param WP_Post $post
	 */
	public function certificate_tpl_mb( $post ) {
		wp_nonce_field( 'edr_certificate_tpl', 'edr_certificate_tpl_nonce' );

		$blocks = get_post_meta( $post->ID, '_edr_crt_blocks', true );

		$orientation = get_post_meta( $post->ID, '_edr_crt_orientation', true );
		$orientations = array(
			'P' => __( 'Portrait', 'edr-crt' ),
			'L' => __( 'Landscape', 'edr-crt' ),
		);

		$page_size = get_post_meta( $post->ID, '_edr_crt_size', true );
		$page_sizes = Edr_Manager::get( 'edr_crt' )->get_page_sizes();
		?>
		<div id="edr-crt-template">
			<div class="edr-form-blocks">
				<div class="block">
					<label><?php _e( 'Page Size', 'edr-crt' ); ?></label>
					<select class="change-page-size" name="_edr_crt_size">
						<?php
							foreach ( $page_sizes as $key => $size ) {
								$selected = ( $key == $page_size ) ? ' selected="selected"' : '';
								echo '<option value="' . esc_attr( $key ) . '" data-width="' . (float) $size['width'] . '"'
									. ' data-height="' . (float) $size['height'] . '"' . $selected . '>'
									. esc_html( $size['label'] ) . '</option>';
							}
						?>
					</select>
				</div>
				<div class="block">
					<label><?php _e( 'Orientation', 'edr-crt' ); ?></label>
					<select class="change-orientation" name="_edr_crt_orientation">
						<?php
							foreach ( $orientations as $key => $title ) {
								$selected = ( $key == $orientation ) ? ' selected="selected"' : '';
								echo '<option value="' . $key . '"' . $selected . '>' . $title . '</option>';
							}
						?>
					</select>
				</div>
			</div>
			<div class="image">
				<div>
					<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail();
						}
					?>
				</div>
			</div>
			<ul class="blocks"></ul>
			<p class="buttons">
				<button class="add-block button" data-block-type="text"><?php _e( 'Add Block', 'edr-crt' ); ?></button>
			</p>
		</div>
		<script type="text/template" id="edr-text-block-tpl">
			<input type="hidden" class="field-x" name="block_x[]" value="<%- x %>">
			<input type="hidden" class="field-y" name="block_y[]" value="<%- y %>">
			<input type="hidden" class="field-width" name="block_width[]" value="<%- width %>">
			<input type="hidden" class="field-height" name="block_height[]" value="<%- height %>">
			<div class="header">
				<span class="text"><%- name %></span>
				<span class="trigger"></span>
			</div>
			<div class="body">
				<div class="name">
					<label><?php _e( 'Name', 'edr-crt' ); ?></label>
					<input type="text" class="field-name" name="block_name[]" value="<%- name %>">
				</div>
				<div class="content">
					<label><?php _e( 'Content', 'edr-crt' ); ?></label>
					<textarea class="field-content" name="block_content[]"><%- content %></textarea>
				</div>
				<div class="font-size">
					<label><?php _e( 'Font Size', 'edr-crt' ); ?></label>
					<input type="number" class="field-font-size" name="block_font_size[]" value="<%- font_size %>">
				</div>
				<div class="align">
					<label><?php _e( 'Text Alignment', 'edr-crt' ); ?></label>

					<select name="block_align[]" class="field-align">
						<option value="L"><?php _e( 'Left', 'edr-crt' ); ?></option>
						<option value="C"><?php _e( 'Center', 'edr-crt' ); ?></option>
						<option value="R"><?php _e( 'Right', 'edr-crt' ); ?></option>
						<option value="J"><?php _e( 'Justify', 'edr-crt' ); ?></option>
					</select>
				</div>
				<div class="edr-buttons-group">
					<a class="remove" href="#"><?php _e( 'Remove', 'edr-crt' ); ?></a>
				</div>
			</div>
		</script>
		<script>
			var edrCertBlocks = <?php echo json_encode( $blocks ); ?>;
		</script>
		<?php
	}

	/**
	 * Display the certificate template select meta box.
	 *
	 * @param WP_Post $post
	 */
	public function select_certificate_tpl_mb( $post ) {
		wp_nonce_field( 'edr_select_certificate_tpl', 'edr_select_certificate_tpl_nonce' );

		$template_id = get_post_meta( $post->ID, '_edr_crt_template', true );
		$templates = get_posts( array(
			'post_type'   => 'edr_certificate_tpl',
			'post_status' => 'publish',
		) );
		?>
		<?php if ( ! empty( $templates ) ) : ?>
			<p>
				<select name="_edr_crt_template">
					<option value=""><?php _e( 'None', 'edr-crt' ); ?></option>
					<?php
						foreach ( $templates as $template ) {
							$selected = ( $template_id == $template->ID ) ? ' selected="selected"' : '';

							echo '<option value="' . intval( $template->ID ) . '"' . $selected . '>'
								. esc_html( $template->post_title ) . '</option>';
						}
					?>
				</select>
			</p>
		<?php else : ?>
			<p><?php _e( 'No certificate templates found.', 'edr-crt' ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save the certificate template meta box.
	 *
	 * @param int $post_id
	 */
	public function save_certificate_tpl_mb( $post_id ) {
		if ( ! $this->can_save_mb_data( 'edr_certificate_tpl', $post_id, 'edr_certificate_tpl' ) ) {
			return;
		}

		// Save blocks.
		if ( isset( $_POST['block_name'] ) ) {
			$blocks = array();
			$valid_align = array( 'L', 'C', 'R', 'J' );

			foreach ( $_POST['block_name'] as $key => $name ) {
				$blocks[] = array(
					'x'         => intval( $_POST['block_x'][ $key ] ),
					'y'         => intval( $_POST['block_y'][ $key ] ),
					'width'     => intval( $_POST['block_width'][ $key ] ),
					'height'    => intval( $_POST['block_height'][ $key ] ),
					'name'      => sanitize_text_field( $name ),
					'content'   => esc_html( $_POST['block_content'][ $key ] ),
					'font_size' => (float) $_POST['block_font_size'][ $key ],
					'align'     => in_array( $_POST['block_align'][ $key ], $valid_align ) ? $_POST['block_align'][ $key ] : 'L',
				);
			}

			update_post_meta( $post_id, '_edr_crt_blocks', $blocks );
		}

		// Save page size.
		$page_size = 'a4';
		$page_sizes = Edr_Manager::get( 'edr_crt' )->get_page_sizes();

		if ( isset( $_POST['_edr_crt_size'] ) && array_key_exists( $_POST['_edr_crt_size'], $page_sizes ) ) {
			$page_size = $_POST['_edr_crt_size'];
		}

		update_post_meta( $post_id, '_edr_crt_size', $page_size );

		// Save orientation.
		$orientation = 'P';

		if ( isset( $_POST['_edr_crt_orientation'] ) && in_array( $_POST['_edr_crt_orientation'], array( 'P', 'L' ) ) ) {
			$orientation = $_POST['_edr_crt_orientation'];
		}

		update_post_meta( $post_id, '_edr_crt_orientation', $orientation );
	}

	/**
	 * Save the certificate template select meta box.
	 *
	 * @param int $post_id
	 */
	public function save_select_certificate_tpl_mb( $post_id ) {
		if ( ! $this->can_save_mb_data( 'ib_educator_course', $post_id, 'edr_select_certificate_tpl' ) ) {
			return;
		}

		// Save the certificate template post ID.
		if ( isset( $_POST['_edr_crt_template'] ) ) {
			update_post_meta( $post_id, '_edr_crt_template', intval( $_POST['_edr_crt_template'] ) );
		}
	}

	/**
	 * Add custom columns to the certificates list page.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function output_certificate_columns( $columns ) {
		unset( $columns['date'] );

		$columns['student'] = __( 'Student', 'edr-crt' );
		$columns['course'] = __( 'Course', 'edr-crt' );
		$columns['date_created'] = __( 'Date Created', 'edr-crt' );

		return $columns;
	}

	/**
	 * Output the data for the custom columns on the certificates list page.
	 *
	 * @param string $column
	 * @param int $post_id
	 * @return array
	 */
	public function output_certificate_column_values( $column, $post_id ) {
		switch ( $column ) {
			case 'student':
				$student_id = get_post_meta( $post_id, 'student_id', true );

				if ( $student_id && ( $student = get_user_by( 'id', $student_id ) ) ) {
					echo esc_html( Edr_Manager::get( 'edr_crt' )->get_student_name( $student ) );
				}
				break;

			case 'course':
				$course_id = get_post_meta( $post_id, 'course_id', true );

				if ( $course_id && ( $course = get_post( $course_id ) ) ) {
					echo esc_html( $course->post_title );
				}
				break;

			case 'date_created':
				the_date();
				break;
		}
	}
}
