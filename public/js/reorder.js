$(document).ready(function() {
	window.dragStartIndex = 0;
	//  $(".sortable:eq(1)").insertBefore($(".sortable:eq(0)"));
	$(".sortable").on("mousedown",function(){
		window.dragStartIndex = $(this).index(".sortable") + 1;
		$(this).addClass("drag-start");
	});
	$(".sortable").on("mouseover",function(){
		if(window.dragStartIndex>0 && window.dragStartIndex != ($(this).index(".sortable") + 1)) {
			var fromIndex = window.dragStartIndex - 1;
			var toIndex = $(this).index(".sortable");
			if(fromIndex > toIndex) {
				$(".sortable:eq("+fromIndex+")").insertBefore($(".sortable:eq("+toIndex+")"));
			} else {
				$(".sortable:eq("+fromIndex+")").insertAfter($(".sortable:eq("+toIndex+")"));
			}
			window.dragStartIndex = toIndex+1;
		}
	});
	$("#dataGrid").on("mouseup",function(){window.dragStartIndex = 0; $(".sortable").removeClass("drag-start");});
	$("#dataGrid").on("mousedown",function(){return false;});
});
function serializeOrder(){
	var response = {order:{}};
	$(".sortable").each(function(){
		var index = ($(this).attr("id") + "").replace("index-","");
		response["order"][$(this).index(".sortable")+1] = index;
	});
	return response;
}
document.onselectstart = function() {return false;}