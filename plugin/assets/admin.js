jQuery(document).ready(function($) {
    $('#aiGeneratorForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find(':submit');
        const $results = $('#generationResults');
        
        // Disable submit button
        $submitButton.prop('disabled', true);
        
        // Show loading message
        $results.html('<div class="notice notice-info"><p>Generiere Artikel...</p></div>');
        
        // Collect form data
        const formData = {
            action: 'deepposter_generate',
            nonce: deepposter.nonce,
            category: $('#categorySelect').val(),
            count: $('#articleCount').val(),
            publish: $('#publishImmediately').is(':checked')
        };
        
        // Send AJAX request
        $.post(deepposter.ajaxurl, formData)
            .done(function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(function(post) {
                        html += `
                            <div class="generation-item">
                                <h4>${post.title}</h4>
                                <div class="generation-meta">
                                    Kategorie: ${post.category}
                                    Status: ${post.status}
                                </div>
                                <div class="generation-actions">
                                    <a href="${post.editUrl}" class="button" target="_blank">Bearbeiten</a>
                                    <a href="${post.viewUrl}" class="button" target="_blank">Ansehen</a>
                                </div>
                            </div>
                        `;
                    });
                    $results.html(html);
                } else {
                    $results.html(`
                        <div class="notice notice-error">
                            <p>${response.data}</p>
                        </div>
                    `);
                }
            })
            .fail(function(xhr, status, error) {
                $results.html(`
                    <div class="notice notice-error">
                        <p>Ein Fehler ist aufgetreten: ${error}</p>
                    </div>
                `);
            })
            .always(function() {
                $submitButton.prop('disabled', false);
            });
    });
}); 