	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Deepposter_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Deepposter_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . '../admin/css/deepposter-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Deepposter_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Deepposter_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . '../admin/js/deepposter-admin.js', array( 'jquery' ), $this->version, false );
		
		// Lokalisiere das Skript mit den notwendigen Daten
		wp_localize_script( $this->plugin_name, 'deepposter', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'deepposter_nonce' ),
			'openai_key' => !empty(get_option('deepposter_openai_api_key')),
			'deepseek_key' => !empty(get_option('deepposter_deepseek_api_key'))
		));
	} 