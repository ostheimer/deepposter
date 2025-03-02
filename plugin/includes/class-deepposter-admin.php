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

		// Manueller Fix für die Datenbank
		global $wpdb;
		
		echo '<div class="wrap">';
		echo '<h1>DeepPoster Datenbank-Reparatur</h1>';
		
		// Prüfe, ob die Reparatur ausgeführt werden soll
		$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
		
		if ($action === 'repair') {
			echo '<div class="notice notice-info"><p>Reparatur wird ausgeführt...</p></div>';
			
			// 1. Finde duplizierte post_name Einträge
			$query = "
				SELECT post_name, COUNT(*) as count
				FROM {$wpdb->posts}
				WHERE post_type = 'deepposter_prompt'
				GROUP BY post_name
				HAVING count > 1
			";
			
			$duplicates = $wpdb->get_results($query);
			$fixed_count = 0;
			
			echo '<h2>1. Duplizierte post_name Einträge</h2>';
			echo '<p>Gefundene duplizierte post_name Einträge: ' . count($duplicates) . '</p>';
			
			if (!empty($duplicates)) {
				echo '<ul>';
				
				foreach ($duplicates as $duplicate) {
					echo '<li>Repariere duplizierte post_name: ' . esc_html($duplicate->post_name) . ' (' . intval($duplicate->count) . ' Duplikate)</li>';
					
					// Hole alle Posts mit diesem post_name
					$posts = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT ID, post_name FROM {$wpdb->posts} 
							WHERE post_type = 'deepposter_prompt' AND post_name = %s
							ORDER BY ID ASC",
							$duplicate->post_name
						)
					);
					
					// Überspringe den ersten Eintrag (behalte ihn)
					array_shift($posts);
					
					// Aktualisiere die restlichen Einträge mit eindeutigen Namen
					foreach ($posts as $index => $post) {
						$new_post_name = $duplicate->post_name . '-' . ($index + 1);
						
						$wpdb->update(
							$wpdb->posts,
							array('post_name' => $new_post_name),
							array('ID' => $post->ID),
							array('%s'),
							array('%d')
						);
						
						echo '<li style="margin-left: 20px;">Post ID ' . intval($post->ID) . ' umbenannt von ' . esc_html($duplicate->post_name) . ' zu ' . esc_html($new_post_name) . '</li>';
						$fixed_count++;
					}
				}
				
				echo '</ul>';
			} else {
				echo '<p>Keine duplizierten post_name Einträge gefunden.</p>';
			}
			
			// 2. Finde duplizierte Metadaten
			$meta_query = "
				SELECT post_id, meta_key, COUNT(*) as count
				FROM {$wpdb->postmeta}
				WHERE post_id IN (
					SELECT ID FROM {$wpdb->posts} WHERE post_type = 'deepposter_prompt'
				)
				GROUP BY post_id, meta_key
				HAVING count > 1
			";
			
			$meta_duplicates = $wpdb->get_results($meta_query);
			$meta_fixed_count = 0;
			
			echo '<h2>2. Duplizierte Metadaten</h2>';
			echo '<p>Gefundene duplizierte Metadaten: ' . count($meta_duplicates) . '</p>';
			
			if (!empty($meta_duplicates)) {
				echo '<ul>';
				
				foreach ($meta_duplicates as $duplicate) {
					echo '<li>Repariere duplizierte Metadaten: Post ID ' . intval($duplicate->post_id) . ', Meta-Key ' . esc_html($duplicate->meta_key) . ' (' . intval($duplicate->count) . ' Duplikate)</li>';
					
					// Hole alle Metadaten für diesen Post und Schlüssel
					$metas = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT meta_id FROM {$wpdb->postmeta} 
							WHERE post_id = %d AND meta_key = %s
							ORDER BY meta_id ASC",
							$duplicate->post_id,
							$duplicate->meta_key
						)
					);
					
					// Überspringe den ersten Eintrag (behalte ihn)
					array_shift($metas);
					
					// Lösche die restlichen Einträge
					foreach ($metas as $meta) {
						$wpdb->delete(
							$wpdb->postmeta,
							array('meta_id' => $meta->meta_id),
							array('%d')
						);
						
						echo '<li style="margin-left: 20px;">Meta ID ' . intval($meta->meta_id) . ' für Post ID ' . intval($duplicate->post_id) . ' gelöscht</li>';
						$meta_fixed_count++;
					}
				}
				
				echo '</ul>';
			} else {
				echo '<p>Keine duplizierten Metadaten gefunden.</p>';
			}
			
			// 3. Erstelle einen Test-Prompt
			echo '<h2>3. Test-Prompt erstellen</h2>';
			
			// Erstelle einen Test-Prompt
			$prompt_title = 'Test-Prompt (automatisch erstellt)';
			$prompt_content = 'Du bist ein professioneller Content-Ersteller für WordPress-Blogs. Erstelle einen gut strukturierten Artikel in der Kategorie [KATEGORIE]. Der Artikel sollte informativ, gut recherchiert und SEO-optimiert sein.';
			
			$prompt_data = array(
				'post_title'    => $prompt_title,
				'post_content'  => $prompt_content,
				'post_status'   => 'publish',
				'post_type'     => 'deepposter_prompt',
				'post_author'   => get_current_user_id()
			);
			
			$prompt_id = wp_insert_post($prompt_data);
			
			if ($prompt_id && !is_wp_error($prompt_id)) {
				echo '<p>Test-Prompt erstellt mit ID: ' . intval($prompt_id) . '</p>';
				
				// Setze den neuen Prompt als aktiven Prompt
				update_option('deepposter_active_prompt', $prompt_id);
				echo '<p>Test-Prompt als aktiver Prompt gesetzt.</p>';
			} else {
				echo '<p>Fehler beim Erstellen des Test-Prompts: ' . ($prompt_id && is_wp_error($prompt_id) ? esc_html($prompt_id->get_error_message()) : 'Unbekannter Fehler') . '</p>';
			}
			
			// 4. Cache leeren
			echo '<h2>4. Cache leeren</h2>';
			wp_cache_flush();
			echo '<p>WordPress-Cache geleert.</p>';
			
			// Zusammenfassung
			echo '<h2>Zusammenfassung</h2>';
			echo '<p>Reparatur abgeschlossen:</p>';
			echo '<ul>';
			echo '<li>' . intval($fixed_count) . ' duplizierte Posts repariert</li>';
			echo '<li>' . intval($meta_fixed_count) . ' duplizierte Metadaten repariert</li>';
			echo '<li>1 Test-Prompt erstellt</li>';
			echo '</ul>';
			
			echo '<p><a href="edit.php?post_type=deepposter_prompt" class="button button-primary">Zurück zu den Prompts</a></p>';
			
		} else {
			// Zeige Informationen und Reparatur-Button an
			echo '<div class="notice notice-warning"><p><strong>Achtung:</strong> Bitte erstellen Sie ein Backup Ihrer Datenbank, bevor Sie die Reparatur durchführen.</p></div>';
			
			echo '<p>Dieses Tool repariert duplizierte IDs in der DeepPoster-Datenbank, die zu Fehlern in der Dropdown-Liste führen können.</p>';
			echo '<p>Folgende Aktionen werden durchgeführt:</p>';
			echo '<ol>';
			echo '<li>Duplizierte post_name Einträge werden repariert</li>';
			echo '<li>Duplizierte Metadaten werden entfernt</li>';
			echo '<li>Ein Test-Prompt wird erstellt</li>';
			echo '<li>Der WordPress-Cache wird geleert</li>';
			echo '</ol>';
			
			echo '<p><a href="' . esc_url(admin_url('edit.php?post_type=deepposter_prompt&page=deepposter-db-repair&action=repair')) . '" class="button button-primary">Datenbank jetzt reparieren</a></p>';
			
			// Zeige auch den JavaScript-Fix an
			echo '<h2>Alternative: JavaScript-Fix</h2>';
			echo '<p>Alternativ können Sie auch folgenden JavaScript-Code in der Browser-Konsole ausführen, um die Dropdown-Liste zu reparieren:</p>';
			echo '<pre style="background: #f5f5f5; padding: 10px; border-radius: 3px; overflow: auto; max-height: 200px;">';
			echo "jQuery('select[name=\"prompt\"]').empty().append('<option value=\"\">Prompt auswählen</option>').append('<option value=\"test1\">Test-Prompt 1</option>').append('<option value=\"test2\">Test-Prompt 2</option>').append('<option value=\"test3\">Test-Prompt 3</option>');";
			echo '</pre>';
			echo '<p>Um die Konsole zu öffnen, drücken Sie F12 oder Rechtsklick > "Untersuchen" > "Konsole".</p>';
		}
		
		echo '</div>';
	} 