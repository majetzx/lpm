var t=null, lpmq_last='';
function lpmSearchInit(max){
	var lpmq=$('#lpmq');
	lpmq.keypress(lpmKeypress).keydown(lpmKeypress);
	lpmq.blur(lpmHideDelayed);
	var p=lpmq.position();
	var lpms=$('#lpms');
	lpms.css('top',p.bottom+'px');
	lpms.css('left',p.left+'px');
	lpms.css('width',lpmq.width()+'px');
}
function lpmKeypress(e){
if(e.keyCode!=0)console.log('>lpmKeypress()',e.keyCode);
	var lpms=$('#lpms');
	if(e.keyCode==27)/*Escape*/
		lpmHide();
	else{
		if(t)
			window.clearTimeout(t);
		t=window.setTimeout('lpmSearch()',200);
	}
}
function lpmHide(){
	$('#lpms').css('display','none');
}
function lpmHideDelayed(){
	window.setTimeout('lpmHide()',400);
}
function lpmSearch(){
	var lpmq=$('#lpmq').val();
	if(lpmq_last!=lpmq){
		if(lpmq==''){
			lpmHide();
			return false;
		}
		$.get('search.php',lpmq+';json',lpmChange);
		lpmq_last=lpmq;
	}
}
function lpmChange(d){
	var lpms=$('#lpms');
	lpms.empty();
	if(d!=''){
		var html='<ul>';
		if(d.results.length==0)
			html+='<li><em>No result</em></li>';
		else{
			for(var i=0; i<d.results.length; i++)
				html+='<li><a href="'+d.results[i].url+'" class="type_'+d.results[i].type+'" title="'+d.results[i].title+'">'+d.results[i].name+'</a></li>';
		}
		html+='</ul>';
		lpms.append(html);
		lpms.css('display','block');
	}
	else
		lpms.css('display','none');
}