# Setup aggiornamenti GitHub (produzione)

1. Crea un **Fine-grained PAT** su GitHub:
   - Repository access: solo il repo del progetto
   - Permissions: **Contents: Read** (e Metadata: Read)
2. Copia `update_config.example.json` → `update_config.json`
3. Compila `owner`, `repo`, `token`
4. Lascia `branch` vuoto per auto-detect (`windows-iis` su Windows, `linux` altrove) oppure forzaloto
5. Assicurati che su GitHub esistano i branch `windows-iis` e `linux`
6. Dopo il primo push, aggiorna `VERSION.json` con lo `commit` SHA reale (oppure usa Aggiorna una volta allineato)

Il token non viene mai inviato al browser: resta solo in `update_config.json` (gitignored).