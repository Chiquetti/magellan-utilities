(function(){
  var reduce=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var layers=[].slice.call(document.querySelectorAll('.layer'));
  var hero=document.getElementById('top'), nav=document.getElementById('nav');
  var rail=document.getElementById('rail'), fill=document.getElementById('railFill'), val=document.getElementById('railVal');
  var bore=document.getElementById('borePath');
  var steps=[].slice.call(document.querySelectorAll('.step'));
  var scopeName=document.getElementById('scopeName'), scopeHead=document.getElementById('scopeHead'), scopeBore=document.getElementById('scopeBore');
  var MAXDEPTH=10, ticking=false;

  var boreLen=0;
  if(bore && bore.getTotalLength){
    boreLen=bore.getTotalLength();
    bore.style.strokeDasharray=boreLen;
    bore.style.strokeDashoffset=reduce?0:boreLen;
  }

  function frame(){
    ticking=false;
    var y=window.scrollY||window.pageYOffset;
    var hh=hero.offsetHeight-window.innerHeight;
    var p=hh>0?Math.min(1,Math.max(0,y/hh)):1;
    if(!reduce){
      for(var i=0;i<layers.length;i++){
        var r=parseFloat(layers[i].getAttribute('data-rate'))||0;
        layers[i].style.transform='translate3d(0,'+(y*r)+'px,0)';
      }
      if(boreLen) bore.style.strokeDashoffset=String(boreLen-(boreLen*p));
    }
    nav.classList.toggle('stuck', y>24);
    var doc=document.documentElement.scrollHeight-window.innerHeight;
    var dp=doc>0?Math.min(1,Math.max(0,y/doc)):0;
    rail.classList.toggle('on', y>window.innerHeight*0.5);
    fill.style.height=(dp*100)+'%';
    val.textContent='-'+Math.round(dp*MAXDEPTH)+' ft';
  }
  function onScroll(){ if(!ticking){ ticking=true; requestAnimationFrame(frame); } }
  window.addEventListener('scroll',onScroll,{passive:true});
  window.addEventListener('resize',onScroll);
  frame();

  if('IntersectionObserver' in window && steps.length){
    var len=scopeBore?scopeBore.getTotalLength():0;
    var io=new IntersectionObserver(function(entries){
      entries.forEach(function(e){
        if(!e.isIntersecting) return;
        steps.forEach(function(s){ s.classList.remove('on'); });
        e.target.classList.add('on');
        if(scopeName) scopeName.textContent=e.target.getAttribute('data-scope');
        if(scopeHead && len){
          var idx=steps.indexOf(e.target);
          var pt=scopeBore.getPointAtLength(len*((idx+0.5)/steps.length));
          scopeHead.setAttribute('cx',pt.x); scopeHead.setAttribute('cy',pt.y);
        }
      });
    },{rootMargin:'-45% 0px -45% 0px'});
    steps.forEach(function(s){ io.observe(s); });
  }
})();
