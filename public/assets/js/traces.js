// Circuit traces drawn on the GPU (WebGPU): picking a pin sends a pulse along a printed-circuit trace from that
// pin, down beside the chip, into the evidence panel. Loaded only on the first pick (motion.js imports it), only
// with WebGPU and never with reduced motion; anywhere else the page keeps its CSS signal on the panel's edge.
//
// Every trace is a short polyline routed like a board: out from the pin's label to its own column (the top pin
// takes the outermost column, so no two traces cross), a 45-degree corner, then straight down to the panel. The
// fragment shader measures each pixel's distance to every segment, so lines and glow come out anti-aliased at any
// pixel ratio with no geometry to rebuild. It renders one frame per animation step and stops when the pulse lands.

const SHADER = /* wgsl */ `
struct U {
  res: vec2f,
  count: f32,
  selected: f32,
  head: f32,
  tail: f32,
  line: f32,
  glow: f32,
  ink: vec4f,
  hot: vec4f,
};
@group(0) @binding(0) var<uniform> u: U;
@group(0) @binding(1) var<storage, read> seg: array<vec4f>;  // x0 y0 x1 y1 (device px)
@group(0) @binding(2) var<storage, read> info: array<vec4f>; // trace, length before this segment, segment length, 0

@vertex fn vs(@builtin(vertex_index) i: u32) -> @builtin(position) vec4f {
  var p = array(vec2f(-1.0, -3.0), vec2f(-1.0, 1.0), vec2f(3.0, 1.0));
  return vec4f(p[i], 0.0, 1.0);
}

@fragment fn fs(@builtin(position) pos: vec4f) -> @location(0) vec4f {
  let p = pos.xy;
  var base = 0.0;
  var lit = 0.0;
  var pulse = 0.0;
  for (var i = 0u; i < u32(u.count); i++) {
    let a = seg[i].xy;
    let b = seg[i].zw;
    let ab = b - a;
    let t = clamp(dot(p - a, ab) / max(dot(ab, ab), 1e-4), 0.0, 1.0);
    let d = length(p - a - ab * t);
    let stroke = 1.0 - smoothstep(u.line - 0.75, u.line + 0.75, d);
    if (info[i].x == u.selected) {
      lit = max(lit, stroke);
      // how far behind the pulse head this point of the trace is, along the trace
      let behind = u.head - (info[i].y + t * info[i].z);
      let trail = select(0.0, exp(-behind / u.tail), behind >= 0.0);
      let halo = exp(-(d * d) / (2.0 * u.glow * u.glow));
      pulse = max(pulse, trail * max(halo, stroke));
    } else {
      base = max(base, stroke);
    }
  }
  let alpha = max(base * 0.22, max(lit * 0.55, pulse));
  if (alpha <= 0.001) { discard; }
  let heat = clamp(pulse / max(alpha, 1e-4), 0.0, 1.0);
  let rgb = mix(u.ink.rgb, u.hot.rgb, heat);
  return vec4f(rgb * alpha, alpha);
}
`;

const hex = (value, fallback) => {
  const m = /^#?([0-9a-f]{6})$/i.exec((value || '').trim());
  const n = parseInt(m ? m[1] : fallback, 16);
  return [(n >> 16 & 255) / 255, (n >> 8 & 255) / 255, (n & 255) / 255, 1];
};

