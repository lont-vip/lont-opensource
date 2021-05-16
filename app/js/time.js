today = new Date();
var date;
var centry ;
centry="";
if (today.getFullYear()<2000 )
centry = "19" ;
date1 = centry + (today.getFullYear()) + "-" + (today.getMonth() + 1 ) + "-" + today.getDate() + "" ;
date2 = "";
document.write( date1+date2);