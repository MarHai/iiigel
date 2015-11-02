function writeText(_sText){
    oCEditor = ace.edit('chapter-editor-ace');
    oCEditor.insert(_sText);
}

$(function() {
    var oCEditor = null, oCSession = null, oCEditorTimeout = null;
    
    
    function adjustIiigelHeight() {
        //initiate height
        var nHeightAvailable = $(window).innerHeight() - $('header').outerHeight() - $('footer').outerHeight();
        
        if($('#chapter-editor').length > 0) {
            var nHeightEditor = (Math.floor(nHeightAvailable/2)*0.9);
            $('#chapter-editor').height(nHeightEditor);
            $('#chapter-editor-ace').height($('#chapter-editor').height());
        }
        
        //setup other heights
        $('#chapter-interpreter').height(nHeightAvailable - nHeightEditor);
    }
    
  //update-chapter
    function updateChapter(_sContent) {
    	$('#chapter-interpreter').load('?c=Admin\\Chapter&a=updateChapter', { _sHashId: $('#chapter-editor').data('chapterhashid'), _sContent: _sContent });
    }
    
  //get-chapter selector
    function getChapter(_sHashId) {
        $.getJSON('?c=Admin\\Chapter&a=getChapter', { _sHashId: _sHashId }, function(_oChapter) {
            oCSession.setMode(ace.require('ace/ext/modelist').getModeForPath('_.html').mode);
            
            oCEditor.setValue(_oChapter.sText);
            oCEditor.setReadOnly(false);
            oCEditor.focus();
            oCEditor.gotoLine(oCSession.getLength(), oCSession.getLine(oCSession.getLength() - 1).length);
            oCSession.off('change');
            oCSession.on('change', function(_oEvent) {
                clearTimeout(oCEditorTimeout);
                nEditorWaitTime = parseInt($('#chapter-editor').data('editorwaittime'));
                
                oCEditorTimeout = setTimeout(function(_sContent) {
                    updateChapter(_sContent);
                }, nEditorWaitTime, oCEditor.getValue());
            });
            
            updateChapter(oCEditor.getValue());
        });
    }
    
    adjustIiigelHeight();
    $(window).resize(adjustIiigelHeight);
    
   //setup editor
    if($('#chapter-editor').length > 0) {
    	//ace
        oCEditor = ace.edit('chapter-editor-ace');
        oCEditor.setTheme('ace/theme/iplastic');
        oCEditor.setShowPrintMargin(false);
        oCEditor.setHighlightActiveLine(true);
        oCEditor.setDisplayIndentGuides(true);
        oCEditor.setFadeFoldWidgets(true);
        oCEditor.commands.addCommand({
            name: 'save',
            bindKey: {
                win: 'Ctrl-S',
                mac: 'Command-S',
                sender: 'editor|cli'
            },
            exec: function(_oEnvironment, _aArgument, _oRequest) {}
        });
        /*oCEditor.on('paste', function(_oEvent) {
            _oEvent.text = '';
        });*/
        oCSession = oCEditor.getSession();
        oCSession.setUseSoftTabs(true);
        oCSession.setUseWrapMode(true);
        oCSession.setOption('useWorker', false);
    	
    	getChapter($('#chapter-editor').data('chapterhashid'));
    }
    
});