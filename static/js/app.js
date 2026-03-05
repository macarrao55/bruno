document.addEventListener('keydown', (e)=>{
  if(e.key==='F11'){e.preventDefault();document.documentElement.requestFullscreen?.();}
});
