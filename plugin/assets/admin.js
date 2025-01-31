jQuery(document).ready(function($) {
    const form = $('#aiGeneratorForm');
    const results = $('#generationResults');

    form.on('submit', function(e) {
        e.preventDefault();
        
        results.html(`
            <div class="loading">
                <div class="spinner is-active"></div>
                <p>Generiere ${$('#articleCount').val()} Artikel...</p>
            </div>
        `);

        $.ajax({
            url: aiGenerator.ajaxurl,
            method: 'POST',
            data: {
                action: 'generate_articles',
                nonce: aiGenerator.nonce,
                category: $('#categorySelect').val(),
                count: $('#articleCount').val(),
                publish: $('#publishImmediately').is(':checked')
            },
            success: function(response) {
                if (response.success) {
                    let html = '<div class="generated-articles">';
                    response.data.forEach(post => {
                        html += `
                            <div class="article">
                                <h3>${post.post_title}</h3>
                                <div class="post-actions">
                                    <a href="/wp-admin/post.php?post=${post.ID}&action=edit" 
                                       class="button" 
                                       target="_blank">
                                       Bearbeiten
                                    </a>
                                    <a href="${post.guid}" 
                                       class="button" 
                                       target="_blank">
                                       Ansehen
                                    </a>
                                </div>
                            </div>`;
                    });
                    results.html(html);
                }
            },
            error: function(xhr) {
                results.html(`
                    <div class="error notice">
                        <p>Fehler: ${xhr.responseJSON?.data || 'Unbekannter Fehler'}</p>
                    </div>
                `);
            }
        });
    });
});
