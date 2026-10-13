(function() {
    tinymce.PluginManager.add('cn_mce_button', function( editor, url ) {
        editor.addButton('cn_mce_button', {
            text: 'Citynet',
            icon: 'settings',
            onclick: function() {
                // change the shortcode as per your requirement
                editor.insertContent('[citynet]');
            }
        });
    });
})();