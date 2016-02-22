function intlinkPrompt(){
	var chapters = document.getElementsByName("chapter");
	var help ="" ;
	for (var i=0;i<document.getElementsByName("chapter").length;i++){
		help = help +'<li><a>'+i+'. '+chapters[i].value+'</a></li>';
	}

	bootbox.dialog({
		title: i18n('intlink.url'),
		message:
				'<script language="javascript" type="text/javascript">'+
					'$(".dropdown-menu li a").click(function(){'+
						'var selText = $(this).text();'+
						'if ((isNaN(selText.substr(0,(selText).indexOf(".")))==false)&&(selText.substr(0,(selText).indexOf("."))!="")){'+
							'$(this).parents(\'.btn-group\').find(\'.dropdown-toggle\').html(selText+\' <span class="caret"></span>\');'+
						'}'+
					'});'+
				'</script>'+
				
				'<form>' +	
					'<div class="well carousel-search hidden-sm">' +
						'<div class="btn-group"> <a class="btn btn-default dropdown-toggle btn-select" data-toggle="dropdown" href="#">'+i18n('intlink.which')+' <span class="caret"></span></a>' +
							'<ul class="dropdown-menu">' +
								help +
							'</ul>' +
						'</div>' +
					'</div>' +
				'</form>' ,
		buttons: {
			success: {
				label: "Save",
				className: "btn-success",
				callback: function () {
					var x =(($('.btn-select').text()).substr(0,($('.btn-select').text()).indexOf(".")));
					var strhashid = (chapters[x].id).substring(8,(chapters[x].id).length);
					writeText("[link]{href=\"learn/"+strhashid+"\";}"+chapters[x].value+"[/link]");
				}
			}
		}
	});
	
}