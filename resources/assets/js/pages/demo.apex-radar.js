(()=>{var e=["#727cf5"];(a=$("#basic-radar").data("colors"))&&(e=a.split(","));var r={chart:{height:350,type:"radar"},series:[{name:"Series 1",data:[80,50,30,40,100,20]}],colors:e,labels:["January","February","March","April","May","June"]};new ApexCharts(document.querySelector("#basic-radar"),r).render();e=["#FF4560"];(a=$("#radar-polygon").data("colors"))&&(e=a.split(","));r={chart:{height:350,type:"radar"},series:[{name:"Series 1",data:[20,100,40,30,50,80,33]}],labels:["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],plotOptions:{radar:{size:140}},colors:e,markers:{size:4,colors:["#fff"],strokeColor:e,strokeWidth:2},tooltip:{y:{formatter:function(e){return e}}},yaxis:{tickAmount:7,labels:{formatter:function(e,r){return r%2==0?e:""}}}};new ApexCharts(document.querySelector("#radar-polygon"),r).render();var a;e=["#727cf5","#02a8b5","#fd7e14"];(a=$("#radar-multiple-series").data("colors"))&&(e=a.split(","));r={chart:{height:350,type:"radar"},series:[{name:"Series 1",data:[80,50,30,40,100,20]},{name:"Series 2",data:[20,30,40,80,20,80]},{name:"Series 3",data:[44,76,78,13,43,10]}],stroke:{width:0},fill:{opacity:.4},markers:{size:0},legend:{offsetY:-10},colors:e,labels:["2011","2012","2013","2014","2015","2016"]};new ApexCharts(document.querySelector("#radar-multiple-series"),r).render()})();