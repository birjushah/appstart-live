function dataTable(params){
	
	var element = false;
	if(!$.isPlainObject(params)){
		throw "Invalid parameters applied to the dataTable function";
	}
	// Get the datatable's Table element
	if( !params.id ){
		throw "Please provide ID of table for dataTables's Grid";
	} else {
		if(typeof(params.id) == "object" && $(params.id).attr("id") ){
			element =  "#" + $(params.id).attr("id");
		} else if (typeof(params.id) == "string" && $("#"+params.id).attr("id")){
			element = $( "#" + params.id).attr("id");
		} else {
			throw "Please provide ID to the table you want to apply dataTables";
		}
	}
	
	//Filter Form
	var filterForm = false;
	if( params.filterForm ){
		if(typeof(params.filterForm) == "object" && $(params.filterForm).attr("id") ){
			filterForm = "#" + $(params.filterForm).attr("id");
		} else if (typeof(params.filterForm) == "string" && $("#"+params.filterForm).attr("id")){
			filterForm = "#" + params.filterForm;
		} else {
			throw "Please provide prper ID for filter form to the table you want to apply dataTables";
		}
	}
	
	// Check for server side enabled and ajax source
	if ( params.bServerSide || params.bServerSide == true ){
		if( !params.sAjaxSource ){
			throw "Invalid sAjaxSoure defined for the datagrid"; 
		}
	} 
	
	if ( !params.aoColumns ){
		throw "No aoColumns define for the grid";
	}

	
	var fnServerDataSuccess = params.fnServerDataSuccess || undefined;
	var fnServerData = function( sSource, aoData, fnCallback){
    	var blockElement = $(element).parent();
        $(document).queue(function(next){ 
            blockElement.block({
                message: '<div class="grid_loading">Loading Grid Data...</div>',
            	onBlock: next
            });
		}).queue(function(next){
			$.ajax({
                type: "POST",
                url : sSource,
                data: aoData,
                cache: false,
                dataType: "json",
                success: function(json){
                	fnCallback(json);
                	if(fnServerDataSuccess != undefined){
                		fnServerDataSuccess(json);
                	}
                }
            }).complete(next);
		}).queue(function(next){
			var charWidth = params.charLimit;
			if(charWidth != undefined) {
				for(i=0;i<charWidth.length;i++) {
					if(charWidth[i]<=0) continue;
					$(element + " tbody tr").each(function(){
						var content = $(this).find("td:nth-child("+(i+1)+")").html();
						if(content!= undefined && content.length > charWidth[i])
						{
							var exerpt = content.substr(0,charWidth[i]);
							$(this).find("td:nth-child("+(i+1)+")").html('<div class="tip"><div class="less">'+exerpt+ ' ...</div><div class="more">' + content + '</div></div>');
						}
					});
					$(element + " .tip").on("mouseover",function(){
						$(this).find(".more").css("position","absolute");
						$(this).find(".more").css("backgroundColor","#fdf9d8");
						$(this).find(".more").css("border","1px double #f59507");
						$(this).find(".more").css("padding","5px");
						$(this).find(".more").css("color","#000");
					    $(this).find(".more").show();
					});
					$(element + " .tip").on("mouseout",function(){
					    $(this).find(".more").hide();
					});
					$(element + " .more").hide();
					$(this).find(".less").css("display","inline");
				}
			}
			blockElement.unblock({
				onUnblock: next
			});
		});
    };
	
	var gridObject = {
    	"aoColumns": params.aoColumns,
    	"sDom" : 'rt<"bottom"ipl>',
    	"bAutoWidth": false,
    	"oPagination" : params.oPagination ? params.oPagination : undefined,
    	"sPaginationType" : params.sPaginationType ? params.sPaginationType : "full_numbers",
    	"oLanguage": {"sUrl": window.dataTableLangUrl},
		"bPaginate" : params.bPaginate ? params.bPaginate : true,
		"bDestroy": params.bDestroy ? params.bDestroy : true,	
        "bProcessing": params.bProcessing ? params.bProcessing : false,
        "bServerSide": params.bServerSide ? params.bServerSide : true,
        "bSortClasses": params.bSortClasses ? params.bSortClasses : false,
        "bLengthChange": params.bLengthChange ? params.bLengthChange : true,
        "sAjaxSource": params.sAjaxSource,
        "fnServerData": fnServerData,
        "fnServerParams": function ( aoData ) {
        	if(filterForm){
        		tmData= $(filterForm).serializeArray();
               	$(tmData).each(function(){
                   	name= "search_" + $(this).attr("name");
        			value= $(this).attr("value");                
                   	aoData.push( { "name": name, "value": value } );
    			});
        	}
		},
		"fnDrawCallback":function(){
			$(element+"_length").show();
			$(element + "_filter").hide();
		}
	};
	
	if (params.aaSorting != undefined ){
		gridObject.aaSorting = params.aaSorting;
	}
	return $(element).dataTable(gridObject);
}