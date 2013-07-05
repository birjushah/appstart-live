(function($){
	$.fn.editor = function(option){
		var $element = $(this);
		var $id = $element.attr("id");
		// Render Add Content Link 
		$element.parent().append('<a id="'+$id+'-preview" href="javascript:void(0);">Add Contnet</a>');
		$("#" + $id+'-preview').live("click",function(){
			// Display Editor
			var oEditor = $('#' + $id).data('liveEdit');
			var docHeight = $(document).height();
			var docWidth = $(document).width();

			$("#" + $id + '-dialog').css("position","absolute");
			$("#" + $id + '-dialog').css("width",docWidth);
			$("#" + $id + '-dialog').css("height",docHeight);
			$("#" + $id + '-dialog').css("top",0);
			$("#" + $id + '-dialog').css("left",0);

			$('#'+$id+'-editor').css("position","absolute");
			$('#'+$id+'-editor').css("width",oEditor.settings.width);
			$('#'+$id+'-editor').css("height",oEditor.settings.height);
			$('#'+$id+'-editor').css("top","50%");
			$('#'+$id+'-editor').css("left","50%");
			
			$('#'+$id+'-editor').css("margin-top",((oEditor.settings.height/2)*-1) + "px");
			$('#'+$id+'-editor').css("margin-left",((oEditor.settings.width/2)*-1) + "px");
			
			//$("#cntContainer_editorobj1").attr("width","320");
			//$("#cntContainer_editorobj1").parent().attr("align","center");
			if(option.innerWidth) {
				$("table[id^=cntContainer_editorobj]").attr("width",option.innerWidth);
				$("table[id^=cntContainer_editorobj]").parent().css("background-color","#ccc");
				$("table[id^=cntContainer_editorobj]").parent().attr("align","center");
			}
			$("#" + $id + '-dialog').show();
			$(document).scrollTop((($(document).height()-$("#" + $id + "-editor").height())/2));
		});

		// Create Editor Dialog
		$element.parent().append('<div id="'+$id+'-dialog" style="display:none;"></div>');
		$("#" + $id+'-dialog').append('<div id="'+$id+'-overlay" class="blocked"></div>');
		$("#" + $id+'-dialog').append('<div id="'+$id+'-editor" style="z-index: 1005;"></div>');

		$("#" + $id+'-editor').append('<div style="width: '+option.width+'px;" class="ui-dialog ui-widget ui-widget-content ui-corner-all" tabindex="-1" role="dialog"></div>');
		$("#" + $id+'-editor').find('.ui-dialog').append('<div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix"></div>');

		$("#" + $id+'-editor').find('.ui-dialog-titlebar').append('<span class="ui-dialog-title" id="ui-dialog-title-lstLogo">&nbsp;</span>');
		$("#" + $id+'-editor').find('.ui-dialog-titlebar').append('<a href="javascript:void(0);" class="ui-dialog-titlebar-close ui-corner-all" role="button"></a>');
		$("#" + $id+'-editor').find('a').append('<span id="'+ $id + '-close" class="ui-icon ui-icon-closethick">close</span>');
		$("#" + $id+'-editor').find('.ui-dialog').append("<div id='"+ $id +"-editbox'></div>");
		// Append Text Area to Dialog
		//$(this).detach();
		$(this).appendTo("#" + $id +'-editbox');
		$(this).liveEdit(option);
		$(this).data('liveEdit').startedit();

		// Setup DialogClose event
		$('#'+ $id + '-close').live("click",function(){
			$('#' + $id).data('liveEdit').finishedit();
			$("#" + $id + '-dialog').hide();
		});

		
	};
})(jQuery);