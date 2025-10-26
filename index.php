<?php
// index.php — Mobile Snake Game with Universal Leaderboard (Text File)
// Stores top 10 scores in leaderboard.json

$board_file = __DIR__ . '/leaderboard.json';
if (!file_exists($board_file)) file_put_contents($board_file, json_encode([]));

function getLeaderboard($file){
  $data = json_decode(@file_get_contents($file), true);
  if(!$data) $data = [];
  usort($data, fn($a,$b)=>$b['score']<=>$a['score']);
  return array_slice($data,0,10);
}

if(isset($_GET['action'])){
  header('Content-Type: application/json');
  $action=$_GET['action'];
  if($action==='get'){ echo json_encode(getLeaderboard($board_file)); exit; }
  if($action==='save' && isset($_POST['name'],$_POST['score'])){
    $name=trim($_POST['name']); $score=intval($_POST['score']);
    $data=json_decode(@file_get_contents($board_file),true) ?: [];
    $data[]=['name'=>$name,'score'=>$score,'time'=>time()];
    file_put_contents($board_file,json_encode($data));
    echo json_encode(['saved'=>true]); exit;
  }
  echo json_encode(['error'=>'invalid_action']); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no" />
<title>Snake Game — CM TECH</title>
<style>
:root{--bg:#0b1220;--panel:#101b2e;--accent:#22c55e;--muted:#a0a8c0;--card:#131f34}
*{box-sizing:border-box;font-family:Inter,system-ui,Segoe UI,Roboto,sans-serif}
body{margin:0;background:linear-gradient(180deg,var(--bg),#061018);color:#e6eef8;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:10px}
.wrap{width:100%;max-width:520px;padding:16px}
header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
header h1{font-size:20px;margin:0;color:var(--accent)}
.panel{background:var(--panel);padding:14px;border-radius:14px;box-shadow:0 4px 18px rgba(0,0,0,0.4)}
canvas{width:100%;border-radius:8px;background:#0c1527;display:block}
.info{display:flex;justify-content:space-between;margin-top:8px}
.badge{background:rgba(255,255,255,0.05);padding:6px 10px;border-radius:999px;font-weight:600}
.btn{background:var(--card);border:1px solid rgba(255,255,255,0.05);padding:8px 12px;border-radius:8px;color:#fff;font-weight:600;cursor:pointer}
.btn:active{transform:translateY(1px)}
.touch-pad{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;max-width:300px;margin:10px auto 0}
.pad-btn{background:rgba(255,255,255,0.04);padding:14px;border-radius:8px;text-align:center;font-size:18px;user-select:none}
.leaderboard{margin-top:18px}
.leaderboard h2{text-align:center;margin:8px 0;color:var(--accent)}
.leader-list{display:flex;flex-direction:column;gap:8px;margin-top:10px}
.lb-card{background:var(--card);border-radius:12px;padding:10px 12px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 8px rgba(0,0,0,0.3)}
.lb-rank{font-weight:700;color:var(--accent);font-size:16px}
.lb-name{font-weight:600}
.lb-score{color:#ffd43b;font-weight:700}
footer{text-align:center;font-size:12px;color:var(--muted);margin-top:14px}
.name-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.9);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:999}
.name-overlay h2{color:#fff;margin-bottom:10px}
.name-overlay input{padding:10px;border-radius:8px;border:none;width:80%;max-width:300px;text-align:center;font-size:16px}
.name-overlay button{margin-top:10px}
</style>
</head>
<body>
<div class="wrap">
<header>
  <h1>Snake Game — CM TECH</h1>
  <span class="badge" id="score">Score: 0</span>
</header>
<div class="panel">
  <canvas id="canvas" width="400" height="400"></canvas>
  <div class="info">
    <span class="badge" id="speed">Medium</span>
    <button class="btn" id="restart">Restart</button>
  </div>
  <div class="touch-pad">
    <div></div>
    <div class="pad-btn" data-dir="up">▲</div>
    <div></div>
    <div class="pad-btn" data-dir="left">◀</div>
    <div class="pad-btn" data-dir="down">▼</div>
    <div class="pad-btn" data-dir="right">▶</div>
  </div>
</div>
<div class="leaderboard panel">
  <h2>🏆 Top 10 Players</h2>
  <div class="leader-list" id="leaderList"></div>
</div>
<footer>Developed by <b>APARUP SARKAR</b> | CM TECH</footer>
</div>
<div class="name-overlay" id="nameOverlay">
  <h2>Enter Your Name</h2>
  <input type="text" id="playerName" placeholder="Your Name" maxlength="20" />
  <button class="btn" id="startBtn">Start Game</button>
</div>
<script>
const c=document.getElementById('canvas');const ctx=c.getContext('2d');let cw=c.width,ch=c.height;let grid=20;let cs=cw/grid;let snake=[],food={},dir={x:1,y:0},score=0,speed=100,loopTimer=null,playerName="";

function newGame(){snake=[{x:8,y:8},{x:7,y:8},{x:6,y:8}];dir={x:1,y:0};placeFood();score=0;updateUI();}
function placeFood(){food.x=Math.floor(Math.random()*grid);food.y=Math.floor(Math.random()*grid);if(snake.some(s=>s.x===food.x&&s.y===food.y))placeFood();}
function step(){const head={x:snake[0].x+dir.x,y:snake[0].y+dir.y};head.x=(head.x+grid)%grid;head.y=(head.y+grid)%grid;if(snake.some((s,i)=>i>0&&s.x===head.x&&s.y===head.y)){gameOver();return;}snake.unshift(head);if(head.x===food.x&&head.y===food.y){score++;updateUI();placeFood();}else snake.pop();draw();}
function draw(){ctx.fillStyle='#081220';ctx.fillRect(0,0,cw,ch);ctx.fillStyle='#ff3b30';ctx.fillRect(food.x*cs+2,food.y*cs+2,cs-4,cs-4);for(let i=0;i<snake.length;i++){const s=snake[i];ctx.fillStyle=i===0?'#22c55e':'#0ea5a4';ctx.fillRect(s.x*cs+1,s.y*cs+1,cs-2,cs-2);} }
function gameOver(){clearInterval(loopTimer);saveScore();alert(`Game Over! Score: ${score}`);fetchLeaderboard();}
function updateUI(){document.getElementById('score').textContent='Score: '+score;}
function restartLoop(){if(loopTimer)clearInterval(loopTimer);loopTimer=setInterval(step,speed);}

// Controls
window.addEventListener('keydown',e=>{const k=e.key;if(k==='ArrowUp'&&dir.y!==1)dir={x:0,y:-1};if(k==='ArrowDown'&&dir.y!==-1)dir={x:0,y:1};if(k==='ArrowLeft'&&dir.x!==1)dir={x:-1,y:0};if(k==='ArrowRight'&&dir.x!==-1)dir={x:1,y:0};});
document.querySelectorAll('.pad-btn').forEach(b=>b.addEventListener('click',()=>{const d=b.dataset.dir;if(d==='up'&&dir.y!==1)dir={x:0,y:-1};if(d==='down'&&dir.y!==-1)dir={x:0,y:1};if(d==='left'&&dir.x!==1)dir={x:-1,y:0};if(d==='right'&&dir.x!==-1)dir={x:1,y:0};}));

function saveScore(){if(!playerName)return;const fd=new FormData();fd.append('name',playerName);fd.append('score',score);fetch('?action=save',{method:'POST',body:fd}).then(()=>fetchLeaderboard());}
function fetchLeaderboard(){fetch('?action=get').then(r=>r.json()).then(list=>{const el=document.getElementById('leaderList');el.innerHTML='';list.forEach((p,i)=>{el.innerHTML+=`<div class=\"lb-card\"><span class=\"lb-rank\">#${i+1}</span><span class=\"lb-name\">${p.name}</span><span class=\"lb-score\">${p.score}</span></div>`});});}

// Name prompt overlay
const overlay=document.getElementById('nameOverlay');document.getElementById('startBtn').addEventListener('click',()=>{const n=document.getElementById('playerName').value.trim();if(!n)return alert('Enter name');playerName=n;overlay.style.display='none';newGame();restartLoop();fetchLeaderboard();});

document.getElementById('restart').addEventListener('click',()=>{newGame();restartLoop();});
fetchLeaderboard();
</script>
</body>
</html>

