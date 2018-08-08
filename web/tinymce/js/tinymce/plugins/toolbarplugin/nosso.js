tinymce.PluginManager.add('toolbarplugin', function(editor, url) {
    editor.addButton('toolbarplugin',
        {title       : 'my plugin button',
         image       : url + '/carro.png',
         onclick     : function() { alert('Clicked!');}});
});