<?php

class Edr_Crt {
	public function output_pdf( $data ) {
		require_once dirname( __FILE__ ) . '/../lib/tfpdf/tfpdf.php';

		if ( 'L' != $data['orientation'] ) {
			$data['orientation'] = 'P';
		}

		$pdf = new TFPDF( $data['orientation'] );
		$pdf->SetAutoPageBreak( false );
		$pdf->AddPage();
		$pdf->SetFont( 'helvetica', '', 12 );

		$w = 210;
		$h = 297;

		if ( 'L' == $data['orientation'] ) {
			$w = 297;
			$h = 210;
		}

		$pdf->Image( $data['image'], 0, 0, $w, $h, '', '' );

		$search = array( '{date}', '{course_title}', '{student_name}' );
		$replace = array( $data['date'], $data['course_title'], $data['student_name'] );

		$line_height = 1.6;
		$valid_align = array( 'L', 'C', 'R', 'J' );

		foreach ( $data['blocks'] as $block ) {
			$x = $block['x'] * 25.4 / 72;
			$y = $block['y'] * 25.4 / 72;
			$width = $block['width'] * 25.4 / 72;
			$height = $block['height'] * 25.4 / 72;
			$align = in_array( $block['align'], $valid_align ) ? $block['align'] : 'L';
			$font_size_pt = $block['font_size'];
			$line_height_mm = $font_size_pt / 72 * 25.4 * $line_height;

			$pdf->SetFontSize( $font_size_pt );
			$pdf->SetXY( $x, $y );
			$pdf->MultiCell( $width, $line_height_mm, str_replace( $search, $replace, $block['content'] ), 0, $align, false );
		}

		$pdf->Output();
	}

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
			update_post_meta( $crt_id, 'entry_id', $entry->ID );
		}

		return $crt_id;
	}

	public function get_certificate_url( $entry_id ) {
		$query = new WP_Query( array(
			'post_type'      => 'edr_certificate',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array( 'value' => $entry_id ),
			),
		) );

		if ( $query->have_posts() ) {
			return get_permalink( $query->posts[0]->ID );
		}

		return '';
	}

	public function can_view_certificate( $certificate ) {
		$user = wp_get_current_user();
		$can_view = false;

		if ( $certificate->post_author == $user->ID ) {
			$can_view = true;
		}

		return $can_view;
	}
}
