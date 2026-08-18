<?php
// Jogo da Cobrinha - PHP + JavaScript
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jogo da Cobrinha</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;background:#101820;color:#fff;display:flex;justify-content:center;align-items:center;min-height:100vh}
.container{width:min(700px,95vw);text-align:center}
h1{margin-bottom:8px}
.info{display:flex;justify-content:center;gap:25px;margin:15px 0;font-size:18px}
canvas{background:#07120b;border:3px solid #39d353;border-radius:10px;max-width:100%;height:auto}
button{border:0;border-radius:8px;padding:11px 18px;font-weight:bold;cursor:pointer;margin:5px}
#start{background:#39d353;color:#07120b}
#report{background:#4da3ff;color:white;text-decoration:none;display:inline-block}
.controls{margin-top:10px}
.controls button{width:55px;height:45px}
.status{min-height:24px;color:#c8d6e5}
</style>
</head>
<body>
<div class="container">
<h1>🐍 Jogo da Cobrinha</h1>
<div class="info">
<div>Pontos: <strong id="score">0</strong></div>
<div>Recorde: <strong id="best">0</strong></div>
</div>
<canvas id="game" width="500" height="500"></canvas>
<div class="status" id="status">Clique em Iniciar para jogar.</div>
<button id="start">Iniciar / Reiniciar</button>
<a id="report" href="report.php" target="_blank">📄 Relatórios das partidas</a>
<div class="controls">
<div><button data-dir="up">▲</button></div>
<div><button data-dir="left">◀</button><button data-dir="down">▼</button><button data-dir="right">▶</button></div>
</div>
<p>Use as setas do teclado ou os botões. A partida é registrada automaticamente quando termina.</p>
</div>

<script>
const canvas=document.getElementById('game');
const ctx=canvas.getContext('2d');
const scoreEl=document.getElementById('score');
const bestEl=document.getElementById('best');
const statusEl=document.getElementById('status');

const grid=25, cell=20;
let snake, food, dir, nextDir, score, running=false, timer, startedAt;

function reset(){
    snake=[{x:12,y:12},{x:11,y:12},{x:10,y:12}];
    food=randomFood();
    dir={x:1,y:0}; nextDir={x:1,y:0};
    score=0; scoreEl.textContent=score;
    draw();
}
function randomFood(){
    let p;
    do { p={x:Math.floor(Math.random()*grid),y:Math.floor(Math.random()*grid)}; }
    while(snake && snake.some(s=>s.x===p.x&&s.y===p.y));
    return p;
}
function draw(){
    ctx.fillStyle='#07120b'; ctx.fillRect(0,0,canvas.width,canvas.height);
    ctx.strokeStyle='rgba(57,211,83,.08)';
    for(let i=0;i<=grid;i++){ctx.beginPath();ctx.moveTo(i*cell,0);ctx.lineTo(i*cell,canvas.height);ctx.stroke();ctx.beginPath();ctx.moveTo(0,i*cell);ctx.lineTo(canvas.width,i*cell);ctx.stroke();}
    ctx.fillStyle='#ff4757'; ctx.beginPath(); ctx.arc(food.x*cell+10,food.y*cell+10,8,0,Math.PI*2);ctx.fill();
    snake.forEach((s,i)=>{ctx.fillStyle=i===0?'#7bed9f':'#39d353';ctx.fillRect(s.x*cell+2,s.y*cell+2,16,16);});
}
function setDirection(x,y){
    if(!running)return;
    if(x===-dir.x && y===-dir.y)return;
    nextDir={x,y};
}
function step(){
    dir=nextDir;
    const head={x:snake[0].x+dir.x,y:snake[0].y+dir.y};
    if(head.x<0||head.x>=grid||head.y<0||head.y>=grid||snake.some(s=>s.x===head.x&&s.y===head.y)){
        endGame(); return;
    }
    snake.unshift(head);
    if(head.x===food.x&&head.y===food.y){
        score++;
        scoreEl.textContent=score;
        food=randomFood();
    }else snake.pop();
    draw();
}
function startGame(){
    clearInterval(timer); reset(); running=true; startedAt=Date.now();
    statusEl.textContent='Jogo em andamento...';
    timer=setInterval(step,110);
}
async function endGame(){
    running=false; clearInterval(timer);
    const duration=Math.max(1,Math.round((Date.now()-startedAt)/1000));
    statusEl.textContent=`Fim de jogo! Pontuação: ${score}. Salvando partida...`;
    try{
        const res=await fetch('save_game.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({score:score,duration:duration})
        });
        const data=await res.json();
        if(data.success){
            bestEl.textContent=data.best;
            statusEl.textContent=`Partida registrada! Pontuação: ${score}.`;
        }else statusEl.textContent='Não foi possível registrar a partida.';
    }catch(e){statusEl.textContent='Erro ao salvar a partida.';}
}
document.getElementById('start').onclick=startGame;
document.addEventListener('keydown',e=>{
    const map={ArrowUp:[0,-1],ArrowDown:[0,1],ArrowLeft:[-1,0],ArrowRight:[1,0],w:[0,-1],s:[0,1],a:[-1,0],d:[1,0]};
    if(map[e.key]){e.preventDefault();setDirection(...map[e.key]);}
});
document.querySelectorAll('[data-dir]').forEach(b=>b.onclick=()=>{
    const d={up:[0,-1],down:[0,1],left:[-1,0],right:[1,0]}[b.dataset.dir];
    setDirection(...d);
});
reset();

async function loadBest(){
    try{
        const r=await fetch('save_game.php?best=1');
        const d=await r.json();
        bestEl.textContent=d.best||0;
    }catch(e){}
}
loadBest();
</script>
</body>
</html>
