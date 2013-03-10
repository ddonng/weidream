$("#form_login").bind('keyup',function(event){
	if(event.keyCode==13){
		$("#sbtn").click();
		return false;
	}
});

$("#birthday").datepicker(  
 {
	yearRange: '1950:1993',
	defaultDate: '1986-12-29',
	showMonthAfterYear: true, // 月在年之后显示  
	changeMonth: true,   // 允许选择月份  
	changeYear: true,   // 允许选择年份  
	dateFormat:'yy-mm-dd',  // 设置日期格式  
 });

 $("#start_work_time").datepicker(  
 {
	yearRange: '1950:2013',
	defaultDate: '2010-08-01',
	showMonthAfterYear: true, // 月在年之后显示  
	changeMonth: true,   // 允许选择月份  
	changeYear: true,   // 允许选择年份  
	dateFormat:'yy-mm-dd',  // 设置日期格式  
 });