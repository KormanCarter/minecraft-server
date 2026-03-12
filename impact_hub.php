<?php
declare(strict_types=1);
// ─── VoxelCraft: A Minecraft-style block building game ──────────────
// Single-file PHP + HTML5 Canvas. No external libraries.
// Place blocks, break blocks, switch materials, explore the world.
$version = '1.0.0';
$seed = isset($_GET['seed']) ? (int)$_GET['seed'] : mt_rand(1000, 9999);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="description" content="VoxelCraft — a Minecraft-style isometric block building game built with PHP and HTML5 Canvas."/>
<title>VoxelCraft — Block Builder</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: #1a1a2e;
    overflow: hidden;
    font-family: 'Segoe UI', system-ui, sans-serif;
    user-select: none;
    -webkit-user-select: none;
  }

  canvas {
    display: block;
    cursor: crosshair;
  }

  /* ── HUD Overlay ──────────────────────────── */
  .hud {
    position: fixed;
    bottom: 0;
    left: 0; right: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding-bottom: 16px;
    pointer-events: none;
    z-index: 10;
  }

  .hotbar {
    display: flex;
    gap: 4px;
    pointer-events: auto;
    background: rgba(0,0,0,0.55);
    padding: 6px 8px;
    border-radius: 10px;
    border: 2px solid rgba(255,255,255,0.15);
    backdrop-filter: blur(6px);
  }

  .slot {
    width: 52px; height: 52px;
    border-radius: 6px;
    border: 2px solid rgba(255,255,255,0.12);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: border-color 0.15s, transform 0.15s;
    position: relative;
  }

  .slot:hover { border-color: rgba(255,255,255,0.4); }
  .slot.active { border-color: #5eead4; transform: scale(1.1); box-shadow: 0 0 12px rgba(94,234,212,0.4); }

  .slot canvas {
    width: 32px; height: 32px;
    cursor: pointer;
    image-rendering: pixelated;
  }

  .slot .key {
    position: absolute;
    top: 2px; right: 4px;
    font-size: 9px;
    color: rgba(255,255,255,0.5);
    font-weight: 700;
  }

  .slot .name {
    font-size: 7px;
    color: rgba(255,255,255,0.65);
    margin-top: 1px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  /* ── Status Bars ──────────────────────────── */
  .status-row {
    display: flex;
    gap: 12px;
    align-items: center;
  }

  .stat-bar {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #ccc;
  }

  .bar-track {
    width: 100px; height: 8px;
    background: rgba(0,0,0,0.5);
    border-radius: 4px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.1);
  }

  .bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s;
  }

  /* ── Info Panel ───────────────────────────── */
  .info {
    position: fixed;
    top: 12px; left: 12px;
    background: rgba(0,0,0,0.6);
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 12px;
    color: #94a3b8;
    line-height: 1.6;
    border: 1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(6px);
    z-index: 10;
    max-width: 220px;
  }

  .info strong { color: #5eead4; }
  .info .title { font-size: 16px; font-weight: 700; color: #e2e8f0; margin-bottom: 4px; }

  .crosshair {
    position: fixed;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 24px; height: 24px;
    z-index: 5;
    pointer-events: none;
  }

  .crosshair::before, .crosshair::after {
    content: '';
    position: absolute;
    background: rgba(255,255,255,0.6);
  }

  .crosshair::before { width: 2px; height: 24px; left: 11px; top: 0; }
  .crosshair::after  { width: 24px; height: 2px; top: 11px; left: 0; }

  /* ── Day/Night Sky ────────────────────────── */
  .sky-info {
    position: fixed;
    top: 12px; right: 12px;
    background: rgba(0,0,0,0.5);
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    color: #cbd5e1;
    z-index: 10;
    border: 1px solid rgba(255,255,255,0.08);
  }

  .coords {
    position: fixed;
    bottom: 90px; left: 12px;
    background: rgba(0,0,0,0.5);
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11px;
    color: #64748b;
    font-family: 'Consolas', monospace;
    z-index: 10;
  }
</style>
</head>
<body>

<canvas id="game"></canvas>
<div class="crosshair"></div>

<div class="info">
  <div class="title">VoxelCraft</div>
  <strong>WASD</strong> — Move player<br/>
  <strong>1</strong> — Diamond Sword<br/>
  <strong>2-9</strong> — Select block<br/>
  <strong>Space / Left Click</strong> — Swing sword<br/>
  <strong>Scroll</strong> — Cycle blocks<br/>
  <strong>T</strong> — Toggle time<br/>
  <strong>R</strong> — Reset world<br/>
  ⚔️ Select slot 1 for Diamond Sword<br/>
  Left click: place block or swing sword<br/>
  Right click: break block<br/>
  Camera follows your character.
  <span style="color:#64748b;">Seed: <?= $seed ?> &middot; v<?= $version ?></span>
</div>

<div class="sky-info" id="skyInfo">☀️ Day</div>
<div class="coords" id="coords">X: 0  Z: 0</div>

<div class="hud">
  <div class="status-row">
    <div class="stat-bar">
      ❤️ <div class="bar-track"><div class="bar-fill" id="hpBar" style="width:100%;background:linear-gradient(90deg,#ef4444,#f87171);"></div></div>
    </div>
    <div class="stat-bar">
      🍖 <div class="bar-track"><div class="bar-fill" id="foodBar" style="width:80%;background:linear-gradient(90deg,#a16207,#eab308);"></div></div>
    </div>
    <div class="stat-bar">
      ⚡ <div class="bar-track"><div class="bar-fill" id="expBar" style="width:35%;background:linear-gradient(90deg,#16a34a,#4ade80);"></div></div>
    </div>
  </div>
  <div class="hotbar" id="hotbar"></div>
</div>

<script>
// ═══════════════════════════════════════════════════════════════════
//  VoxelCraft — Isometric Block Builder Engine
// ═══════════════════════════════════════════════════════════════════

const SEED = <?= $seed ?>;
const CHUNK = 24;          // world size (CHUNK x CHUNK)
const MAX_H = 12;          // max build height
const TILE = 40;           // iso tile pixel size
const HALF = TILE / 2;
const QUARTER = TILE / 4;

// ── Block Definitions ───────────────────────
const BLOCKS = {
  grass:     { top: '#4ade80', side: '#6b4226', front: '#7a5033', label: 'Grass' },
  dirt:      { top: '#8B6914', side: '#6b4226', front: '#7a5033', label: 'Dirt' },
  stone:     { top: '#9ca3af', side: '#6b7280', front: '#7d8490', label: 'Stone' },
  wood:      { top: '#d4a24e', side: '#92631a', front: '#a6752e', label: 'Wood' },
  sand:      { top: '#fde68a', side: '#d4a24e', front: '#e2b960', label: 'Sand' },
  water:     { top: '#38bdf899', side: '#0284c7aa', front: '#0ea5e9aa', label: 'Water' },
  brick:     { top: '#b45309', side: '#92400e', front: '#a34e12', label: 'Brick' },
  diamond:   { top: '#22d3ee', side: '#06b6d4', front: '#14b8c8', label: 'Diamond' },
};

const BLOCK_ORDER = ['sword','grass','dirt','stone','wood','sand','water','brick','diamond'];

// ── State ───────────────────────────────────
let world = {};           // key "x,y,z" → block type
let cam = { x: 0, z: 0 };
let selected = 0;         // hotbar index
let isNight = false;
let keys = {};
let hp = 100, food = 80, exp = 35;
let blockCount = 0;
let kills = 0;
let swordSwing = 0;        // swing animation timer (>0 = swinging)
let damageFlash = 0;       // player damage flash

// ── Mob System ──────────────────────────────
const MOB_TYPES = {
  zombie: {
    label: 'Zombie',
    bodyColor: '#2d5a27',
    headColor: '#3a7a32',
    eyeColor: '#111',
    shirtColor: '#3d6b35',
    hp: 20,
    damage: 5,
    speed: 0.02,
    aggroRange: 6,
    xpDrop: 8,
  },
  skeleton: {
    label: 'Skeleton',
    bodyColor: '#d1d5db',
    headColor: '#e5e7eb',
    eyeColor: '#1a1a1a',
    shirtColor: '#9ca3af',
    hp: 16,
    damage: 4,
    speed: 0.025,
    aggroRange: 8,
    xpDrop: 10,
  },
  spider: {
    label: 'Spider',
    bodyColor: '#3b2a1a',
    headColor: '#4a3828',
    eyeColor: '#ef4444',
    shirtColor: '#2a1d10',
    hp: 14,
    damage: 3,
    speed: 0.035,
    aggroRange: 5,
    xpDrop: 6,
  },
};

const MOB_NAMES = Object.keys(MOB_TYPES);
let mobs = [];
let particles = [];  // damage/death particles
let mobSpawnTimer = 0;
const MAX_MOBS = 8;

// ── Player Character ────────────────────────
let player = {
  x: 12,                  // world grid X
  z: 12,                  // world grid Z
  facing: 'se',           // se, sw, ne, nw
  walkFrame: 0,           // animation frame
  walkTimer: 0,           // animation timer
  moving: false,
};

// Get the Y (height) the player stands on at (x,z)
function getGroundY(gx, gz) {
  let topY = 0;
  for (let y = MAX_H; y >= 0; y--) {
    if (world[`${Math.floor(gx)},${y},${Math.floor(gz)}`]) {
      topY = y + 1;
      break;
    }
  }
  return topY;
}

// ── Draw Player (isometric pixel character) ─
function drawPlayer() {
  const py = getGroundY(player.x, player.z);
  const { sx, sy } = isoProject(player.x, py, player.z);

  const px = sx - cam.x + canvas.width / 2;
  const baseY = sy - cam.z + canvas.height / 2 - 80 + QUARTER;

  // Character dimensions
  const ch = 38;    // total height
  const cw = 14;    // body width
  const headR = 7;  // head radius

  // Animation: slight bob and leg swing
  const bob = player.moving ? Math.sin(player.walkFrame * 0.3) * 2 : 0;
  const legSwing = player.moving ? Math.sin(player.walkFrame * 0.3) * 5 : 0;
  const armSwing = player.moving ? Math.sin(player.walkFrame * 0.3) * 4 : 0;

  const drawY = baseY - ch + bob;

  // Shadow
  ctx.fillStyle = 'rgba(0,0,0,0.18)';
  ctx.beginPath();
  ctx.ellipse(px, baseY + 2, 10, 4, 0, 0, Math.PI * 2);
  ctx.fill();

  // ── Legs ──
  ctx.strokeStyle = '#1e3a5f';
  ctx.lineWidth = 3.5;
  ctx.lineCap = 'round';
  // Left leg
  ctx.beginPath();
  ctx.moveTo(px - 3, drawY + 22);
  ctx.lineTo(px - 3 - legSwing, baseY - 1);
  ctx.stroke();
  // Right leg
  ctx.beginPath();
  ctx.moveTo(px + 3, drawY + 22);
  ctx.lineTo(px + 3 + legSwing, baseY - 1);
  ctx.stroke();

  // ── Shoes ──
  ctx.fillStyle = '#4a3728';
  ctx.fillRect(px - 5 - legSwing, baseY - 4, 5, 4);
  ctx.fillRect(px + 1 + legSwing, baseY - 4, 5, 4);

  // ── Body / Shirt ──
  ctx.fillStyle = '#22b573';
  ctx.fillRect(px - cw / 2, drawY + 10, cw, 14);
  // Belt
  ctx.fillStyle = '#6b4226';
  ctx.fillRect(px - cw / 2, drawY + 21, cw, 3);

  // ── Arms ──
  ctx.strokeStyle = '#d4a373';
  ctx.lineWidth = 3;
  // Left arm
  ctx.beginPath();
  ctx.moveTo(px - cw / 2 - 1, drawY + 12);
  ctx.lineTo(px - cw / 2 - 4, drawY + 22 + armSwing);
  ctx.stroke();

  // Right arm — holds the diamond sword (only when sword slot selected)
  const hasSword = BLOCK_ORDER[selected] === 'sword';
  const swingAngle = swordSwing > 0 ? Math.sin(swordSwing * 0.5) * 1.2 : 0;
  const rightArmEndX = px + cw / 2 + 4 + (hasSword ? swingAngle * 8 : 0);
  const rightArmEndY = drawY + 22 - armSwing - (hasSword ? swingAngle * 10 : 0);
  ctx.beginPath();
  ctx.moveTo(px + cw / 2 + 1, drawY + 12);
  ctx.lineTo(rightArmEndX, rightArmEndY);
  ctx.stroke();

  if (hasSword) {
    // ── Diamond Sword ──
    ctx.save();
    ctx.translate(rightArmEndX, rightArmEndY);
    const swordRot = -0.6 + swingAngle * 1.5;
    ctx.rotate(swordRot);

    // Blade (diamond blue gradient)
    const bladeGrad = ctx.createLinearGradient(0, 0, 0, -28);
    bladeGrad.addColorStop(0, '#06b6d4');
    bladeGrad.addColorStop(0.5, '#22d3ee');
    bladeGrad.addColorStop(1, '#67e8f9');
    ctx.fillStyle = bladeGrad;
    ctx.beginPath();
    ctx.moveTo(-2, 0);
    ctx.lineTo(2, 0);
    ctx.lineTo(1.5, -22);
    ctx.lineTo(0, -28);
    ctx.lineTo(-1.5, -22);
    ctx.closePath();
    ctx.fill();

    // Blade edge highlight
    ctx.strokeStyle = '#a5f3fc';
    ctx.lineWidth = 0.7;
    ctx.beginPath();
    ctx.moveTo(0, -2);
    ctx.lineTo(0, -26);
    ctx.stroke();

    // Guard (gold cross)
    ctx.fillStyle = '#fbbf24';
    ctx.fillRect(-5, -1, 10, 3);

    // Handle (dark)
    ctx.fillStyle = '#6b4226';
    ctx.fillRect(-1.5, 2, 3, 8);

    // Pommel (gold)
    ctx.fillStyle = '#f59e0b';
    ctx.beginPath();
    ctx.arc(0, 11, 2, 0, Math.PI * 2);
    ctx.fill();

    // Sparkle on blade when swinging
    if (swordSwing > 0) {
      ctx.fillStyle = '#fff';
      ctx.globalAlpha = 0.8;
      const sparkY = -8 - Math.sin(swordSwing) * 12;
      ctx.fillRect(-1, sparkY, 2, 2);
      ctx.fillRect(1, sparkY - 5, 2, 2);
      ctx.globalAlpha = 1;
    }

    ctx.restore();
  } else {
    // ── Held block (right hand) when not using sword ──
    const heldType = BLOCK_ORDER[selected];
    const heldBlock = BLOCKS[heldType];
    if (heldBlock) {
      ctx.fillStyle = heldBlock.top;
      ctx.fillRect(rightArmEndX - 2, rightArmEndY - 2, 5, 5);
      ctx.strokeStyle = 'rgba(0,0,0,0.3)';
      ctx.lineWidth = 0.5;
      ctx.strokeRect(rightArmEndX - 2, rightArmEndY - 2, 5, 5);
    }
  }

  // ── Head ──
  ctx.fillStyle = damageFlash > 0 ? '#ff6b6b' : '#d4a373';
  ctx.fillRect(px - headR, drawY, headR * 2, headR * 2);

  // ── Hair ──
  ctx.fillStyle = '#5c3317';
  ctx.fillRect(px - headR, drawY - 1, headR * 2, 5);
  // Side hair flaps
  ctx.fillRect(px - headR - 1, drawY, 3, 8);
  ctx.fillRect(px + headR - 2, drawY, 3, 8);

  // ── Eyes ──
  const eyeOffX = (player.facing === 'sw' || player.facing === 'nw') ? -2 : 2;
  ctx.fillStyle = '#fff';
  ctx.fillRect(px + eyeOffX - 3, drawY + 5, 3, 3);
  ctx.fillRect(px + eyeOffX + 1, drawY + 5, 3, 3);
  // Pupils
  ctx.fillStyle = '#1a1a2e';
  ctx.fillRect(px + eyeOffX - 2, drawY + 6, 2, 2);
  ctx.fillRect(px + eyeOffX + 2, drawY + 6, 2, 2);

  // ── Mouth ──
  ctx.fillStyle = '#a0522d';
  ctx.fillRect(px + eyeOffX - 1, drawY + 10, 3, 1);

  // ── Nametag ──
  ctx.font = '600 10px Inter, sans-serif';
  ctx.textAlign = 'center';
  ctx.fillStyle = 'rgba(0,0,0,0.45)';
  ctx.fillRect(px - 20, drawY - 16, 40, 14);
  ctx.fillStyle = '#fff';
  ctx.fillText('Player', px, drawY - 6);
}

// ── Spawn Mobs ──────────────────────────────
function spawnMob() {
  if (mobs.length >= MAX_MOBS) return;

  const type = MOB_NAMES[Math.floor(Math.random() * MOB_NAMES.length)];
  const def = MOB_TYPES[type];

  // Spawn at a random edge, away from player
  let mx, mz;
  const edge = Math.floor(Math.random() * 4);
  switch (edge) {
    case 0: mx = Math.random() * CHUNK; mz = 0; break;
    case 1: mx = Math.random() * CHUNK; mz = CHUNK - 1; break;
    case 2: mx = 0; mz = Math.random() * CHUNK; break;
    default: mx = CHUNK - 1; mz = Math.random() * CHUNK; break;
  }

  mobs.push({
    type,
    x: mx,
    z: mz,
    hp: def.hp,
    maxHp: def.hp,
    knockX: 0,
    knockZ: 0,
    hurtTimer: 0,
    walkFrame: Math.random() * 100,
  });
}

// ── Draw Mob ────────────────────────────────
function drawMob(mob) {
  const def = MOB_TYPES[mob.type];
  const my = getGroundY(mob.x, mob.z);
  const { sx, sy } = isoProject(mob.x, my, mob.z);

  const mx = sx - cam.x + canvas.width / 2;
  const baseY = sy - cam.z + canvas.height / 2 - 80 + QUARTER;

  const mh = mob.type === 'spider' ? 22 : 34;
  const mw = mob.type === 'spider' ? 18 : 12;
  const walk = Math.sin(mob.walkFrame * 0.2) * 3;
  const drawY = baseY - mh + Math.abs(walk * 0.3);

  // Frustum cull
  if (mx < -60 || mx > canvas.width + 60 || baseY < -60 || baseY > canvas.height + 60) return;

  // Hurt flash
  const isHurt = mob.hurtTimer > 0;

  // Shadow
  ctx.fillStyle = 'rgba(0,0,0,0.15)';
  ctx.beginPath();
  ctx.ellipse(mx, baseY + 2, mob.type === 'spider' ? 14 : 8, 3, 0, 0, Math.PI * 2);
  ctx.fill();

  if (mob.type === 'spider') {
    // ── Spider: wide flat body with legs ──
    // Legs
    ctx.strokeStyle = isHurt ? '#ff4444' : '#2a1d10';
    ctx.lineWidth = 1.5;
    for (let side = -1; side <= 1; side += 2) {
      for (let i = 0; i < 4; i++) {
        const legX = mx + side * (6 + i * 3);
        const legMid = drawY + 8 + Math.sin(mob.walkFrame * 0.25 + i) * 2;
        ctx.beginPath();
        ctx.moveTo(mx + side * 4, drawY + 6 + i * 2);
        ctx.lineTo(legX + side * 5, legMid);
        ctx.lineTo(legX + side * 3, baseY);
        ctx.stroke();
      }
    }
    // Body
    ctx.fillStyle = isHurt ? '#ff4444' : def.bodyColor;
    ctx.beginPath();
    ctx.ellipse(mx, drawY + 8, 10, 6, 0, 0, Math.PI * 2);
    ctx.fill();
    // Head
    ctx.fillStyle = isHurt ? '#ff6666' : def.headColor;
    ctx.beginPath();
    ctx.ellipse(mx, drawY + 2, 6, 5, 0, 0, Math.PI * 2);
    ctx.fill();
    // Eyes (red)
    ctx.fillStyle = def.eyeColor;
    ctx.fillRect(mx - 4, drawY, 3, 3);
    ctx.fillRect(mx + 1, drawY, 3, 3);
  } else {
    // ── Zombie / Skeleton humanoid ──
    // Legs
    ctx.strokeStyle = isHurt ? '#ff4444' : def.bodyColor;
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    ctx.beginPath();
    ctx.moveTo(mx - 3, drawY + 20);
    ctx.lineTo(mx - 3 - walk, baseY - 1);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(mx + 3, drawY + 20);
    ctx.lineTo(mx + 3 + walk, baseY - 1);
    ctx.stroke();

    // Body
    ctx.fillStyle = isHurt ? '#ff4444' : def.shirtColor;
    ctx.fillRect(mx - mw / 2, drawY + 8, mw, 14);

    // Arms (zombies extend forward)
    ctx.strokeStyle = isHurt ? '#ff6666' : def.bodyColor;
    ctx.lineWidth = 2.5;
    const armExt = mob.type === 'zombie' ? 8 : 0;
    ctx.beginPath();
    ctx.moveTo(mx - mw / 2 - 1, drawY + 10);
    ctx.lineTo(mx - mw / 2 - 4, drawY + 18 + walk);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(mx + mw / 2 + 1, drawY + 10);
    ctx.lineTo(mx + mw / 2 + 4 + armExt, drawY + 14 - walk);
    ctx.stroke();

    // Skeleton bow
    if (mob.type === 'skeleton') {
      ctx.strokeStyle = '#8B6914';
      ctx.lineWidth = 1.5;
      ctx.beginPath();
      ctx.arc(mx + mw / 2 + 6, drawY + 12, 6, -1.2, 1.2);
      ctx.stroke();
      // Bowstring
      ctx.strokeStyle = '#ccc';
      ctx.lineWidth = 0.5;
      ctx.beginPath();
      ctx.moveTo(mx + mw / 2 + 4, drawY + 6);
      ctx.lineTo(mx + mw / 2 + 4, drawY + 18);
      ctx.stroke();
    }

    // Head
    ctx.fillStyle = isHurt ? '#ff6666' : def.headColor;
    ctx.fillRect(mx - 6, drawY - 2, 12, 12);

    // Eyes
    ctx.fillStyle = def.eyeColor;
    if (mob.type === 'skeleton') {
      // Hollow eyes
      ctx.fillRect(mx - 4, drawY + 2, 3, 4);
      ctx.fillRect(mx + 1, drawY + 2, 3, 4);
    } else {
      ctx.fillRect(mx - 4, drawY + 3, 3, 2);
      ctx.fillRect(mx + 1, drawY + 3, 3, 2);
    }
  }

  // ── HP Bar above mob ──
  const hpPct = mob.hp / mob.maxHp;
  const barW = 24;
  const barH = 3;
  const barX = mx - barW / 2;
  const barY = drawY - (mob.type === 'spider' ? 8 : 14);

  ctx.fillStyle = 'rgba(0,0,0,0.5)';
  ctx.fillRect(barX - 1, barY - 1, barW + 2, barH + 2);
  ctx.fillStyle = hpPct > 0.5 ? '#22c55e' : hpPct > 0.25 ? '#eab308' : '#ef4444';
  ctx.fillRect(barX, barY, barW * hpPct, barH);

  // Mob label
  ctx.font = '600 8px Inter, sans-serif';
  ctx.textAlign = 'center';
  ctx.fillStyle = 'rgba(255,255,255,0.6)';
  ctx.fillText(def.label, mx, barY - 3);
}

// ── Update Mobs ─────────────────────────────
function updateMobs() {
  // Spawn timer
  mobSpawnTimer++;
  const spawnRate = isNight ? 60 : 180;  // spawn faster at night
  if (mobSpawnTimer >= spawnRate) {
    mobSpawnTimer = 0;
    spawnMob();
  }

  for (let i = mobs.length - 1; i >= 0; i--) {
    const mob = mobs[i];
    const def = MOB_TYPES[mob.type];

    // Distance to player
    const dx = player.x - mob.x;
    const dz = player.z - mob.z;
    const dist = Math.sqrt(dx * dx + dz * dz);

    // Move toward player if in aggro range
    if (dist < def.aggroRange && dist > 0.5) {
      mob.x += (dx / dist) * def.speed;
      mob.z += (dz / dist) * def.speed;
      mob.walkFrame++;
    }

    // Apply knockback
    if (mob.knockX !== 0 || mob.knockZ !== 0) {
      mob.x += mob.knockX;
      mob.z += mob.knockZ;
      mob.knockX *= 0.7;
      mob.knockZ *= 0.7;
      if (Math.abs(mob.knockX) < 0.01) mob.knockX = 0;
      if (Math.abs(mob.knockZ) < 0.01) mob.knockZ = 0;
    }

    // Clamp to world
    mob.x = Math.max(0, Math.min(CHUNK - 1, mob.x));
    mob.z = Math.max(0, Math.min(CHUNK - 1, mob.z));

    // Hurt timer
    if (mob.hurtTimer > 0) mob.hurtTimer--;

    // Mob attacks player on contact
    if (dist < 1.0) {
      if (Math.random() < 0.02) {
        hp = Math.max(0, hp - def.damage);
        damageFlash = 12;
        // Spawn damage particles on player
        spawnParticles(player.x, player.z, '#ff4444', 3);
      }
    }

    // Remove dead mobs
    if (mob.hp <= 0) {
      // Death particles
      spawnParticles(mob.x, mob.z, '#aaa', 8);
      kills++;
      exp = Math.min(100, exp + def.xpDrop);
      // Food recovery on kill
      food = Math.min(100, food + 3);
      mobs.splice(i, 1);
    }
  }

  // Damage flash countdown
  if (damageFlash > 0) damageFlash--;

  // Sword swing countdown
  if (swordSwing > 0) swordSwing--;
}

// ── Attack (Sword Swing) ────────────────────
function playerAttack() {
  if (swordSwing > 0) return;  // already swinging
  swordSwing = 15;  // 15-frame swing animation

  const SWORD_RANGE = 2.0;
  const SWORD_DAMAGE = 7;

  for (const mob of mobs) {
    const dx = mob.x - player.x;
    const dz = mob.z - player.z;
    const dist = Math.sqrt(dx * dx + dz * dz);

    if (dist < SWORD_RANGE) {
      mob.hp -= SWORD_DAMAGE;
      mob.hurtTimer = 10;

      // Knockback away from player
      if (dist > 0.1) {
        mob.knockX = (dx / dist) * 0.6;
        mob.knockZ = (dz / dist) * 0.6;
      }

      // Hit particles
      spawnParticles(mob.x, mob.z, '#22d3ee', 5);
    }
  }
}

// ── Particles System ────────────────────────
function spawnParticles(wx, wz, color, count) {
  for (let i = 0; i < count; i++) {
    particles.push({
      x: wx,
      z: wz,
      vx: (Math.random() - 0.5) * 0.15,
      vz: (Math.random() - 0.5) * 0.15,
      vy: -Math.random() * 0.12,
      life: 20 + Math.floor(Math.random() * 15),
      color,
      size: 2 + Math.random() * 2,
    });
  }
}

function updateParticles() {
  for (let i = particles.length - 1; i >= 0; i--) {
    const p = particles[i];
    p.x += p.vx;
    p.z += p.vz;
    p.vy += 0.008;  // gravity
    p.life--;
    if (p.life <= 0) particles.splice(i, 1);
  }
}

function drawParticles() {
  for (const p of particles) {
    const py = getGroundY(p.x, p.z);
    const { sx, sy } = isoProject(p.x, py, p.z);
    const px = sx - cam.x + canvas.width / 2;
    const screenY = sy - cam.z + canvas.height / 2 - 80 - (p.vy * p.life * 3);

    ctx.globalAlpha = Math.min(1, p.life / 10);
    ctx.fillStyle = p.color;
    ctx.fillRect(px - p.size / 2, screenY, p.size, p.size);
  }
  ctx.globalAlpha = 1;
}

const canvas = document.getElementById('game');
const ctx = canvas.getContext('2d');

// ── Resize ──────────────────────────────────
function resize() {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
}
window.addEventListener('resize', resize);
resize();

// ── Pseudo-random terrain gen ───────────────
function hashNoise(x, z) {
  let h = (x * 374761 + z * 668265 + SEED * 1013) & 0xFFFFFFFF;
  h = ((h >> 16) ^ h) * 0x45d9f3b;
  h = ((h >> 16) ^ h) * 0x45d9f3b;
  h = (h >> 16) ^ h;
  return (h & 0xFF) / 255;
}

function generateTerrain() {
  world = {};
  blockCount = 0;
  for (let x = 0; x < CHUNK; x++) {
    for (let z = 0; z < CHUNK; z++) {
      const n = hashNoise(x, z);
      const height = Math.floor(n * 4) + 1; // 1-4 blocks high

      for (let y = 0; y < height; y++) {
        let type;
        if (y === height - 1) {
          // top layer
          if (n < 0.2) type = 'sand';
          else if (n < 0.35) type = 'water';
          else type = 'grass';
        } else if (y === 0) {
          type = 'stone';
        } else {
          type = 'dirt';
        }
        world[`${x},${y},${z}`] = type;
        blockCount++;
      }
    }
  }
}

// ── Isometric Projection ────────────────────
function isoProject(x, y, z) {
  const screenX = (x - z) * HALF;
  const screenY = (x + z) * QUARTER - y * HALF;
  return { sx: screenX, sy: screenY };
}

// ── Draw Single Isometric Block ─────────────
function drawBlock(x, y, z, type, highlight = false) {
  const block = BLOCKS[type];
  if (!block) return;

  const { sx, sy } = isoProject(x, y, z);

  // Camera offset + center on screen
  const cx = sx - cam.x + canvas.width / 2;
  const cy = sy - cam.z + canvas.height / 2 - 80;

  // Frustum cull
  if (cx < -TILE * 2 || cx > canvas.width + TILE * 2) return;
  if (cy < -TILE * 2 || cy > canvas.height + TILE * 2) return;

  // Night dimming
  const nightAlpha = isNight ? 0.35 : 0;

  // ── Top face ──
  ctx.fillStyle = block.top;
  ctx.beginPath();
  ctx.moveTo(cx, cy - QUARTER);
  ctx.lineTo(cx + HALF, cy);
  ctx.lineTo(cx, cy + QUARTER);
  ctx.lineTo(cx - HALF, cy);
  ctx.closePath();
  ctx.fill();

  // ── Left face ──
  ctx.fillStyle = block.side;
  ctx.beginPath();
  ctx.moveTo(cx - HALF, cy);
  ctx.lineTo(cx, cy + QUARTER);
  ctx.lineTo(cx, cy + QUARTER + HALF);
  ctx.lineTo(cx - HALF, cy + HALF);
  ctx.closePath();
  ctx.fill();

  // ── Right face ──
  ctx.fillStyle = block.front;
  ctx.beginPath();
  ctx.moveTo(cx + HALF, cy);
  ctx.lineTo(cx, cy + QUARTER);
  ctx.lineTo(cx, cy + QUARTER + HALF);
  ctx.lineTo(cx + HALF, cy + HALF);
  ctx.closePath();
  ctx.fill();

  // ── Edges ──
  ctx.strokeStyle = 'rgba(0,0,0,0.2)';
  ctx.lineWidth = 0.5;

  // Top edges
  ctx.beginPath();
  ctx.moveTo(cx, cy - QUARTER);
  ctx.lineTo(cx + HALF, cy);
  ctx.lineTo(cx, cy + QUARTER);
  ctx.lineTo(cx - HALF, cy);
  ctx.closePath();
  ctx.stroke();

  // Left edges
  ctx.beginPath();
  ctx.moveTo(cx - HALF, cy);
  ctx.lineTo(cx - HALF, cy + HALF);
  ctx.lineTo(cx, cy + QUARTER + HALF);
  ctx.lineTo(cx, cy + QUARTER);
  ctx.closePath();
  ctx.stroke();

  // Right edges
  ctx.beginPath();
  ctx.moveTo(cx + HALF, cy);
  ctx.lineTo(cx + HALF, cy + HALF);
  ctx.lineTo(cx, cy + QUARTER + HALF);
  ctx.lineTo(cx, cy + QUARTER);
  ctx.closePath();
  ctx.stroke();

  // Night overlay
  if (nightAlpha > 0) {
    ctx.fillStyle = `rgba(10,15,40,${nightAlpha})`;
    // Top
    ctx.beginPath();
    ctx.moveTo(cx, cy - QUARTER);
    ctx.lineTo(cx + HALF, cy);
    ctx.lineTo(cx, cy + QUARTER);
    ctx.lineTo(cx - HALF, cy);
    ctx.closePath();
    ctx.fill();
    // Left
    ctx.beginPath();
    ctx.moveTo(cx - HALF, cy);
    ctx.lineTo(cx, cy + QUARTER);
    ctx.lineTo(cx, cy + QUARTER + HALF);
    ctx.lineTo(cx - HALF, cy + HALF);
    ctx.closePath();
    ctx.fill();
    // Right
    ctx.beginPath();
    ctx.moveTo(cx + HALF, cy);
    ctx.lineTo(cx, cy + QUARTER);
    ctx.lineTo(cx, cy + QUARTER + HALF);
    ctx.lineTo(cx + HALF, cy + HALF);
    ctx.closePath();
    ctx.fill();
  }

  // Highlight (hover)
  if (highlight) {
    ctx.fillStyle = 'rgba(255,255,255,0.18)';
    ctx.beginPath();
    ctx.moveTo(cx, cy - QUARTER);
    ctx.lineTo(cx + HALF, cy);
    ctx.lineTo(cx, cy + QUARTER);
    ctx.lineTo(cx - HALF, cy);
    ctx.closePath();
    ctx.fill();
  }
}

// ── Render World ────────────────────────────
function render() {
  // Sky gradient
  if (isNight) {
    const grad = ctx.createLinearGradient(0, 0, 0, canvas.height);
    grad.addColorStop(0, '#0a0e27');
    grad.addColorStop(0.5, '#111833');
    grad.addColorStop(1, '#1a1f3a');
    ctx.fillStyle = grad;
  } else {
    const grad = ctx.createLinearGradient(0, 0, 0, canvas.height);
    grad.addColorStop(0, '#60a5fa');
    grad.addColorStop(0.4, '#93c5fd');
    grad.addColorStop(1, '#bfdbfe');
    ctx.fillStyle = grad;
  }
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  // Stars at night
  if (isNight) {
    ctx.fillStyle = 'rgba(255,255,255,0.6)';
    for (let i = 0; i < 80; i++) {
      const sx = (hashNoise(i, 0) * canvas.width);
      const sy = (hashNoise(0, i) * canvas.height * 0.5);
      const r = hashNoise(i, i) * 1.5 + 0.5;
      ctx.beginPath();
      ctx.arc(sx, sy, r, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  // Sun/Moon
  const orbX = canvas.width - 80;
  const orbY = 60;
  if (isNight) {
    ctx.fillStyle = '#e2e8f0';
    ctx.beginPath();
    ctx.arc(orbX, orbY, 22, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = '#0a0e27';
    ctx.beginPath();
    ctx.arc(orbX + 7, orbY - 5, 18, 0, Math.PI * 2);
    ctx.fill();
  } else {
    ctx.fillStyle = '#fbbf24';
    ctx.shadowColor = '#fbbf24';
    ctx.shadowBlur = 30;
    ctx.beginPath();
    ctx.arc(orbX, orbY, 24, 0, Math.PI * 2);
    ctx.fill();
    ctx.shadowBlur = 0;
  }

  // Clouds (day only)
  if (!isNight) {
    ctx.fillStyle = 'rgba(255,255,255,0.35)';
    const t = Date.now() * 0.01;
    for (let i = 0; i < 5; i++) {
      const cx = ((t + i * 300) % (canvas.width + 200)) - 100;
      const cy = 40 + hashNoise(i, 99) * 80;
      ctx.beginPath();
      ctx.ellipse(cx, cy, 60 + i * 12, 18, 0, 0, Math.PI * 2);
      ctx.fill();
      ctx.beginPath();
      ctx.ellipse(cx + 35, cy + 6, 40, 14, 0, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  // Sort blocks back-to-front for painter's algorithm
  const entries = Object.entries(world);
  entries.sort((a, b) => {
    const [ax, ay, az] = a[0].split(',').map(Number);
    const [bx, by, bz] = b[0].split(',').map(Number);
    // Sort by depth: farther tiles first
    const da = ax + az - ay;
    const db = bx + bz - by;
    return da - db;
  });

  // Determine player depth for correct draw order
  const playerGroundY = getGroundY(player.x, player.z);
  const playerDepth = player.x + player.z - playerGroundY;
  let playerDrawn = false;

  for (const [key, type] of entries) {
    const [x, y, z] = key.split(',').map(Number);
    const blockDepth = x + z - y;

    // Draw player when we reach blocks in front of them
    if (!playerDrawn && blockDepth > playerDepth) {
      drawPlayer();
      playerDrawn = true;
    }

    const isHover = (hoverBlock && hoverBlock.x === x && hoverBlock.y === y && hoverBlock.z === z);
    drawBlock(x, y, z, type, isHover);
  }

  // If player is in front of all blocks, draw last
  if (!playerDrawn) {
    drawPlayer();
  }

  // Draw mobs in sorted order
  // (simple approach: draw after blocks for now, sorted by depth)
  const sortedMobs = [...mobs].sort((a, b) => {
    const da = a.x + a.z;
    const db = b.x + b.z;
    return da - db;
  });
  for (const mob of sortedMobs) {
    drawMob(mob);
  }

  // Draw particles on top
  drawParticles();

  // Grid floor hint (subtle)
  ctx.strokeStyle = isNight ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.05)';
  ctx.lineWidth = 0.5;
  for (let x = 0; x <= CHUNK; x++) {
    const s = isoProject(x, 0, 0);
    const e = isoProject(x, 0, CHUNK);
    ctx.beginPath();
    ctx.moveTo(s.sx - cam.x + canvas.width/2, s.sy - cam.z + canvas.height/2 - 80 + QUARTER + HALF);
    ctx.lineTo(e.sx - cam.x + canvas.width/2, e.sy - cam.z + canvas.height/2 - 80 + QUARTER + HALF);
    ctx.stroke();
  }
  for (let z = 0; z <= CHUNK; z++) {
    const s = isoProject(0, 0, z);
    const e = isoProject(CHUNK, 0, z);
    ctx.beginPath();
    ctx.moveTo(s.sx - cam.x + canvas.width/2, s.sy - cam.z + canvas.height/2 - 80 + QUARTER + HALF);
    ctx.lineTo(e.sx - cam.x + canvas.width/2, e.sy - cam.z + canvas.height/2 - 80 + QUARTER + HALF);
    ctx.stroke();
  }
}

// ── Screen to World (approximate hit test) ──
let hoverBlock = null;

function screenToWorld(mx, my) {
  // Test blocks from front to back (reverse painter's order)
  const entries = Object.entries(world);
  entries.sort((a, b) => {
    const [ax, ay, az] = a[0].split(',').map(Number);
    const [bx, by, bz] = b[0].split(',').map(Number);
    return (bx + bz - by) - (ax + az - ay);
  });

  for (const [key] of entries) {
    const [x, y, z] = key.split(',').map(Number);
    const { sx, sy } = isoProject(x, y, z);
    const cx = sx - cam.x + canvas.width / 2;
    const cy = sy - cam.z + canvas.height / 2 - 80;

    // Check if mouse is within the top diamond of this block
    // Using simple bounding box + diamond check
    const dx = mx - cx;
    const dy = my - (cy + QUARTER * 0.5);

    if (Math.abs(dx) / HALF + Math.abs(dy) / (HALF * 0.75) <= 1.0) {
      return { x, y, z };
    }
  }

  // If no block hit, try to find the ground position
  // Reverse-project mouse to ground plane (y = 0)
  const relX = mx - canvas.width / 2 + cam.x;
  const relY = my - canvas.height / 2 + cam.z + 80;

  const gx = Math.floor((relX / HALF + relY / QUARTER) / 2);
  const gz = Math.floor((relY / QUARTER - relX / HALF) / 2);

  if (gx >= 0 && gx < CHUNK && gz >= 0 && gz < CHUNK) {
    return { x: gx, y: -1, z: gz, ground: true };
  }

  return null;
}

// ── Place Block ─────────────────────────────
function placeBlock(mx, my) {
  const hit = screenToWorld(mx, my);
  if (!hit) return;

  const type = BLOCK_ORDER[selected];
  let px, py, pz;

  if (hit.ground) {
    px = hit.x; py = 0; pz = hit.z;
  } else {
    // Place on top of the hit block
    px = hit.x; py = hit.y + 1; pz = hit.z;
  }

  if (py >= MAX_H) return;
  if (py < 0) py = 0;

  const key = `${px},${py},${pz}`;
  if (!world[key]) {
    world[key] = type;
    blockCount++;
    exp = Math.min(100, exp + 2);
    updateBars();
  }
}

// ── Break Block ─────────────────────────────
function breakBlock(mx, my) {
  const hit = screenToWorld(mx, my);
  if (!hit || hit.ground) return;

  const key = `${hit.x},${hit.y},${hit.z}`;
  if (world[key]) {
    delete world[key];
    blockCount--;
    exp = Math.min(100, exp + 1);
    updateBars();
  }
}

// ── Update HUD Bars ─────────────────────────
function updateBars() {
  document.getElementById('hpBar').style.width = hp + '%';
  document.getElementById('foodBar').style.width = food + '%';
  document.getElementById('expBar').style.width = exp + '%';
}

// ── Hotbar Setup ────────────────────────────
function buildHotbar() {
  const bar = document.getElementById('hotbar');
  bar.innerHTML = '';

  BLOCK_ORDER.forEach((type, i) => {
    const isSword = (type === 'sword');
    const block = isSword ? null : BLOCKS[type];
    const slot = document.createElement('div');
    slot.className = 'slot' + (i === selected ? ' active' : '');
    slot.title = isSword ? 'Diamond Sword' : block.label;

    // Mini preview canvas
    const mini = document.createElement('canvas');
    mini.width = 32; mini.height = 32;
    const mc = mini.getContext('2d');

    if (isSword) {
      // Draw mini sword icon
      const sg = mc.createLinearGradient(16, 28, 16, 4);
      sg.addColorStop(0, '#06b6d4');
      sg.addColorStop(0.5, '#22d3ee');
      sg.addColorStop(1, '#67e8f9');
      mc.fillStyle = sg;
      mc.beginPath();
      mc.moveTo(14, 26);
      mc.lineTo(18, 26);
      mc.lineTo(17, 8);
      mc.lineTo(16, 4);
      mc.lineTo(15, 8);
      mc.closePath();
      mc.fill();
      mc.strokeStyle = '#a5f3fc';
      mc.lineWidth = 0.6;
      mc.beginPath();
      mc.moveTo(16, 6);
      mc.lineTo(16, 24);
      mc.stroke();
      mc.fillStyle = '#fbbf24';
      mc.fillRect(11, 25, 10, 3);
      mc.fillStyle = '#6b4226';
      mc.fillRect(15, 28, 3, 4);
    } else {
      // Draw mini isometric block
      const mx = 16, my = 8, mh = 8, mw = 14;

      // Top
      mc.fillStyle = block.top;
      mc.beginPath();
      mc.moveTo(mx, my - 4);
      mc.lineTo(mx + mw, my + 3);
      mc.lineTo(mx, my + 10);
      mc.lineTo(mx - mw, my + 3);
      mc.closePath();
      mc.fill();

      // Left
      mc.fillStyle = block.side;
      mc.beginPath();
      mc.moveTo(mx - mw, my + 3);
      mc.lineTo(mx, my + 10);
      mc.lineTo(mx, my + 10 + mh);
      mc.lineTo(mx - mw, my + 3 + mh);
      mc.closePath();
      mc.fill();

      // Right
      mc.fillStyle = block.front;
      mc.beginPath();
      mc.moveTo(mx + mw, my + 3);
      mc.lineTo(mx, my + 10);
      mc.lineTo(mx, my + 10 + mh);
      mc.lineTo(mx + mw, my + 3 + mh);
      mc.closePath();
      mc.fill();
    }

    const keyLabel = document.createElement('span');
    keyLabel.className = 'key';
    keyLabel.textContent = (i + 1).toString();

    const nameLabel = document.createElement('span');
    nameLabel.className = 'name';
    nameLabel.textContent = isSword ? 'Sword' : block.label;

    slot.appendChild(mini);
    slot.appendChild(keyLabel);
    slot.appendChild(nameLabel);

    slot.addEventListener('click', () => {
      selected = i;
      buildHotbar();
    });

    bar.appendChild(slot);
  });
}

// ── Input Handling ──────────────────────────
canvas.addEventListener('mousedown', (e) => {
  e.preventDefault();
  if (e.button === 0) placeBlock(e.clientX, e.clientY);       // Left = place
  if (e.button === 2) breakBlock(e.clientX, e.clientY);       // Right = break
});

canvas.addEventListener('contextmenu', (e) => e.preventDefault());

canvas.addEventListener('mousemove', (e) => {
  hoverBlock = screenToWorld(e.clientX, e.clientY);
  if (!hoverBlock || hoverBlock.ground) hoverBlock = null;
});

window.addEventListener('keydown', (e) => {
  keys[e.key.toLowerCase()] = true;

  // Number keys 1-9
  const num = parseInt(e.key);
  if (num >= 1 && num <= 9) {
    selected = num - 1;
    buildHotbar();
  }

  // SPACE = sword attack (only when sword is selected)
  if (e.key === ' ') {
    e.preventDefault();
    if (BLOCK_ORDER[selected] === 'sword') {
      playerAttack();
    }
  }

  // Toggle day/night
  if (e.key.toLowerCase() === 't') {
    isNight = !isNight;
    document.getElementById('skyInfo').innerHTML = isNight ? '🌙 Night' : '☀️ Day';
  }

  // Reset world
  if (e.key.toLowerCase() === 'r') {
    generateTerrain();
    mobs = [];
    particles = [];
    kills = 0;
    hp = 100;
    food = 80;
    exp = 35;
    updateBars();
  }
});

window.addEventListener('keyup', (e) => {
  keys[e.key.toLowerCase()] = false;
});

// Mouse wheel to cycle blocks
window.addEventListener('wheel', (e) => {
  if (e.deltaY > 0) selected = (selected + 1) % BLOCK_ORDER.length;
  else selected = (selected - 1 + BLOCK_ORDER.length) % BLOCK_ORDER.length;
  buildHotbar();
});

// ── Player Movement & Camera Follow ─────────
const MOVE_SPEED = 0.08;
const CAM_SMOOTH = 0.1;

function updatePlayer() {
  let moved = false;

  // Iso-aware movement: W = up-left on screen (decreasing X and Z)
  if (keys['w'] || keys['arrowup']) {
    player.x -= MOVE_SPEED;
    player.z -= MOVE_SPEED;
    player.facing = 'nw';
    moved = true;
  }
  if (keys['s'] || keys['arrowdown']) {
    player.x += MOVE_SPEED;
    player.z += MOVE_SPEED;
    player.facing = 'se';
    moved = true;
  }
  if (keys['a'] || keys['arrowleft']) {
    player.x -= MOVE_SPEED;
    player.z += MOVE_SPEED;
    player.facing = 'sw';
    moved = true;
  }
  if (keys['d'] || keys['arrowright']) {
    player.x += MOVE_SPEED;
    player.z -= MOVE_SPEED;
    player.facing = 'ne';
    moved = true;
  }

  // Clamp to world bounds
  player.x = Math.max(0, Math.min(CHUNK - 1, player.x));
  player.z = Math.max(0, Math.min(CHUNK - 1, player.z));

  // Walk animation
  player.moving = moved;
  if (moved) {
    player.walkFrame++;
  } else {
    player.walkFrame = 0;
  }
}

function updateCamera() {
  // Camera smoothly follows the player
  const { sx, sy } = isoProject(player.x, 0, player.z);
  cam.x += (sx - cam.x) * CAM_SMOOTH;
  cam.z += (sy - cam.z) * CAM_SMOOTH;

  // Update coords display
  document.getElementById('coords').textContent =
    `X: ${Math.floor(player.x)}  Z: ${Math.floor(player.z)}  Blocks: ${blockCount}  Kills: ${kills}`;
}

// ── Game Loop ───────────────────────────────
function gameLoop() {
  updatePlayer();
  updateMobs();
  updateParticles();
  updateCamera();
  render();
  updateBars();
  requestAnimationFrame(gameLoop);
}

// ── Slowly drain food (cosmetic) ────────────
setInterval(() => {
  if (food > 10) food -= 1;
  updateBars();
}, 8000);

// ── Init ────────────────────────────────────
generateTerrain();
buildHotbar();
updateBars();
gameLoop();
</script>

</body>
</html>
