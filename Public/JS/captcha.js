document.addEventListener('DOMContentLoaded', function () {

  const LENGTH = 6;
  const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  const CANVAS_ID = 'captcha-canvas';
  const REFRESH_ID = 'captcha-refresh';
  const HIDDEN_INPUT_ID = 'captcha_token';
  const VISIBLE_INPUT_SELECTOR = 'input[name="captcha"]';

  const canvas = document.getElementById(CANVAS_ID);
  const refreshBtn = document.getElementById(REFRESH_ID);
  const hiddenInput = document.getElementById(HIDDEN_INPUT_ID);
  const visibleInput = document.querySelector(VISIBLE_INPUT_SELECTOR);

  if (!canvas || !hiddenInput || !visibleInput) return;

  const ctx = canvas.getContext('2d');

  function genCode(len) {
    let s = '';
    for (let i = 0; i < len; i++) {
      s += CHARS.charAt(Math.floor(Math.random() * CHARS.length));
    }
    return s;
  }

  function randColor(min, max) {
    const r = Math.floor(min + Math.random() * (max - min));
    const g = Math.floor(min + Math.random() * (max - min));
    const b = Math.floor(min + Math.random() * (max - min));
    return 'rgb(' + r + ',' + g + ',' + b + ')';
  }

  function drawCode(code) {
    const w = canvas.width;
    const h = canvas.height;

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, w, h);

    for (let i = 0; i < 3; i++) {
      ctx.strokeStyle = randColor(100, 220);
      ctx.beginPath();
      ctx.moveTo(Math.random() * w, Math.random() * h);
      ctx.lineTo(Math.random() * w, Math.random() * h);
      ctx.stroke();
    }

    ctx.textBaseline = 'middle';
    const fontSize = Math.floor(h * 0.6);
    ctx.font = fontSize + 'px sans-serif';

    const charWidth = w / (code.length + 1);
    for (let i = 0; i < code.length; i++) {
      const ch = code.charAt(i);
      const x = (i + 0.6) * charWidth;
      const y = h / 2 + (Math.random() - 0.5) * (h * 0.18);
      const angle = (Math.random() - 0.5) * 0.6;

      ctx.save();
      ctx.translate(x, y);
      ctx.rotate(angle);
      ctx.fillStyle = randColor(10, 120);
      ctx.fillText(ch, -charWidth * 0.2, 0);
      ctx.restore();
    }

    for (let i = 0; i < 50; i++) {
      ctx.fillStyle = randColor(150, 240);
      ctx.beginPath();
      ctx.arc(Math.random() * w, Math.random() * h, Math.random() * 1.6 + 0.4, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  function refresh(sendFocus) {
    const code = genCode(LENGTH);
    drawCode(code);
    hiddenInput.value = code;
    if (sendFocus) visibleInput.focus();
  }

  canvas.addEventListener('click', function () { refresh(true); });
  if (refreshBtn) refreshBtn.addEventListener('click', function (e) { e.preventDefault(); refresh(true); });
  if (!canvas.width) canvas.width = 150;
  if (!canvas.height) canvas.height = 50;
  refresh(false);
});
