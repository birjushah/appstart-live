/**
 * Custom BlockUI Prompt
 * 
 * @dependency JQuery,BlockUI
 * @author Tirth Bodawala
 * @param params
 * @returns {prompt}
 */
function prompt(params){
	this.reference = params.reference != undefined ? params.reference : this; 
	this.element = params.element != undefined ? $(params.element) : $("body");
	this.message = params.message || "Are you sure?";
	this.beforeShow = params.beforeShow != undefined ? params.beforeShow : function(){};
	this.onBlock = params.onBlock != undefined ? params.onBlock : function(){};
	this.onUnblock = params.onUnblock != undefined ? params.onUnblock : function(){};
	this.alternateMessage = false;
	this.buttons = params.buttons || {
			"Yes" : function(){
				this.close();
			},
			"No"  : function(){
				this.close();
			}
		};
	
	this.showMessage = function(message){
		$('.confirm_wrapper').html(message);
	};
	this.showErrorMessage = function(message){
		var errorMessage = '<div class="grid_error">';
    	errorMessage +=  message;
    	errorMessage += "</div>";
    	this.showMessage(errorMessage);
    	return errorMessage;
	};
	this.showSuccessMessage = function(message){
		var successMessage = '<div class="grid_success">';
    	successMessage +=  message;
    	successMessage += "</div>";
    	this.showMessage(successMessage);
    	return successMessage;
	};
	this.showLoadingMessage = function(message){
		var loadingMessage = '<div class="grid_loading">';
    	loadingMessage +=  message;
    	loadingMessage += "</div>";
    	this.showMessage(loadingMessage);
    	return loadingMessage;
	};
	this.close = function(){
		this.element.unblock({
			onUnblock : this.onUnblock
		});
	};
	this.createMessage = function(){
		var message = '<div><div class="confirm_wrapper">';
		
		if(typeof(this.alternateMessage) == 'string'){
			message += this.alternateMessage;
			message += '</div></div>';
		} else {
			message += this.message;
			message += '<br />';
	        message += '<div class="confirm">';
	        message += '</div>';
	        message += '</div></div>';
		}
        message = $(message);
        $.each(this.buttons , function(buttonName, associatedFunction){
            var button = $("<button></button>",{
                'class' : "button",
                'value' : buttonName
            });
            button.html(buttonName);
            $(message).find(".confirm").append(button);
        });
		 return message.html();
	};

	this.bindEvents = function(){
		var self = this;
		$.each(this.buttons,function(buttonName,associatedFunction){
			$(document).off("click","button[value="+ buttonName +"]");
			$(document).on("click","button[value="+ buttonName +"]",function(){
				associatedFunction.apply(self, [self.reference]);
			});
		});
	};
	this.init = function(self){
		$(document).queue(function(next){
			self.beforeShow.call(self);
			var message = self.createMessage();
			self.element.block({
				message: message,
				onBlock: next
			});
		}).queue(function(next){
			self.bindEvents();
			next();
		});
		
	}(this);
}