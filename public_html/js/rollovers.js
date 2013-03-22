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

$(function() {
	$("input.yes").mouseover(function(){
		$(this).attr('value','No');
		$(this).removeClass('yes')
		$(this).addClass('no')
	});
	$("input.yes").mouseout(function(){
		$(this).attr('value','Yes');
		$(this).removeClass('no')
		$(this).addClass('yes')
	});	
	$("input.no").mouseover(function(){
		$(this).attr('value','Yes');
		$(this).removeClass('no')
		$(this).addClass('yes')
	});
	$("input.no").mouseout(function(){
		$(this).attr('value','No');
		$(this).removeClass('yes')
		$(this).addClass('no')
	});		
});