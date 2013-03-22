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

$(document).ready(function(){
	$('.checkbox').click(function (){
		var thisCheck = $(this);
		if (thisCheck.is (':checked')){
			$(this).parent().addClass('checked');
		} else {
			$(this).parent().removeClass('checked');
		}
	});
	
	$('.users.checkbox').click(function (){
		var thisCheck = $(this);
		if (thisCheck.is (':checked')){
			$(this).parent().parent().addClass('hide');
			$(this).next('.icon').addClass('hide');
		} else {
			$(this).parent().parent().removeClass('hide');
			$(this).next('.icon').removeClass('hide');
		}
	});	
	
});
