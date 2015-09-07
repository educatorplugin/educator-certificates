<?php

class Edr_Crt_Test {
	private static $instance = null;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	public function addCourse() {
		$data = array(
			'post_title'  => 'A Course',
			'post_type'   => 'ib_educator_course',
			'post_status' => 'publish',
		);

		return wp_insert_post( $data );
	}

	public function addEntry( $data ) {
		$entry = IB_Educator_Entry::get_instance();
		$entry->course_id = $data['course_id'];
		$entry->user_id = $data['user_id'];
		$entry->entry_status = $data['entry_status'];
		$entry->grade = isset( $data['grade'] ) ? $data['grade'] : 0.00;
		$entry->complete_date = isset( $data['complete_date'] ) ? $data['complete_date'] : '';

		return ( $entry->save() ) ? $entry : null;
	}
}
