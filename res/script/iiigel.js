$(function() {
    var oCloud = {}, sCurrentFolderHash = null,
        oEditor = null, oSession = null, sCurrentFileHash = null, oEditorTimeout = null;
    
    function adjustIiigelHeight() {
        //initiate height
        var nHeightAvailable = $(window).innerHeight() - $('header').outerHeight() - $('footer').outerHeight();
        $('#iiigel-chapter').parentsUntil('body').each(function(i, _oElem) {
            nHeightAvailable -= $(_oElem).position().top;
        });
        
        //setup editor height
        if($('#iiigel-editor').length > 0) {
            var nHeightEditor = Math.floor(nHeightAvailable/2);
            $('#iiigel-editor').height(nHeightEditor);
            $('#iiigel-editor-ace').height($('#iiigel-editor').height());
        }
        
        //setup other heights
        $('#iiigel-cloud, #iiigel-interpreter, #iiigel-chapter').height(nHeightAvailable - nHeightEditor);
        var nHeightControls = Math.ceil($('#iiigel-controls').outerHeight());
        if($('#iiigel-module').height() > 120 && (nHeightAvailable - nHeightControls) > 120) {
            $('#iiigel-module').css('maxHeight', (nHeightAvailable - nHeightControls) + 'px');
        }
    }
    
    
    function updateCloudList(_oFolder) {
        oCloud = _oFolder;
        extractOpenFiles();
        
        if ((sCurrentFolderHash !== null) || (oCloud.length > 0)) {
        	openDir(sCurrentFolderHash === null ? oCloud[0]['sHashId'] : sCurrentFolderHash);
        }
    }

    function findElementForHash(_sHashId, _oCloud) {
        if(typeof(_oCloud) === 'undefined' || _oCloud === null) {
            _oCloud = oCloud;
        }
        for(var i in _oCloud) {
            if(_oCloud[i]['sHashId'] == _sHashId) {
                _oCloud[i]['aParent'] = [];
                return _oCloud[i];
            } else {
                if(typeof(_oCloud[i]['aChildren']) !== 'undefined') {
                    var mSub = findElementForHash(_sHashId, _oCloud[i]['aChildren']);
                    if(mSub !== null) {
                        mSub['aParent'].push(_oCloud[i]);
                        return mSub;
                    }
                }
            }
        }
        return null;
    }

    function findHashForName(_sName, _oCloud) {
        if(typeof(_oCloud) === 'undefined' || _oCloud === null) {
            _oCloud = oCloud;
        }
        for(var i in _oCloud) {
            if(_oCloud[i]['sName'] == _sName) {
                return _oCloud[i]['sHashId'];
            } else {
                if(typeof(_oCloud[i]['aChildren']) !== 'undefined') {
                    var mSub = findHashForName(_sName, _oCloud[i]['aChildren']);
                    if(mSub !== null) {
                        return mSub;
                    }
                }
            }
        }
        return null;
    }
    
    function saveFile(_sContent) {
    	if ((typeof(sCurrentFileHash) !== 'undefined') && (sCurrentFileHash !== null)) {
    		$.get('?c=Iiigel&a=update', { _sHashId: sCurrentFileHash, _sContent: _sContent });
    	}
    }

    function interpret() {
    	sCurrentChapterHash = $($('#iiigel-module a.active[data-chapter]').get(0)).data('chapter');
    	
    	if ((typeof(sCurrentChapterHash) === 'undefined') || (sCurrentChapterHash === null)) {
    		sCurrentChapterHash = $($('#iiigel-module a[data-chapter]').get(0)).data('chapter');
    	}
    	
    	if ((typeof(sCurrentFileHash) !== 'undefined') && (sCurrentFileHash !== null) &&
    		(typeof(sCurrentChapterHash) !== 'undefined') && (sCurrentChapterHash !== null)) {
    		$('#iiigel-interpreter').load('?c=Iiigel&a=interpret', { _sHashIdFile: sCurrentFileHash, _sHashIdChapter: sCurrentChapterHash });
    	}
    }

    //open-folder selector
    function openDir(_sHashId) {
        var oElem = findElementForHash(_sHashId);
        if(oElem['sType'] != 'folder') {
            oElem = null;
        }
        if(oElem === null && oCloud.length > 0) {
            oElem = oCloud[0];
        }
        $('#iiigel-cloudbrowser tbody tr').remove();
        if(oElem === null) {
            $('<tr><td colspan="5">' + i18n('error.querynoresult') + '</td></tr>').appendTo($('#iiigel-cloudbrowser tbody'));
        } else {
            sCurrentFolderHash = _sHashId;
            setupBreadcrumbs(oElem);
            if(oElem.aParent.length > 0) {
                $('<tr data-type="up"><td></td><td colspan="4"><i class="fa fa-level-up tooltips" title="' + i18n('cloud.onedirup') + '" data-placement="right"></i></td></tr>').appendTo($('#iiigel-cloudbrowser tbody'));
            }
            for(var i in oElem.aChildren) {
                $('<tr data-type="' + (oElem.aChildren[i].sType == 'folder' ? 'folder' : 'file') + '" data-hash="' + oElem.aChildren[i].sHashId + '">' +
                    '<td><i class="fa fa-' + (oElem.aChildren[i].sType == 'folder' ? 'folder' : 'file-text') + '"></i></td>' +
                    '<td>' + oElem.aChildren[i].sName + '</td>' +
                    '<td>' + oElem.aChildren[i].sSize + '</td>' +
                    '<td>' + i18n_datetime(oElem.aChildren[i].nUpdate) + '</td>' +
                    '<td>' +
                        '<a href="' + $('.logo').get(0).href + 'Iiigel/download/' + oElem.aChildren[i].sHashId + '" class="btn btn-default btn-xs tooltips iiigel-download iiigel-unobtrusive" data-placement="left" title="' + i18n('cloud.download' + (oElem.aChildren[i].sType == 'folder' ? 'folder' : 'file')) +'"><i class="fa fa-' + (oElem.aChildren[i].sType == 'folder' ? 'dropbox' : 'download') + '"></i></a> ' +
                        '<a href="#" class="btn btn-default btn-xs tooltips iiigel-rename iiigel-unobtrusive" data-placement="left" title="' + i18n('cloud.rename') +'"><i class="fa fa-i-cursor"></i></a> ' +
                        '<a href="#" class="btn btn-danger btn-xs tooltips iiigel-delete iiigel-unobtrusive" data-placement="left" title="' + i18n('cloud.delete' + (oElem.aChildren[i].sType == 'folder' ? 'folder' : 'file')) +'"><i class="fa fa-ban"></i></a>' +
                    '</td>' +
                  '</tr>').appendTo($('#iiigel-cloudbrowser tbody'));
            }
            $('#iiigel-cloud tbody tr[data-type]').on('mouseover', function(_oEvent) {
                $(this).find('a.iiigel-delete, a.iiigel-rename, a.iiigel-download').removeClass('iiigel-unobtrusive');
            });
            $('#iiigel-cloud tbody tr[data-type]').on('mouseout', function(_oEvent) {
                $(this).find('a.iiigel-delete, a.iiigel-rename, a.iiigel-download').addClass('iiigel-unobtrusive');
            });
            $('#iiigel-cloud [data-type]').off('click').on('click', function(_oEvent) {
                _oEvent.preventDefault();
                switch($(this).data('type')) {
                    case 'up':
                        if($('#iiigel-cloud .breadcrumb a:last').length == 1) {
                            openDir($('#iiigel-cloud .breadcrumb a:last').data('hash'));
                        }
                        break;
                    case 'file':
                        openFile($(this).data('hash'));
                        break;
                    case 'folder':
                        openDir($(this).data('hash'));
                        break;
                }
                return false;
            });
            $('#iiigel-cloud a.iiigel-delete').on('click', function(_oEvent) {
                _oEvent.preventDefault();
                bootbox.confirm(i18n('cloud.deleteareyousure'), (function(_oElem) { return function(_bResponse) {
                    if(_bResponse) {
                        $.getJSON('?c=Iiitgel&a=delete', { _sHashId: $(_oElem).parents('tr').data('hash') }, updateCloudList);
                    }
                }})(this));
                return false;
            });
            $('#iiigel-cloud a.iiigel-rename').on('click', function(_oEvent) {
                _oEvent.preventDefault();
                bootbox.prompt({
                    title: i18n('cloud.rename'),
                    value: $(this).parents('tr').find('td:nth(1)').text(),
                    callback: (function(_oElem) { return function(_mResult) {
                        if(_mResult !== null && _mResult) {
                            $.getJSON('?c=Iiitgel&a=rename', { _sHashId: $(this).parents('tr').data('hash'), _sNewName: _mResult }, updateCloudList);
                        }
                    }})(this)
                });
                return false;
            });
            $('#iiigel-cloud .tooltips').tooltip();
        }
    }

    function extractOpenFiles() {
        extractOpenFiles_inner();
        if($('#iiigel-editor .iiigel-files a[data-hash]').length == 0) {
            oEditor.setValue(i18n('mode.openfiletoedit'));
            oEditor.setReadOnly(true);
        }
    }
    
    function extractOpenFiles_inner(_oCloud) {
        if(typeof(_oCloud) === 'undefined' || _oCloud === null) {
            _oCloud = oCloud;
        }
        for(var i in _oCloud) {
            if(_oCloud[i]['sType'] != 'folder' && _oCloud[i]['bOpen'] == 1) {
                addToOpenFiles(_oCloud[i], false);
            } else if(_oCloud[i]['sType'] == 'folder' && typeof(_oCloud[i]['aChildren']) !== 'undefined') {
                extractOpenFiles_inner(_oCloud[i]['aChildren']);
            }
        }

    }
    
    function addToOpenFiles(_oFile, _bOpen) {
        if($('#iiigel-editor .iiigel-files a[data-hash="' + _oFile.sHashId + '"]').length == 0) {
            $('<div class="btn-group"><a href="#" class="btn btn-' + (_bOpen ? 'primary' : 'default') + ' tooltips" title="' + _oFile.sName + '" data-placement="left" data-hash="' + _oFile.sHashId + '">' + _oFile.sName + '</a><a href="#" class="btn btn-' + (_bOpen ? 'primary' : 'default') + ' tooltips" data-placement="left" data-close="' + _oFile.sHashId + '" title="' + i18n('cloud.close') + '">&times;</a></div>')
                .prependTo($('#iiigel-editor .iiigel-files'));
            $('#iiigel-editor .iiigel-files a[data-hash="' + _oFile.sHashId + '"].tooltips, #iiigel-editor .iiigel-files a[data-close="' + _oFile.sHashId + '"].tooltips').tooltip();
            $('#iiigel-editor .iiigel-files a[data-hash="' + _oFile.sHashId + '"]').on('click', openOpenedFile);
            $('#iiigel-editor .iiigel-files a[data-close="' + _oFile.sHashId + '"]').on('click', closeOpenedFile);
        }
    }

    function setupBreadcrumbs(_oFolder) {
        $('#iiigel-cloud .breadcrumb li').remove();
        for(var i = _oFolder.aParent.length - 1; i >= 0; i--) {
            $('<li><a href="#" data-type="folder" data-hash="' + _oFolder.aParent[i].sHashId + '">' + _oFolder.aParent[i].sName + '</a></li>').appendTo($('#iiigel-cloud .breadcrumb'));
        }
        $('<li class="active">' + _oFolder.sName + '</li>').appendTo($('#iiigel-cloud .breadcrumb'));
    }

    //open-file link handler
    function openOpenedFile(_oEvent) {
        _oEvent.preventDefault();
        openFile($(this).data('hash'));
        return false;
    }

    //open-file closing link handler
    function closeOpenedFile(_oEvent) {
        _oEvent.preventDefault();
        $.get('?c=Iiigel&a=close', { _sHashId: $(this).data('close') });
        if($(this).hasClass('btn-primary')) {
            if($(this).parents('.btn-group').prev('.btn-group').length > 0) {
                $(this).parents('.btn-group').prev('.btn-group').find('a[data-hash]').click();
            } else if($(this).parents('.btn-group').next('.btn-group').length > 0) {
                $(this).parents('.btn-group').next('.btn-group').find('a[data-hash]').click();
            } else {
                oEditor.setValue(i18n('mode.openfiletoedit'));
                oEditor.setReadOnly(true);
                sCurrentFileHash = null;
            }
        }
        $(this).parents('.btn-group').remove();
        return false;
    }

    //open-file selector
    function openFile(_sHashId) {
        $.getJSON('?c=Iiigel&a=open', { _sHashId: _sHashId }, function(_oFile) {
            if(_oFile.sType.indexOf('text') === 0) {
                sCurrentFileHash = _oFile.sHashId;
                if(_oFile.sName.indexOf('.') > 0) {
                    oSession.setMode(ace.require('ace/ext/modelist').getModeForPath(_oFile.sName).mode);
                }
                $('#iiigel-editor .iiigel-files a.btn-primary').removeClass('btn-primary').addClass('btn-default');
                if($('#iiigel-editor .iiigel-files a[data-hash="' + _oFile.sHashId + '"]').length == 0) {
                    addToOpenFiles(_oFile, true);
                } else {
                    $('#iiigel-editor .iiigel-files a[data-hash="' + _oFile.sHashId + '"]').addClass('btn-primary').removeClass('btn-default');
                    $('#iiigel-editor .iiigel-files a[data-close="' + _oFile.sHashId + '"]').addClass('btn-primary').removeClass('btn-default');
                }
                
                oEditor.setValue(_oFile.sFile);
                oEditor.setReadOnly(false);
                oEditor.focus();
                oEditor.gotoLine(oSession.getLength(), oSession.getLine(oSession.getLength() - 1).length);
                oSession.off('change');
                oSession.on('change', function(_oEvent) {
                    clearTimeout(oEditorTimeout);
                    nEditorWaitTime = parseInt($('#iiigel-editor').data('editorwaittime'));
                    
                    if (nEditorWaitTime >= 0) {
                    	oEditorTimeout = setTimeout(function(_sContent) {
                            saveFile(_sContent);
                            interpret();
                        }, nEditorWaitTime, oEditor.getValue());
                    } else {
                    	oEditorTimeout = setTimeout(function(_sContent) {
                            saveFile(_sContent);
                        }, nEditorWaitTime, oEditor.getValue());
                    }
                });
            } else {
                //open file in new browser window with
                //url/file/' + _oFile.sFile
            }
        });
    }
    
    //allow mode changes (from cloud to interpreter to chapter and back)
    function changeMode(_oEvent) {
        _oEvent.preventDefault();
        $('#iiigel-cloud, #iiigel-interpreter, #iiigel-chapter').addClass('hide');
        $('#' + this.href.split('#')[1]).removeClass('hide');
        $('a[href="#iiigel-cloud"], a[href="#iiigel-interpreter"], a[href="#iiigel-chapter"]').removeClass('active');
        $(this).addClass('active');
        return false;
    }
    
    
    
    
    adjustIiigelHeight();
    $(window).resize(adjustIiigelHeight);
    
    //setup editor
    if($('#iiigel-editor').length > 0) {
        //ace
        oEditor = ace.edit('iiigel-editor-ace');
        oEditor.setTheme('ace/theme/iplastic');
        oEditor.setShowPrintMargin(false);
        oEditor.setHighlightActiveLine(true);
        oEditor.setDisplayIndentGuides(true);
        oEditor.setFadeFoldWidgets(true);
        oEditor.commands.addCommand({
            name: 'save',
            bindKey: {
                win: 'Ctrl-S',
                mac: 'Command-S',
                sender: 'editor|cli'
            },
            exec: function(_oEnvironment, _aArgument, _oRequest) {}
        });
        oEditor.on('paste', function(_oEvent) {
            _oEvent.text = '';
        });
        oSession = oEditor.getSession();
        oSession.setUseSoftTabs(true);
        oSession.setUseWrapMode(true);
        oSession.setOption('useWorker', false);
        
        //interpret on click
        $('.iiigel-interpret').off('click').on('click', function(_oEvent) {
            _oEvent.preventDefault();
            interpret();
            $('a[href="#iiigel-interpreter"]').click();
            return false;
        });
    }
    
    $('a[href="#iiigel-cloud"]').off('click').on('click', changeMode);
    $('a[href="#iiigel-interpreter"]').off('click').on('click', changeMode);
    $('a[href="#iiigel-chapter"]').off('click').on('click', changeMode);
    
    //handin
    $('a.iiigel-handin').off('click').on('click', function(_oEvent) {
        _oEvent.preventDefault();
        bootbox.confirm(i18n('handin.areyousure'), function(_bHandin) {
            if(_bHandin) {
                bootbox.alert(i18n('handin.done'));
            }
        });
        return false;
    });
    
    //help call requests
    $('a.iiigel-help').off('click').on('click', function(_oEvent) {
        _oEvent.preventDefault();
        bootbox.prompt(i18n('help.question'), function(_mQuestion) {
            if(_mQuestion !== null) {
                $.gritter.add({
                    title: 'Mario Haim braucht Hilfe',
                    text: _mQuestion,
                    image: 'http://www.gravatar.com/avatar/e9cfacb01dbd96cc4fc9ca45bcb57d70.jpg?s=40&d=mm',
                    sticky: true
                });
            }
        });
        return false;
    });
    
    //setup cloud
    if($('#iiigel-cloud').length > 0) {
        //creation
        $('#iiigel-cloud a.iiigel-createfile, #iiigel-cloud a.iiigel-createdir').on('click', function(_oEvent) {
            _oEvent.preventDefault();
            var sAction = $(this).hasClass('iiigel-createdir') ? 'createDir' : 'createFile';
            bootbox.prompt(i18n('cloud.' + sAction.toLowerCase()), (function(_sAction, _oElem) { return function(_mName) {
                if(_mName !== null) {
                    $.getJSON('?c=Iiigel&a=' + _sAction, { _sHashIdParent: sCurrentFolderHash, _sName: _mName }, (function(_sName) { return function(_oReturn) {
                        updateCloudList(_oReturn);
                        openFile(findHashForName(_sName));
                    }})(_mName));
                }
            }})(sAction, this));
            return false;
        });
        
        //upload from url
        $('#iiigel-cloud #sUrl').parent().find('a.btn').on('click', function(_oEvent) {
            _oEvent.preventDefault();
            $.getJSON('?c=Iiigel&a=uploadFromUrl', { _sHashId: sCurrentFolderHash, _sUrl: $('#iiigel-cloud #sUrl').val() }, function(_oData) {
                updateCloudList(_oData);
                $('#iiigel-cloud #sUrl').val('');
            });
            return false;
        });
        $('#iiigel-cloud #sUrl').on('keypress', function(_oEvent) {
            if(_oEvent.keyCode == 13 || _oEvent.keyCode == 10) {
                _oEvent.preventDefault();
                $('#iiigel-cloud #sUrl').parent().find('a.btn').click();
                return false;
            }
        })
        
        //upload from host
        $('input[type="file"].iiigel-nodefaultupload').fileupload({
            dataType: 'json',
            add: function(_oEvent, _oData) {
                for(var i = 0; i < _oData.files.length; i++) {
                    $(this).after('<div class="control-label"><div class="progress"><div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%" data-name="' + _oData.files[i].name + '"><span class="sr-only">' + _oData.files[i].name + '</span></div></div></div>');
                }
                
                _oData.form[0].action = '?c=Iiigel&a=uploadFromHost&_sHashId=' + sCurrentFolderHash;
                _oData.submit();
            },
            error: function(_oEvent, _sStatus, _oError) {
                bootbox.alert(i18n('error') + '<br />' + _oError.message + '<br /><br />' + _oEvent.responseText);
            },
            done: function(_oEvent, _oData) {
                updateCloudList(_oData.result);
                $(this).siblings('.control-label').remove();
            },
            progress: function(_oEvent, _oData) {
                for(var i = 0; i < _oData.files.length; i++) {
                    $('.progress-bar[data-name="' + _oData.files[i].name + '"]').css('width', parseInt(_oData.loaded / _oData.total * 100, 10) + '%');
                }
            }
        });
    }
    
    //initial load of cloud and open files
    $.getJSON('?c=Iiigel&a=cloud', function(_oData) {
        updateCloudList(_oData);
        $('#iiigel-editor .iiigel-files a[data-hash]').first().click();
    });
});
