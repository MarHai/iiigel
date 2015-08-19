$(function() {
    //move debug output into body
    if($('#main-content .wrapper').length > 0) {
        $('body > .debug').appendTo('#main-content .wrapper');
    }
    
    //set modal/popup links for other stuff
    $('a[data-iiigel="confirm"]').off('click').on('click', function(_oEvent) {
        _oEvent.preventDefault();
        bootbox.confirm($(this).data('confirm'), (function(_sHref) { return function(_oResult) {
            if(_oResult === true) {
                document.location.href = _sHref;
            }
        }})(this.href));
        return false;
    });
    
    //set modal/popup links for forms
    $('a[data-iiigel="form"]').off('click').on('click', function(_oEvent) {
        _oEvent.preventDefault();
        var aUrlParam = document.location.href.split('?'),
            oData = {};
        if(aUrlParam.length > 1) {
            aUrlParam = aUrlParam[1].split('&');
            for(var i = 0; i < aUrlParam.length; i++) {
                aUrlParam[i] = aUrlParam[i].split('=');
                oData[unescape(aUrlParam[i][0])] = unescape(aUrlParam[i][1]);
            }
        }
        $.get('?c=StaticPage&a=loadFile&_sFile=' + $(this).data('form'), oData, (function(_oElem) { return function(_sHtml) {
            var sTitle = $(_oElem).text();
            bootbox.dialog({
                title: sTitle,
                onEscape: true,
                message: _sHtml,
                buttons: {
                    cancel: {
                        label: i18n('cancel'),
                        className: 'btn-default',
                        callback: function() {
                        }
                    },
                    success: {
                        label: sTitle,
                        className: 'btn-success',
                        callback: function() {
                            $('.bootbox form').submit();
                        }
                    }
                }
            });
            
        } })(this));
        return false;
    });
    
    //set editable values
    $('a[data-name][data-url][data-title][data-type]').each(function(i, _oElem) {
        var oOption = {
            mode: 'inline',
            pk: $(_oElem).data('url').split('&_sHashId=').pop(),
            type: $(_oElem).data('type')
        };
        if(oOption.type == 'select') {
            oOption.source = decodeURIComponent($(_oElem).data('select'));
            oOption.value = $(_oElem).data('value');
        } else if(oOption.type == 'datetime') {
            oOption.format = $(_oElem).data('sFormat');
        }
        $(_oElem).editable(oOption);
    });
    
    //optimize tables
    $('#main-content table.Iiigel-interactive').dataTable({
        bLengthChange: false,
        pageLength: 25,
        language: {
            'sEmptyTable': i18n('nothingfound'),
            'sInfo': i18n('table.displayinfo'),
            'sInfoEmpty': i18n('nothingfound'),
            'sInfoFiltered': '(gefiltert von _MAX_ Einträgen)',
            'sInfoPostFix': '',
            'sInfoThousands': '',
            'sLengthMenu': i18n('table.showentries'),
            'sLoadingRecords': i18n('table.loading'),
            'sProcessing': i18n('table.loading'),
            'sSearch': i18n('table.search'),
            'sZeroRecords': i18n('nothingfound'),
            'oPaginate': {
                'sFirst': '<i class="fa fa-angle-double-left"></i>',
                'sPrevious': '<i class="fa fa-angle-left"></i>',
                'sNext': '<i class="fa fa-angle-right"></i>',
                'sLast': '<i class="fa fa-angle-double-right"></i>'
            },
            'oAria': {
                'sSortAscending': ': ' + i18n('table.sortasc'),
                'sSortDescending': ': ' + i18n('table.sortdesc')
            }
        }
    });
    $('#main-content table tr.Iiigel-click').off('click').on('click', function(_oEvent) {
        _oEvent.preventDefault();
        document.location.href = $(this).data('click');
        return false;
    });
    
    //show register/login window if necessary
    if(document.location.href.indexOf($('base').get(0).href + '?sActivation=') === 0) {
        $('a[data-form="login"]').click();
    }
    
    //toggle dashboard navbar (a) depending on screen size and (b) on click
    function showDashboardNav(_bClick) {
        $('#main-content').css('margin-left', '210px');
        $('#sidebar').css('margin-left', '0px');
        $('#sidebar > ul').show();
        $('#container').removeClass('sidebar-closed');
        if(_bClick) {
            $.get('?c=StaticPage&a=saveDashboardNavStatus&_bHide=0');
        }
    }
    function hideDashboardNav(_bClick) {
        $('#main-content').css('margin-left', '0px');
        $('#sidebar').css('margin-left', '-210px');
        $('#sidebar > ul').hide();
        $('#container').addClass('sidebar-closed');
        if(_bClick) {
            $.get('?c=StaticPage&a=saveDashboardNavStatus&_bHide=1');
        }
    }
    $('.fa-bars').click(function() {
        if($('#sidebar > ul').is(':visible') === true) {
            hideDashboardNav(true);
        } else {
            showDashboardNav(true);
        }
    });
    function responsiveView() {
        if($(window).width() <= 768 || $('#sidebar').data('initial') == 'closed' || $('#sidebar').length == 0) {
            hideDashboardNav(false);
            $('#sidebar').removeData('initial');
        } else {
            showDashboardNav(false);
        }
    }
    $(window).on('load', responsiveView);
    $(window).on('resize', responsiveView);
    
    //tooltips
    $('.tooltips').tooltip();
    
    //select optimization
    $('select').select2({
        minimumResultsForSearch: 5
    });
    
    //navdrops (too many navs on a nav-list)
    $('.nav-tabs').tabdrop();
    
    //back-to-top link
    $('.backtotop').on('click', function(_oEvent) {
        _oEvent.preventDefault();
        $(window).scrollTop(0);
        return false;
    });
    
    //file uploads
    $('input[type="file"]').fileupload({
        dataType: 'json',
        url: '?c=File&a=upload',
        add: function(_oEvent, _oData) {
            for(var i = 0; i < _oData.files.length; i++) {
                $(this).after('<div class="control-label"><div class="progress"><div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%" data-name="' + _oData.files[i].name + '"><span class="sr-only">' + _oData.files[i].name + '</span></div></div></div>');
            }
            if(!$(this).is('[multiple]')) {
                $(this).hide();
            }
            _oData.submit();
        },
        error: function(_oEvent, _sStatus, _oError) {
            bootbox.alert(i18n('error') + '<br />' + _oError.message + '<br /><br />' + _oEvent.responseText);
            $('div[data-name="' + this.originalFiles[0].name + '"]').parents('div.control-label').remove();
            if(!$('#' + this.paramName[0]).is('[multiple]')) {
                $('#' + this.paramName[0]).show();
            }
        },
        done: function(_oEvent, _oData) {
            var oElem = this;
            $.each(_oData.result, function(i, _aResult) {
                $('.progress-bar[data-name="' + _aResult.originalName + '"]')
                    .parent()
                    .replaceWith(
                        '<p class="control-label">' + _aResult.name + ' <a href="' + _aResult.deleteUrl + '" class="btn btn-danger btn-xs tooltips" title="' + i18n('delete') + '"><i class="fa fa-trash-o"></i></a></p>'
                    );
                $('a[href="' + _aResult.deleteUrl + '"]').on('click', (function(_aResult) { return function(_oEvent) {
                    _oEvent.preventDefault();
                    $.get(_aResult.deleteUrl, { sFile: _aResult.name }, (function(_oElem) { return function(_sData) {
                        var oInput = $(_oElem).parents('div.control-label').prevAll('input[type="file"]').get(0);
                        if(parseInt(_sData) == 1) {
                            if(!$(oInput).is('[multiple]')) {
                                $(oInput).show();
                            }
                            $('textarea#' + oInput.id.replace(/-input/, '')).val(
                                $.map($('textarea#' + oInput.id.replace(/-input/, '')).val().split(','), function(_sFile, i) {
                                    return _sFile == _aResult.name ? null : _sFile;
                                }).join(',')
                            );
                            $(_oElem).parents('div.control-label').remove();
                        }
                    } })(this));
                    return false;
                }})(_aResult))
                var aFiles = $('textarea#' + oElem.id.replace(/-input/, '')).val().split(',');
                aFiles.push(_aResult.name);
                $('textarea#' + oElem.id.replace(/-input/, '')).val(
                    $.map(aFiles, function(_sFile, i) { return _sFile == '' ? null : _sFile }).join(',')
                );
            });
            $('.tooltips').tooltip();
        },
        progress: function(_oEvent, _oData) {
            for(var i = 0; i < _oData.files.length; i++) {
                $('.progress-bar[data-name="' + _oData.files[i].name + '"]').css('width', parseInt(_oData.loaded / _oData.total * 100, 10) + '%');
            }
        }
    });
});