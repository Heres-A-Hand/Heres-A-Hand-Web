/**
Copyright 2011-2013 Here's A Hand Limited

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.

You may obtain a copy of the License at
http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
**/

var SubjectToLongWarning;


$(document).ready(function(){
	$('#SendMessageExtras').hide();
	$('#SendMessageText').keyup(newRequestSummaryChanged);
	$('#SendMessageText').change(newRequestSummaryChanged);
	$('#SendMessageText').focus(newRequestSummaryChanged);
	$('form#SendMessageForm').submit(function() {
		var val = $('#SendMessageText').val();
		var left = 140 - val.length;
		if (left < 0) {
			alert('You have used to many characters - please remove some!');
			return false;
		} else {
			return true;
		}
	});
	
	$('#SendMessageDetails').hide();
	$('#SendMessageDetailsLink').show();
	$('#SendMessageDetailsLink, #SendMessageDetailsLink2').click(function() {
		$('#SendMessageDetails').show();
		$('#SendMessageDetailsLink').hide();
		$('#SendMessageDetails textarea').focus();
	});
	

	SubjectToLongWarning = $('#SubjectToLongWarning');
});

function newRequestSummaryChanged() {
	var field = $('#SendMessageText');
	var val = field.val();

	$('#SendMessageExtras').show();
	var left = 140 - val.length;
	$('#SendMessageTextLength').html(left);
	
	if (left < 0) {
		SubjectToLongWarning.show();
	} else {
		SubjectToLongWarning.hide();
	}
}
