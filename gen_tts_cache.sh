#!/bin/bash
# Genera cache audio pre-renderizzata per voce indicata
# Uso: bash gen_tts_cache.sh [paola|riccardo]
# Default: genera entrambe

PIPER="/opt/piper/piper"
PIPER_LIB="/opt/piper"
SOX="/usr/bin/sox"
MAX_JOBS=4
BASE_DIR="/home/acwild-blf/htdocs/blf.acwild.it/tts_cache"

declare -A MODELS=(
  [paola]="/opt/piper/voices/it_IT-paola-medium.onnx"
  [riccardo]="/opt/piper/voices/it_IT-riccardo-x_low.onnx"
)

VOCI=("${@:-paola riccardo}")
[ $# -eq 0 ] && VOCI=(paola riccardo)

gen_one() {
    local text="$1" outfile="$2" model="$3"
    [ -f "$outfile" ] && return 0
    echo "$text" \
      | LD_LIBRARY_PATH="$PIPER_LIB" "$PIPER" --model "$model" --output_raw 2>/dev/null \
      | "$SOX" -t raw -r 22050 -e signed -b 16 -c 1 - "$outfile" pad 0 0.15 2>/dev/null
}

run_bg() {
    gen_one "$1" "$2" "$3" &
    while [ "$(jobs -rp | wc -l)" -ge "$MAX_JOBS" ]; do sleep 0.2; done
}

for VOCE in "${VOCI[@]}"; do
    MODEL="${MODELS[$VOCE]}"
    [ -z "$MODEL" ] && echo "Voce '$VOCE' non valida, skip." && continue
    OUTDIR="$BASE_DIR/$VOCE"
    LOGFILE="$BASE_DIR/gen_${VOCE}.log"
    mkdir -p "$OUTDIR"
    echo "=== Inizio $VOCE $(date) ===" | tee -a "$LOGFILE"

    echo "Turni A-Z..." | tee -a "$LOGFILE"
    for l in {A..Z}; do
        run_bg "Turno $l" "$OUTDIR/t_${l}.wav" "$MODEL"
    done
    wait
    echo "  turni OK: $(find $OUTDIR -name 't_*.wav' | wc -l)/26 ($(date '+%H:%M:%S'))" | tee -a "$LOGFILE"

    echo "Numeri 0-1000..." | tee -a "$LOGFILE"
    for n in $(seq 0 1000); do
        run_bg "numero $n" "$OUTDIR/n_${n}.wav" "$MODEL"
    done
    wait
    echo "  numeri OK: $(find $OUTDIR -name 'n_*.wav' | wc -l)/1001 ($(date '+%H:%M:%S'))" | tee -a "$LOGFILE"

    echo "Sportelli 1-20..." | tee -a "$LOGFILE"
    for s in $(seq 1 20); do
        run_bg "recarsi allo sportello $s" "$OUTDIR/s_${s}.wav" "$MODEL"
    done
    wait
    echo "  sportelli OK: $(find $OUTDIR -name 's_*.wav' | wc -l)/20 ($(date '+%H:%M:%S'))" | tee -a "$LOGFILE"

    count=$(find "$OUTDIR" -name '*.wav' | wc -l)
    size=$(du -sh "$OUTDIR" | cut -f1)
    echo "=== COMPLETATO $VOCE: $count file, $size ($(date)) ===" | tee -a "$LOGFILE"
done
