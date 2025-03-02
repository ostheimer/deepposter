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
		
		// Füge das Dropdown-Fix-Skript hinzu
		wp_enqueue_script( $this->plugin_name . '-dropdown-fix', plugin_dir_url( __FILE__ ) . '../assets/dropdown-fix.js', array( 'jquery', $this->plugin_name ), $this->version, true );

		// Lokalisiere das Skript mit den notwendigen Daten
		wp_localize_script( $this->plugin_name, 'deepposter', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'deepposter_nonce' ),
			'openai_key' => !empty(get_option('deepposter_openai_api_key')),
			'deepseek_key' => !empty(get_option('deepposter_deepseek_api_key'))
		));
	}

	/**
	 * Fügt einen Menüpunkt für das Datenbank-Reparatur-Tool hinzu
	 *
	 * @since    1.0.0
	 */
	public function add_repair_menu() {
		add_submenu_page(
			'edit.php?post_type=deepposter_prompt',
			'Datenbank-Reparatur',
			'Datenbank-Reparatur',
			'manage_options',
			'deepposter-db-repair',
			array( $this, 'display_repair_page' )
		);
	}

	/**
	 * Zeigt die Datenbank-Reparatur-Seite an
	 *
	 * @since    1.0.0
	 */
	public function display_repair_page() {
		// Sicherheitscheck
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Lade das Reparatur-Skript
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'assets/db-fix.php';
	} 