export async function init(root, pins, panel) {
  if (!navigator.gpu) return null;
  let adapter;
  let device;
  try {
    adapter = await navigator.gpu.requestAdapter({ powerPreference: 'low-power' });
    device = adapter && await adapter.requestDevice();
  } catch (error) {
    return null;
  }
  if (!device) return null;

  const canvas = document.createElement('canvas');
  canvas.className = 'traces';
  canvas.setAttribute('aria-hidden', 'true');
  root.prepend(canvas);
  const ctx = canvas.getContext('webgpu');
  const format = navigator.gpu.getPreferredCanvasFormat();
  ctx.configure({ device, format, alphaMode: 'premultiplied' });

  const module = device.createShaderModule({ code: SHADER });
  const blend = { color: { srcFactor: 'one', dstFactor: 'one-minus-src-alpha' }, alpha: { srcFactor: 'one', dstFactor: 'one-minus-src-alpha' } };
  const pipeline = device.createRenderPipeline({
    layout: 'auto',
    vertex: { module, entryPoint: 'vs' },
    fragment: { module, entryPoint: 'fs', targets: [{ format, blend }] },
    primitive: { topology: 'triangle-list' },
  });
  const uniforms = device.createBuffer({ size: 64, usage: GPUBufferUsage.UNIFORM | GPUBufferUsage.COPY_DST });
  const MAX = 64;
  const segBuffer = device.createBuffer({ size: MAX * 16, usage: GPUBufferUsage.STORAGE | GPUBufferUsage.COPY_DST });
  const metaBuffer = device.createBuffer({ size: MAX * 16, usage: GPUBufferUsage.STORAGE | GPUBufferUsage.COPY_DST });
  const bind = device.createBindGroup({
    layout: pipeline.getBindGroupLayout(0),
    entries: [{ binding: 0, resource: { buffer: uniforms } }, { binding: 1, resource: { buffer: segBuffer } }, { binding: 2, resource: { buffer: metaBuffer } }],
  });

  let count = 0;
  let lengths = [];
  let dpr = 1;
  let selected = -1;
  let head = 0;
  let frame = 0;

  // Route every pin to the panel, in CSS pixels relative to root, then upload in device pixels.
  const layout = () => {
    const box = root.getBoundingClientRect();
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.max(1, Math.round(box.width * dpr));
    canvas.height = Math.max(1, Math.round(box.height * dpr));
    const top = panel.getBoundingClientRect().top - box.top;
    const segs = [];
    const metas = [];
    lengths = [];
    for (const side of ['left', 'right']) {
      const column = pins.filter((pin) => pin.dataset.side === side);
      const labels = column.map((pin) => pin.querySelector('.pin__label').getBoundingClientRect());
      const edge = side === 'left' ? Math.min(...labels.map((r) => r.left)) - box.left : Math.max(...labels.map((r) => r.right)) - box.left;
      const room = side === 'left' ? edge : box.width - edge;
      const step = Math.min(8, (room - 10) / column.length);
      if (step < 3) continue; // no room beside the labels on this layout
      column.forEach((pin, row) => {
        const r = labels[row];
        const y = r.top + r.height / 2 - box.top;
        const dir = side === 'left' ? -1 : 1;
        const x0 = side === 'left' ? r.left - box.left - 4 : r.right - box.left + 4;
        const x1 = edge + dir * (6 + (column.length - 1 - row) * step); // top pin takes the outermost column
        const c = Math.min(6, Math.abs(x1 - x0));
        const pts = [[x0, y], [x1 - dir * c, y], [x1, y + c], [x1, top]];
        const index = pins.indexOf(pin);
        let run = 0;
        for (let i = 0; i < pts.length - 1; i++) {
          const [ax, ay] = pts[i];
          const [bx, by] = pts[i + 1];
          const len = Math.hypot(bx - ax, by - ay) * dpr;
          segs.push(ax * dpr, ay * dpr, bx * dpr, by * dpr);
          metas.push(index, run, len, 0);
          run += len;
        }
        lengths[index] = run;
      });
    }
    count = Math.min(segs.length / 4, MAX);
    device.queue.writeBuffer(segBuffer, 0, new Float32Array(segs.slice(0, MAX * 4)));
    device.queue.writeBuffer(metaBuffer, 0, new Float32Array(metas.slice(0, MAX * 4)));
  };

  const draw = () => {
    const css = getComputedStyle(document.documentElement);
    const data = new Float32Array(16);
    data.set([canvas.width, canvas.height, count, selected, head, 90 * dpr, 0.9 * dpr, 5 * dpr], 0);
    data.set(hex(css.getPropertyValue('--field'), '757680'), 8);
    data.set(hex(css.getPropertyValue('--accent'), '2A5ED9'), 12);
    device.queue.writeBuffer(uniforms, 0, data);
    const encoder = device.createCommandEncoder();
    const pass = encoder.beginRenderPass({
      colorAttachments: [{ view: ctx.getCurrentTexture().createView(), loadOp: 'clear', storeOp: 'store', clearValue: { r: 0, g: 0, b: 0, a: 0 } }],
    });
    pass.setPipeline(pipeline);
    pass.setBindGroup(0, bind);
    pass.draw(3);
    pass.end();
    device.queue.submit([encoder.finish()]);
  };

  const DURATION = 750;
  const run = (start) => {
    cancelAnimationFrame(frame);
    const total = lengths[selected] || 0;
    const tick = (now) => {
      const t = Math.min((now - start) / DURATION, 1);
      const eased = 1 - Math.pow(1 - t, 3); // decelerates into the panel
      head = eased * total;
      draw();
      if (t < 1) frame = requestAnimationFrame(tick);
    };
    frame = requestAnimationFrame(tick);
  };

  new ResizeObserver(() => { layout(); draw(); }).observe(root);
  new MutationObserver(() => draw()).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
  device.lost.then(() => canvas.remove());
  layout();

  return {
    select(pin) {
      const index = pins.indexOf(pin);
      if (index < 0 || lengths[index] === undefined) return;
      layout();
      selected = index;
      head = 0;
      run(performance.now());
    },
  };
}
