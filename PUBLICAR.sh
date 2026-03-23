#!/bin/bash
# ═══════════════════════════════════════════════════════
#  PUBLICAR CURSO ECG EN GITHUB PAGES
#  Ejecuta esto en tu terminal, reemplaza TU-USUARIO
# ═══════════════════════════════════════════════════════

# 1. Asegúrate de tener git instalado
#    En Windows: https://git-scm.com/download/win
#    En Mac: git --version  (se instala solo)
#    En Linux: sudo apt install git

# 2. Crea el repo en GitHub.com:
#    → github.com → New repository
#    → Nombre: ecg-curso
#    → Public ✓
#    → NO inicialices con README (lo tenemos ya)

# 3. Ejecuta estos comandos (reemplaza TU-USUARIO):

cd ecg-curso-github/

git init
git add .
git commit -m "Módulo 1 ECG - Atlas interactivo con IA"

git branch -M main
git remote add origin https://github.com/TU-USUARIO/ecg-curso.git
git push -u origin main

# 4. Activar GitHub Pages:
#    → En github.com/TU-USUARIO/ecg-curso
#    → Settings → Pages
#    → Source: "Deploy from a branch"
#    → Branch: main / (root)
#    → Save

# 5. En ~2 minutos tu módulo estará en:
#    https://TU-USUARIO.github.io/ecg-curso/

echo "✅ Listo. Tu módulo estará en:"
echo "   https://TU-USUARIO.github.io/ecg-curso/"
