#!/bin/bash
# Genera cache audio pre-renderizzata usando edge-tts (Microsoft Neural voices)
# Uso: bash gen_edge_cache.sh [ElsaNeural|IsabellaNeural|DiegoNeural|GiuseppeMultilingualNeural]
# Default: tutte e 4

FFMPEG="/usr/bin/ffmpeg"
MAX_JOBS=4
BASE_DIR="/home/acwild-blf/htdocs/blf.acwild.it/tts_cache"

ALL_VOICES=(ElsaNeural IsabellaNeural DiegoNeural GiuseppeMultilingualNeural)
[ $# -gt 0 ] && ALL_VOICES=("$@")

gen_one() {
    local voice="$1" text="$2" outfile="$3"
    [ -f "$outfile" ] && return 0
    local tmp=$(mktemp /tmp/etmp_XXXXXX.mp3)
    edge-tts --voice "it-IT-${voice}" --text "$text" --write-media "$tmp" 2>/dev/null \
      && "$FFMPEG" -y -i "$tmp" -ar 22050 -ac 1 "$outfile" 2>/dev/null
    rm -f "$tmp"
}

run_bg() {
    gen_one "$1" "$2" "$3" &
    while [ "$(jobs -rp | wc -l)" -ge "$MAX_JOBS" ]; do sleep 0.2; done
}

for VOICE in "${ALL_VOICES[@]}"; do
    OUTDIR="$BASE_DIR/$VOICE"
    LOGFILE="$BASE_DIR/gen_${VOICE}.log"
    mkdir -p "$OUTDIR"
    echo "=== Inizio $VOICE $(date) ===" | tee -a "$LOGFILE"

    echo "Turni A-Z..." | tee -a "$LOGFILE"
    for l in {A..Z}; do
        run_bg "$VOICE" "Turno $l" "$OUTDIR/t_${l}.wav"
    done
    wait
    echo "  turni OK: $(find $OUTDIR -name 't_*.wav' | wc -l)/26 ($(date '+%H:%M:%S'))" | tee -a "$LOGFILE"

    echo "Numeri 0-1000..." | tee -a "$LOGFILE"
    for n in $(seq 0 1000); do
        run_bg "$VOICE" "numero $n" "$OUTDIR/n_${n}.wav"
    done
    wait
    echo "  numeri OK: $(find $OUTDIR -name 'n_*.wav' | wc -l)/1001 ($(date '+%H:%M:%S'))" | tee -a "$LOGFILE"

    echo "Sportelli 1-20..." | tee -a "$LOGFILE"
    for s in $(seq 1 20); do
        run_bg "$VOICE" "recarsi allo sportello $s" "$OUTDIR/s_${s}.wav"
    done
    wait
    echo "  sportelli OK: $(find $OUTDIR -name 's_*.wav' | wc -l)/20 ($(date '+%H:%M:%S'))" | tee -a "$LOGFILE"

    count=$(find "$OUTDIR" -name '*.wav' | wc -l)
    size=$(du -sh "$OUTDIR" | cut -f1)
    echo "=== COMPLETATO $VOICE: $count file, $size ($(date)) ===" | tee -a "$LOGFILE"
done
