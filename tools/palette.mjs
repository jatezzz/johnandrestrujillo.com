// Surface and text colours for site.css, from one seed with Material Color Utilities (HCT tonal palettes).
// One hue, two neutral palettes: "paper" (low chroma) for the light theme, where the chip is a drawing, and
// "board" (solder-mask chroma) for the dark theme, where it is the part on its board. Tones follow the
// Material 3 surface roles, so contrast comes from tone distance rather than from picking colours by eye.
// The brand colours (accent, pads, lit) are not generated here.
//
//   npm i @material/material-color-utilities@0.3.0 && node tools/palette.mjs   (on a build machine)
import { Hct, TonalPalette, argbFromHex, hexFromArgb } from '@material/material-color-utilities';

const SEED = '#0B5E45';
const hue = Hct.fromInt(argbFromHex(SEED)).hue;
const palettes = {
  light: { neutral: TonalPalette.fromHueAndChroma(hue, 6), variant: TonalPalette.fromHueAndChroma(hue, 8) },
  dark: { neutral: TonalPalette.fromHueAndChroma(hue, 16), variant: TonalPalette.fromHueAndChroma(hue, 20) },
};
// site token: [Material 3 role, light tone, dark tone, palette]
const roles = {
  '--paper': ['surface', 98, 6, 'neutral'],
  '--surface': ['surface-container-lowest / surface-container', 100, 12, 'neutral'],
  '--surface-high': ['surface-container-high', 92, 17, 'neutral'],
  '--ink': ['on-surface', 10, 90, 'neutral'],
  '--graphite': ['on-surface-variant', 30, 80, 'variant'],
  '--field': ['outline', 50, 60, 'variant'],
  '--rule': ['outline-variant', 80, 30, 'variant'],
};
for (const scheme of ['light', 'dark']) {
  console.log(`/* ${scheme}: seed ${SEED}, hue ${hue.toFixed(1)} */`);
  for (const [token, [role, lightTone, darkTone, palette]] of Object.entries(roles)) {
    const tone = scheme === 'light' ? lightTone : darkTone;
    console.log(`${token}: ${hexFromArgb(palettes[scheme][palette].tone(tone)).toUpperCase()}; /* ${role}, T${tone} */`);
  }
}
