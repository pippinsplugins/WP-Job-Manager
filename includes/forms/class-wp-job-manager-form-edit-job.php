<?php

include_once( 'class-wp-job-manager-form-submit-job.php' );

/**
 * WP_Job_Manager_Form_Edit_Job class.
 */
class WP_Job_Manager_Form_Edit_Job extends WP_Job_Manager_Form_Submit_Job {

	public static $form_name = 'edit-job';

	/**
	 * Constructor
	 */
	public static function init() {
		self::$job_id = ! empty( $_REQUEST['job_id'] ) ? absint( $_REQUEST[ 'job_id' ] ) : 0;
	}

	/**
	 * output function.
	 *
	 * @access public
	 * @return void
	 */
	public static function output() {
		self::submit_handler();
		self::submit();
	}

	/**
	 * Submit Step
	 */
	public static function submit() {
		global $job_manager, $post;

		$job = get_post( self::$job_id );

		if ( empty( self::$job_id  ) || $job->post_status !== 'publish' ) {
			echo wpautop( __( 'Invalid job', 'job_manager' ) );
			return;
		}

		self::init_fields();

		foreach ( self::$fields as $group_key => $fields ) {
			foreach ( $fields as $key => $field ) {
				switch ( $key ) {
					case 'job_title' :
						self::$fields[ $group_key ][ $key ]['value'] = $job->post_title;
					break;
					case 'job_description' :
						self::$fields[ $group_key ][ $key ]['value'] = $job->post_content;
					break;
					case 'job_type' :
						self::$fields[ $group_key ][ $key ]['value'] = current( wp_get_object_terms( $job->ID, 'job_listing_type', array( 'fields' => 'slugs' ) ) );
					break;
					case 'job_category' :
						self::$fields[ $group_key ][ $key ]['value'] = current( wp_get_object_terms( $job->ID, 'job_listing_category', array( 'fields' => 'slugs' ) ) );
					break;
					default:
						self::$fields[ $group_key ][ $key ]['value'] = get_post_meta( $job->ID, '_' . $key, true );
					break;
				}
			}
		}

		get_job_manager_template( 'job-submit.php', array( 'form' => __CLASS__, 'submit_button_text' => __( 'Update job listing', 'job_manager' ) ) );
	}

	/**
	 * Submit Step is posted
	 */
	public static function submit_handler() {
		if ( empty( $_POST['submit_job'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'submit_form_posted' ) )
			return;

		try {

			// Get posted values
			$values = self::get_posted_fields();

			// Validate required
			if ( is_wp_error( ( $return = self::validate_fields( $values ) ) ) )
				throw new Exception( $return->get_error_message() );

			// Update the job
			self::save_job( $values['job']['job_title'], $values['job']['job_description'], 'publish' );
			self::update_job_data( $values );

			// Successful
			echo '<div class="job-manager-message">' . __( 'Your changes have been saved.', 'job_manager' ), ' <a href="' . get_permalink( self::$job_id ) . '">' . __( 'View Job Listing &rarr;', 'job_manager' ) . '</a>' . '</div>';

		} catch ( Exception $e ) {
			echo '<div class="job-manager-error">' . $e->getMessage() . '</div>';
			return;
		}
	}
}

WP_Job_Manager_Form_Edit_Job::init();