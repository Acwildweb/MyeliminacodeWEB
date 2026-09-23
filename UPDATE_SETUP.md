# Setup aggiornamenti GitHub (produzione)

Repo: `Acwildweb/MyeliminacodeWEB`
Branch: `windows-iis` (Windows/IIS) · `linux` (Apache/Nginx)

1. Crea un **Fine-grained PAT** su GitHub:
   - Repository access: solo `Acwildweb/MyeliminacodeWEB`
   - Permissions: **Contents: Read** (e Metadata: Read)
2. Copia `update_config.example.json` → `update_config.json`
3. Compila `token` (owner/repo già impostati nell'example)
4. Lascia `branch` vuoto per auto-detect oppure forzalo
5. Dopo il primo deploy, allinea `VERSION.json` allo SHA remoto (o usa Aggiorna)

Il token non viene mai inviato al browser: resta solo in `update_config.json` (gitignored).
