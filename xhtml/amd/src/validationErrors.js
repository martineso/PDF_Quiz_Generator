/**
	Javascript modal to show an error if there are not supported questions in the category bank
	* @author Martin Kontilov 
*/


define(['jquery', 'core/modal_factory', 'core/modal_events', 'core/templates'], function($, ModalFactory, ModalEvents, Templates) {

    return {
        init: function(notSupportedArray) {

        		var notSupportedString = "";
        		console.log(notSupportedArray);
        		for(var property in notSupportedArray) {
        			
        			if(notSupportedArray.hasOwnProperty(property)) {
        				notSupportedString += String(notSupportedArray[property]) + "</br>";

        			}
        		}

        		console.log(notSupportedString)
            var trigger = $('#modal');
            
            ModalFactory.create({
							type: ModalFactory.types.CONFIRM,
					    title: 'Error!',
					    body: "The following questions are not supported: </br>" 
					    				+ notSupportedString + "</br>" 
					    				+ "Do you want to go back?" + "</br>", 
            }, trigger).done(function(modal){
            	modal.show();
            })
        }
    }
});
