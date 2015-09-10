<?php

/**
 * Certificates service.
 */
class Edr_Crt {
	/**
	 * Get page sizes.
	 *
	 * @return array
	 */
	public function get_page_sizes() {
		return apply_filters( 'edr_crt_page_sizes', array(
			'a4' => array(
				'label'  => __( 'A4', 'edr-crt' ),
				'width'  => 595,
				'height' => 842,
			),
			'letter' => array(
				'label'  => __( 'Letter', 'edr-crt' ),
				'width'  => 612,
				'height' => 792,
			),
		) );
	}

	/**
	 * Output the certificate PDF.
	 *
	 * @param array $data Certificate data.
	 */
	public function output_pdf( $data ) {
		require_once dirname( __FILE__ ) . '/../lib/tfpdf/tfpdf.php';

		if ( 'L' != $data['orientation'] ) {
			$data['orientation'] = 'P';
		}

		$pdf = new TFPDF( $data['orientation'], 'pt', $data['page_size'] );
		$pdf->SetAutoPageBreak( false );
		$pdf->AddPage();
		$pdf->SetFont( 'helvetica', '', 12 );

		$w = $data['page_size'][0];
		$h = $data['page_size'][1];

		if ( 'L' == $data['orientation'] ) {
			$w = $data['page_size'][1];
			$h = $data['page_size'][0];
		}

		$pdf->Image( $data['image'], 0, 0, $w, $h, '', '' );

		$search = array( '{date}', '{course_title}', '{student_name}' );
		$replace = array( $data['date'], $data['course_title'], $data['student_name'] );

		$line_height = 1.6;
		$valid_align = array( 'L', 'C', 'R', 'J' );

		foreach ( $data['blocks'] as $block ) {
			$x = $block['x'];
			$y = $block['y'];
			$width = $block['width'];
			$height = $block['height'];
			$align = in_array( $block['align'], $valid_align ) ? $block['align'] : 'L';
			$font_size_pt = $block['font_size'];
			$line_height_mm = $font_size_pt * $line_height;

			$pdf->SetFontSize( $font_size_pt );
			$pdf->SetXY( $x, $y );
			$pdf->MultiCell( $width, $line_height_mm, str_replace( $search, $replace, $block['content'] ), 0, $align, false );
		}

		$pdf->Output( $data['file_name'], 'I' );
	}

	/**
	 * Create a certificate post.
	 *
	 * @param IB_Educator_Entry $entry
	 * @return int Post ID.
	 */
	public function create_certificate( $entry ) {
		$unique_id = substr( md5( $entry->ID . microtime() ), rand( 0, 23 ), 10 );

		$args = array(
			'post_type'   => 'edr_certificate',
			'post_name'   => $unique_id,
			'post_title'  => $unique_id,
			'post_author' => $entry->user_id,
			'post_status' => 'publish',
		);

		$crt_id = wp_insert_post( $args );

		if ( $crt_id ) {
			update_post_meta( $crt_id, 'student_id', $entry->user_id );
			update_post_meta( $crt_id, 'course_id', $entry->course_id );
			update_post_meta( $crt_id, 'entry_id', $entry->ID );
		}

		return $crt_id;
	}

	/**
	 * Get student name.
	 *
	 * @param WP_User $student
	 * @return string
	 */
	public function get_student_name( $student ) {
		$student_name = $student->display_name;

		if ( $student->first_name && $student->last_name ) {
			$student_name = $student->first_name . ' ' . $student->last_name;
		}

		return $student_name;
	}

	/**
	 * Get certificate by entry id.
	 *
	 * @param int $entry_id
	 * @return WP_Post|null
	 */
	public function get_certificate_by_entry_id( $entry_id ) {
		$query = new WP_Query( array(
			'post_type'      => 'edr_certificate',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array( 'value' => $entry_id ),
			),
		) );

		if ( $query->have_posts() ) {
			return $query->posts[0];
		}

		return null;
	}

	/**
	 * Get the certificate URL.
	 *
	 * @param int $entry_id
	 * @return string
	 */
	public function get_certificate_url( $entry_id ) {
		$certificate = $this->get_certificate_by_entry_id( $entry_id );

		if ( ! is_null( $certificate ) ) {
			return get_permalink( $certificate->ID );
		}

		return '';
	}

	/**
	 * Check if the current user can view a given certificate.
	 *
	 * @param WP_Post $certificate
	 * @return boolean
	 */
	public function can_view_certificate( $certificate ) {
		$can_view = false;
		$user = wp_get_current_user();
		$student_id = get_post_meta( $certificate->ID, 'student_id', true );

		if ( $student_id == $user->ID || current_user_can( 'administrator' ) ) {
			$can_view = true;
		}

		return $can_view;
	}
}
