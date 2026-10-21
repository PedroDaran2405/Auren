#!/usr/bin/env bash
# Resize the original WordPress artwork; do not redraw or replace the brand.
set -euo pipefail
cd "$(dirname "$0")/.."
command -v magick >/dev/null
for size in 180 192 512; do
  magick apps/mobile/assets/auren-bull-original.png -resize "${size}x${size}" -background '#00121f' -alpha remove -alpha off "PNG24:/tmp/auren-icon-${size}.png"
  base64 -w 0 "/tmp/auren-icon-${size}.png" > "wordpress/auren-app-bridge/assets/icon-${size}.png.b64"
  printf '\n' >> "wordpress/auren-app-bridge/assets/icon-${size}.png.b64"
done
magick apps/mobile/assets/auren-bull-original.png -resize 1024x1024 -background '#00121f' -alpha remove -alpha off PNG24:apps/mobile/assets/icon.png
