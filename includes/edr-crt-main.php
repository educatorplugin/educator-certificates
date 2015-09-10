<?php

/**
 * The main plugin initialization class.
 */
class Edr_Crt_Main {
	/**
	 * @var string
	 */
	private $plugin_url;

	/**
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Constructor.
	 */
	public function __construct( $file ) {
		$this->plugin_url = plugin_dir_url( $file );
		$this->plugin_dir = plugin_dir_path( $file );

		register_activation_hook( $file, array( $this, 'plugin_activation' ) );
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'template_redirect', array( $this, 'view_certificate' ) );
		add_action( 'edr_entry_status_change', array( $this, 'create_certificate' ), 10, 2 );
		add_filter( 'edr_student_courses_headings', array( $this, 'my_courses_heading' ), 10, 2 );
		add_filter( 'edr_student_courses_values', array( $this, 'my_courses_certificate_link' ), 10, 3 );

		if ( is_admin() ) {
			require $this->plugin_dir . 'admin/edr-crt-admin.php';

			new Edr_Crt_Admin( $this->plugin_url );
		}
	}

	/**
	 * Plugin activation listener.
	 */
	public function plugin_activation() {
		global $wp_roles;

		// Flush rewrite rules.
		$this->register_post_types();
		flush_rewrite_rules();

		// Add capabilities.
		if ( isset( $wp_roles ) && is_object( $wp_roles ) ) {
			$post_type = 'edr_certificate_tpl';
			$admin_caps = array(
				"edit_{$post_type}",
				"read_{$post_type}",
				"delete_{$post_type}",
				"edit_{$post_type}s",
				"edit_others_{$post_type}s",
				"publish_{$post_type}s",
				"read_private_{$post_type}s",
				"delete_{$post_type}s",
				"delete_private_{$post_type}s",
				"delete_published_{$post_type}s",
				"delete_others_{$post_type}s",
				"edit_private_{$post_type}s",
				"edit_published_{$post_type}s",
			);

			foreach ( $admin_caps as $cap ) {
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}

	/**
	 * Get the certificates service.
	 * This method is used by Edr_Manager only.
	 *
	 * @return Edr_Crt
	 */
	public function get_service() {
		require $this->plugin_dir . 'includes/edr-crt.php';

		return new Edr_Crt();
	}

	/**
	 * Various processing on plugins loaded.
	 */
	public function on_plugins_loaded() {
		// Load plugin text domain.
		load_plugin_textdomain( 'edr-crt', false, 'educator-certificates/languages' );

		// Register the Edr_Crt service with Educator.
		Edr_Manager::add( 'edr_crt', array( $this, 'get_service' ) );
	}

	/**
	 * Register post types.
	 */
	public function register_post_types() {
		// Certificate templates.
		register_post_type(
			'edr_certificate_tpl',
			apply_filters( 'edr_cpt_certificate_tpl', array(
				'label'              => __( 'Certificate Templates', 'edr-crt' ),
				'labels'             => array(
					'name'          => __( 'Certificate Templates', 'edr-crt' ),
					'singular_name' => __( 'Certificate Template', 'edr-crt' ),
				),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => 'ib_educator_admin',
				'show_in_admin_bar'  => false,
				'capability_type'    => 'edr_certificate_tpl',
				'map_meta_cap'       => true,
				'hierarchical'       => false,
				'supports'           => array( 'title', 'thumbnail' ),
				'has_archive'        => false,
				'rewrite'            => array( 'slug' => 'certificate-template' ),
				'query_var'          => 'certificate-template',
			) )
		);

		// Certificates.
		register_post_type(
			'edr_certificate',
			apply_filters( 'edr_cpt_certificate', array(
				'label'              => __( 'Certificates', 'edr-crt' ),
				'labels'             => array(
					'name'          => __( 'Certificates', 'edr-crt' ),
					'singular_name' => __( 'Certificate', 'edr-crt' ),
				),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => 'ib_educator_admin',
				'show_in_admin_bar'  => false,
				'capability_type'    => 'edr_certificate',
				'map_meta_cap'       => true,
				'hierarchical'       => false,
				'supports'           => array( 'title', 'custom-fields' ),
				'has_archive'        => false,
				'rewrite'            => array( 'slug' => 'certificate' ),
				'query_var'          => 'certificate',
			) )
		);
	}

	/**
	 * View certificates.
	 * Listens to the "template_redirect" action hook.
	 */
	public function view_certificate() {
		global $post;

		$post_type = get_post_type();

		if ( 'edr_certificate' == $post_type ) {
			$edr_crt = Edr_Manager::get( 'edr_crt' );

			// Check permission.
			if ( ! $edr_crt->can_view_certificate( $post ) ) {
				wp_die( __( 'You are not allowed to view this page.', 'edr-crt' ) );
			}

			$custom = get_post_custom( $post->ID );

			// Get entry.
			$entry = IB_Educator_Entry::get_instance( $custom['entry_id'][0] );

			if ( $entry ) {
				// Get course.
				$course = get_post( $entry->course_id );

				if ( $course ) {
					// Get template ID.
					$crt_template = get_post_meta( $course->ID, '_edr_crt_template', true );

					// Get student.
					$student = get_user_by( 'id', $entry->user_id );

					if ( $crt_template && $student ) {
						// Get page size.
						$page_size = get_post_meta( $crt_template, '_edr_crt_size', true );
						$page_sizes = $edr_crt->get_page_sizes();

						if ( array_key_exists( $page_size, $page_sizes ) ) {
							$page_size = array( $page_sizes[ $page_size ]['width'], $page_sizes[ $page_size ]['height'] );
						} else {
							$page_size = 'a4';
						}

						$data = array(
							'orientation'  => get_post_meta( $crt_template, '_edr_crt_orientation', true ),
							'image'        => get_attached_file( get_post_thumbnail_id( $crt_template ) ),
							'student_name' => $edr_crt->get_student_name( $student ),
							'course_title' => $course->post_title,
							'date'         => date_i18n( get_option( 'date_format' ), time() ),
							'blocks'       => get_post_meta( $crt_template, '_edr_crt_blocks', true ),
							'file_name'    => 'certificate-' . sanitize_title( $post->post_name ) . '.pdf',
							'page_size'    => $page_size,
						);

						$edr_crt->output_pdf( $data );
					}
				}
			}

			exit();
		} elseif ( 'edr_certificate_tpl' == $post_type ) {
			$user = wp_get_current_user();

			if ( ! $user->ID || ! current_user_can( 'edit_edr_certificate_tpl', $post->ID ) ) {
				wp_die( __( 'You are not allowed to view this page.', 'edr-crt' ) );
			}

			$post_id = get_the_ID();
			$edr_crt = Edr_Manager::get( 'edr_crt' );

			// Get page size.
			$page_size = get_post_meta( $post_id, '_edr_crt_size', true );
			$page_sizes = $edr_crt->get_page_sizes();

			if ( array_key_exists( $page_size, $page_sizes ) ) {
				$page_size = array( $page_sizes[ $page_size ]['width'], $page_sizes[ $page_size ]['height'] );
			} else {
				$page_size = 'a4';
			}

			$data = array(
				'orientation'  => get_post_meta( $post_id, '_edr_crt_orientation', true ),
				'image'        => get_attached_file( get_post_thumbnail_id( $post_id ) ),
				'course_title' => __( 'Course Title', 'edr-crt' ),
				'student_name' => $edr_crt->get_student_name( $user ),
				'date'         => date_i18n( get_option( 'date_format' ), time() ),
				'blocks'       => get_post_meta( $post_id, '_edr_crt_blocks', true ),
				'file_name'    => 'preview-certificate-' . $post_id . '.pdf',
				'page_size'    => $page_size,
			);

			$edr_crt->output_pdf( $data );

			exit();
		}
	}

	/**
	 * Create a certificate.
	 *
	 * @param IB_Educator_Entry $entry
	 * @param string $prev_status
	 */
	public function create_certificate( $entry, $prev_status ) {
		// Is the entry completed?
		if ( 'complete' != $entry->entry_status ) {
			return;
		}

		// Does the course have a certificate template set?
		if ( ! get_post_meta( $entry->course_id, '_edr_crt_template', true ) ) {
			return;
		}

		// Can the certificate be created?
		if ( ! apply_filters( 'edr_crt_can_create', true, $entry ) ) {
			return;
		}

		$edr_crt = Edr_Manager::get( 'edr_crt' );
		$certificate = $edr_crt->get_certificate_by_entry_id( $entry->ID );

		if ( is_null( $certificate ) ) {
			$edr_crt->create_certificate( $entry );
		}
	}

	/**
	 * Add the "Actions" column to the completed courses table
	 * on the student's courses page.
	 *
	 * @param array $headings
	 * @param string $status
	 * @return array
	 */
	public function my_courses_heading( $headings, $status ) {
		if ( 'complete' == $status && ! isset( $headings['actions'] ) ) {
			$headings['actions'] = '<th>' . __( 'Actions', 'edr-crt' ) . '</th>';
		}

		return $headings;
	}

	/**
	 * Display the "view certificate" link in the completed courses table.
	 *
	 * @param array $values
	 * @param string $status
	 * @param IB_Educator_Entry $entry
	 * @return array
	 */
	public function my_courses_certificate_link( $values, $status, $entry ) {
		if ( 'complete' == $status ) {
			$certificate_url = Edr_Manager::get( 'edr_crt' )->get_certificate_url( $entry->ID );

			if ( ! empty( $certificate_url ) ) {
				if ( ! isset( $values['actions'] ) ) {
					$values['actions'] = '<td><a href="' . esc_url( $certificate_url ) . '" target="_blank">' . __( 'View Certificate', 'edr-crt' ) . '</a></td>';
				}
			} else {
				if ( ! isset( $values['actions'] ) ) {
					$values['actions'] = '<td></td>';
				}
			}
		}

		return $values;
	}
}
