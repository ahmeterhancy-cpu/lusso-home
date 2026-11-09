#!/usr/bin/env bash
# lusso:aktar --liste ile oluşan storage/app/gorsel-listesi.txt dosyasındaki
# görselleri curl ile paralel indirir (inmiş dosyayı atlar).
# Kullanım (proje kökünde):  bash tools/gorsel-indir.sh [paralel=8]
set -u
P=${1:-8}
LIST=storage/app/gorsel-listesi.txt
[ -f "$LIST" ] || { echo "Liste yok: $LIST"; exit 1; }
sort -u "$LIST" | awk -F'\t' 'NF==2' | while IFS=$'\t' read -r url rel; do
  printf '%s\t%s\n' "$url" "$rel"
done | xargs -P "$P" -d '\n' -n 1 bash -c '
  url="${0%%	*}"; rel="${0#*	}"; out="public/medya/$rel"
  [ -s "$out" ] && exit 0
  mkdir -p "$(dirname "$out")"
  curl -sfL --retry 3 --retry-delay 1 --max-time 40 -A "LussoImporter/1.0" -o "$out.tmp" "$url" && mv "$out.tmp" "$out" || { rm -f "$out.tmp"; echo "INMEDI $url"; }
'
echo "Bitti. public/medya/urunler: $(ls public/medya/urunler | wc -l) dosya"
