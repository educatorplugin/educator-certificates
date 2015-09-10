<?php

class EdrCrtTest extends WP_UnitTestCase {
	/**
	 * Test the "create_certificate" method.
	 */
	public function testCreateCertificate() {
		$edr_crt_test = Edr_Crt_Test::get_instance();
		$course = get_post( $edr_crt_test->addCourse() );
		$user = get_user_by( 'id', 1 );
		$entry = $edr_crt_test->addEntry( array(
			'user_id'      => $user->ID,
			'course_id'    => $course->ID,
			'entry_status' => 'complete',
			'grade'        => 89.67,
		) );
		$certificate_id = Edr_Manager::get( 'edr_crt' )->create_certificate( $entry );

		$this->assertTrue( intval( $certificate_id ) == $certificate_id && $certificate_id > 0 );
		$this->assertEquals( $entry->user_id, get_post_meta( $certificate_id, 'student_id', true ) );
		$this->assertEquals( $entry->course_id, get_post_meta( $certificate_id, 'course_id', true ) );
		$this->assertEquals( $entry->ID, get_post_meta( $certificate_id, 'entry_id', true ) );
	}

	/**
	 * Test the "get_student_name" method.
	 */
	public function testGetStudentName() {
		$edr_crt = Edr_Manager::get( 'edr_crt' );
		$student = get_user_by( 'id', 1 );

		$this->assertEquals( $student->display_name, $edr_crt->get_student_name( $student ) );

		wp_update_user( array(
			'ID'         => $student->ID,
			'first_name' => 'FirstName',
			'last_name'  => 'LastName',
		) );

		$this->assertEquals( 'FirstName LastName', $edr_crt->get_student_name( $student ) );
	}

	/**
	 * Test the "get_certificate_by_entry_id" method.
	 */
	public function testGetCertificateByEntryId() {
		$entry = Edr_Crt_Test::get_instance()->addEntry( array(
			'user_id'      => 1,
			'course_id'    => 1,
			'entry_status' => 'complete',
		) );

		$edr_crt = Edr_Manager::get( 'edr_crt' );
		$certificate_id = $edr_crt->create_certificate( $entry );

		$this->assertEquals( $certificate_id, $edr_crt->get_certificate_by_entry_id( $entry->ID )->ID );
	}

	/**
	 * Test the "get_certificate_url" method.
	 */
	public function testGetCertificateUrl() {
		$entry = Edr_Crt_Test::get_instance()->addEntry( array(
			'user_id'      => 1,
			'course_id'    => 1,
			'entry_status' => 'complete',
		) );

		$edr_crt = Edr_Manager::get( 'edr_crt' );
		$certificate_id = $edr_crt->create_certificate( $entry );

		$this->assertEquals( get_permalink( $certificate_id ), $edr_crt->get_certificate_url( $entry->ID ) );
	}

	/**
	 * Test the "can_view_certificate" method.
	 */
	public function testCanViewCertificate() {
		global $current_user;

		// Create student.
		$student1_id = wp_insert_user( array(
			'user_login' => 'student1',
			'user_pass'  => '123456',
			'role'       => 'student',
		) );

		// Create entry.
		$entry = Edr_Crt_Test::get_instance()->addEntry( array(
			'user_id'      => $student1_id,
			'course_id'    => 1,
			'entry_status' => 'complete',
		) );

		// Create certificate.
		$edr_crt = Edr_Manager::get( 'edr_crt' );
		$certificate_id = $edr_crt->create_certificate( $entry );
		$certificate = get_post( $certificate_id );

		// Guest user.
		$this->assertEquals( 0, get_current_user_id() );
		$this->assertFalse( $edr_crt->can_view_certificate( $certificate ) );

		// Student 1.
		$current_user = new WP_User( $student1_id );
		$this->assertEquals( $student1_id, get_current_user_id() );
		$this->assertTrue( $edr_crt->can_view_certificate( $certificate ) );

		// Administrator.
		$current_user = new WP_User( 1 );
		$this->assertEquals( 1, get_current_user_id() );
		$this->assertTrue( $edr_crt->can_view_certificate( $certificate ) );
	}
}
