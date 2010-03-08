//java -jar yuicompressor-2.4.2.jar --type js -o functions.js --line-break 0 --nomunge functions-orig.js
function selectChange(control,controlToPopulate){if($(controlToPopulate).children!=null){$(controlToPopulate).innerHTML=""
}var select=document.createElement("select");
select.setAttribute("name","choice[]");
var myEle=document.createElement("option");
theText=document.createTextNode("----");
myEle.appendChild(theText);
select.appendChild(myEle);
if(arrItems.get(control.value)==null){$(controlToPopulate).innerHTML="";
return
}arrItems.get($(control).value).each(function(pair){myEle=document.createElement("option");
myEle.setAttribute("value",pair.key);
var txt=document.createTextNode(pair.value);
myEle.appendChild(txt);
select.appendChild(myEle)
});
$(controlToPopulate).appendChild(select)
select.focus()
};